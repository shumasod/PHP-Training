<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseOperationException extends \Exception 
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DatabaseOperationService
{
    private const MAX_ATTEMPTS = 3;
    private const BASE_DELAY_SECONDS = 1;

    /**
     * ログに出してはいけないキー。
     *
     * このサービスは更新内容 ($data) をそのままログへ書いていた。
     * users テーブルの更新に使えば、パスワードや残りのトークンが
     * そのままログファイルへ流れる。
     *
     *     $service->updateWithDeadlockRetry('users', ['password' => $hash], ['id' => 1]);
     *     -> Log::info(..., ['data' => ['password' => $hash]])
     *
     * ログは平文で長期間残り、閲覧できる範囲もアプリ本体より広いことが多い。
     * Laravel の Handler が $dontFlash で同じ項目を除外しているのと同じ考え方。
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'token',
        'credit_card',
        'card_number',
        'cvv',
    ];

    /**
     * ログ用に機微な値を伏せる。
     *
     * 値そのものは伏せつつキーは残す。
     * 「password を更新しようとして失敗した」という事実は
     * 調査に必要なので、キーまで消してしまうと役に立たない。
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $values[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
    
    /**
     * デッドロック対応付きでデータベース更新を実行
     * 
     * @param string $table テーブル名
     * @param array $data 更新データ
     * @param array $where WHERE条件
     * @param int $maxAttempts 最大試行回数
     * @return int 更新された行数
     * @throws DatabaseOperationException
     */
    public function updateWithDeadlockRetry(
        string $table, 
        array $data, 
        array $where = [], 
        int $maxAttempts = self::MAX_ATTEMPTS
    ): int {
        $attempt = 1;
        
        do {
            try {
                return $this->executeUpdate($table, $data, $where);
                
            } catch (\PDOException $e) {
                if ($this->isDeadlock($e)) {
                    if ($attempt === $maxAttempts) {
                        Log::error("デッドロックの解決に失敗しました", [
                            'table' => $table,
                            'attempt' => $attempt,
                            'max_attempts' => $maxAttempts,
                            'error' => $e->getMessage(),
                            'data' => $this->redact($data),
                            'where' => $this->redact($where)
                        ]);
                        throw new DatabaseOperationException(
                            "デッドロックが解決できませんでした（試行回数: {$maxAttempts}回）", 
                            0, 
                            $e
                        );
                    }
                    
                    $this->handleDeadlockRetry($attempt, $e);
                    $attempt++;
                    continue;
                }
                
                // デッドロック以外のPDOException
                Log::error("データベース操作でPDOExceptionが発生しました", [
                    'table' => $table,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'data' => $this->redact($data),
                    'where' => $this->redact($where)
                ]);
                // 例外メッセージに PDO のエラー文を連結しない。
                // PDOException のメッセージにはクエリ本文やテーブル名が
                // 含まれることがあり、この例外がそのまま画面や API の
                // レスポンスへ出ると内部構造が漏れる。
                // 詳細は直前の Log::error に残っており、
                // 元の例外も $previous として保持しているので追跡できる。
                throw new DatabaseOperationException(
                    "データベース操作でエラーが発生しました",
                    0,
                    $e
                );
            }
            
        } while ($attempt <= $maxAttempts);
        
        // ここには到達しないはずだが、安全のため
        throw new DatabaseOperationException("予期しないエラーが発生しました");
    }
    
    /**
     * トランザクション内でのデータ更新を実行
     */
    private function executeUpdate(string $table, array $data, array $where): int
    {
        try {
            DB::beginTransaction();
            
            $query = DB::table($table);
            
            // WHERE条件を適用
            foreach ($where as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
            
            $result = $query->update($data);
            
            if ($result === false) {
                throw new DatabaseOperationException("更新処理が失敗しました");
            }
            
            // 0件更新の場合も正常として扱うが、ログに記録
            if ($result === 0) {
                Log::info("更新対象のレコードが見つかりませんでした", [
                    'table' => $table,
                    'data' => $this->redact($data),
                    'where' => $this->redact($where)
                ]);
            }
            
            DB::commit();
            
            Log::info("データベース更新が完了しました", [
                'table' => $table,
                'affected_rows' => $result,
                'data' => $this->redact($data),
                'where' => $this->redact($where)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * デッドロックかどうかを判定
     */
    private function isDeadlock(\PDOException $e): bool
    {
        $deadlockIndicators = [
            'deadlock',
            'lock wait timeout',
            'try restarting transaction',
            '1213',  // MySQL deadlock error code
            '40001'  // SQL standard deadlock error code
        ];
        
        $message = strtolower($e->getMessage());
        
        foreach ($deadlockIndicators as $indicator) {
            if (str_contains($message, strtolower($indicator))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * デッドロック発生時のリトライ処理
     */
    private function handleDeadlockRetry(int $attempt, \PDOException $e): void
    {
        Log::warning("デッドロックを検出しました。リトライします。", [
            'attempt' => $attempt,
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ]);
        
        // 指数バックオフ + ジッター
        $delay = self::BASE_DELAY_SECONDS * $attempt + mt_rand(100, 500) / 1000;
        usleep((int)($delay * 1000000)); // マイクロ秒に変換
    }
    
    /**
     * バッチ更新（複数レコードを一括更新）
     */
    public function batchUpdateWithDeadlockRetry(
        string $table, 
        array $updates, 
        string $keyColumn = 'id',
        int $maxAttempts = self::MAX_ATTEMPTS
    ): int {
        if (empty($updates)) {
            return 0;
        }
        
        $attempt = 1;
        
        do {
            try {
                return $this->executeBatchUpdate($table, $updates, $keyColumn);
                
            } catch (\PDOException $e) {
                if ($this->isDeadlock($e)) {
                    if ($attempt === $maxAttempts) {
                        Log::error("バッチ更新でデッドロックの解決に失敗しました", [
                            'table' => $table,
                            'attempt' => $attempt,
                            'max_attempts' => $maxAttempts,
                            'error' => $e->getMessage(),
                            'update_count' => count($updates)
                        ]);
                        throw new DatabaseOperationException(
                            "バッチ更新でデッドロックが解決できませんでした", 
                            0, 
                            $e
                        );
                    }
                    
                    $this->handleDeadlockRetry($attempt, $e);
                    $attempt++;
                    continue;
                }
                
                // 同上。PDO のエラー文は連結せず、$previous 経由で追跡する。
                throw new DatabaseOperationException(
                    "バッチ更新でエラーが発生しました",
                    0,
                    $e
                );
            }
            
        } while ($attempt <= $maxAttempts);
        
        throw new DatabaseOperationException("予期しないエラーが発生しました");
    }
    
    /**
     * バッチ更新の実行
     */
    private function executeBatchUpdate(string $table, array $updates, string $keyColumn): int
    {
        try {
            DB::beginTransaction();
            
            $totalAffected = 0;
            
            foreach ($updates as $update) {
                if (!isset($update[$keyColumn])) {
                    throw new DatabaseOperationException("キーカラム '{$keyColumn}' が見つかりません");
                }
                
                $keyValue = $update[$keyColumn];
                unset($update[$keyColumn]); // キーカラムを更新データから除外
                
                $affected = DB::table($table)
                    ->where($keyColumn, $keyValue)
                    ->update($update);
                    
                $totalAffected += $affected;
            }
            
            DB::commit();
            
            Log::info("バッチ更新が完了しました", [
                'table' => $table,
                'total_affected_rows' => $totalAffected,
                'update_count' => count($updates)
            ]);
            
            return $totalAffected;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

// 使用例
class ExampleController
{
    private DatabaseOperationService $dbService;
    
    public function __construct(DatabaseOperationService $dbService)
    {
        $this->dbService = $dbService;
    }
    
    /**
     * 単一レコード更新の例
     */
    public function updateUser(int $userId, array $userData): int
    {
        try {
            return $this->dbService->updateWithDeadlockRetry(
                'users',
                $userData,
                ['id' => $userId]
            );
        } catch (DatabaseOperationException $e) {
            Log::error("ユーザー更新に失敗しました", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * バッチ更新の例
     */
    public function batchUpdateUsers(array $userUpdates): int
    {
        try {
            return $this->dbService->batchUpdateWithDeadlockRetry(
                'users',
                $userUpdates,
                'id'
            );
        } catch (DatabaseOperationException $e) {
            Log::error("ユーザーバッチ更新に失敗しました", [
                'update_count' => count($userUpdates),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * 複雑な更新条件の例
     */
    public function updateActiveUsers(array $data): int
    {
        try {
            return $this->dbService->updateWithDeadlockRetry(
                'users',
                $data,
                [
                    'status' => 'active',
                    'last_login_at' => ['>=', now()->subDays(30)]
                ]
            );
        } catch (DatabaseOperationException $e) {
            Log::error("アクティブユーザー更新に失敗しました", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
