<?php

// 2. 緊急修正版 UserController（フェーズ1：5分で対応）
// ====================================================================

/**
 * UserController.php - 緊急修正版
 * 即座にサービスを復旧させるための防御的プログラミング
 */
class UserController extends CI_Controller
{
    /**
     * JWT の署名検証に使う共通鍵 (HS256)。
     *
     * 修正前はこのプロパティが宣言されておらず、
     * getIdFromJWT() が $this->jwtKey を参照していた。
     *
     *     $ grep -rn "jwtKey" --include=*.php . | grep -v vendor
     *     ./codeigniter-typeerror/after/UserController.php:133: ... $this->jwtKey ...
     *
     * ヒットは 1 件、つまり代入は一度も無い。
     * PHP 8 では未定義プロパティの参照は Warning を出して null を返すため、
     * 署名鍵として null が JWT::decode() に渡されていた。
     *
     *     $ php -r 'class A { public function f() { return $this->jwtKey; } }
     *               var_dump((new A)->f());'
     *     PHP Warning:  Undefined property: A::$jwtKey
     *     NULL
     *
     * 鍵をソースに書くわけにはいかないので、設定から読む。
     * 読めなかった場合は「鍵無しで検証する」のではなく
     * 検証そのものを失敗させる。空の鍵で通してしまうと、
     * 誰でも自分で署名したトークンで他人になりすませる。
     *
     * @var string
     */
    private $jwtKey = '';

    public function __construct()
    {
        parent::__construct();

        // CodeIgniter の設定、無ければ環境変数から読む。
        // どちらにも無ければ空のまま残し、getIdFromJWT() 側で弾く。
        $configured = $this->config->item('jwt_key');
        if (!is_string($configured) || $configured === '') {
            $configured = (string) getenv('JWT_KEY');
        }

        $this->jwtKey = is_string($configured) ? $configured : '';
    }

    public function getUserOptions()
    {
        try {
            // 安全なユーザーID取得
            $userId = $this->getValidUserId();
            
            // 早期バリデーション
            if (!$this->isValidUserId($userId)) {
                return $this->respondWithError('invalid_user_id', 'ユーザー情報が正しくありません。再度ログインしてください。');
            }
            
            // 安全な型変換後にメソッド呼び出し
            $options = $this->OptionService_model->getUserDetail('param1', (int)$userId);
            
            return $this->respondWithSuccess($options);
            
        } catch (Exception $e) {
            // 全てのエラーをキャッチして安全に処理
            return $this->handleSystemError($e);
        }
    }
    
    /**
     * 複数のソースからユーザーIDを安全に取得
     */
    private function getValidUserId()
    {
        // 優先順位付きでID取得を試行
        $sources = [
            'session' => function() { return $_SESSION['user_id'] ?? null; },
            'post' => function() { return $this->input->post('user_id'); },
            'get' => function() { return $this->input->get('id'); },
            'jwt' => function() { return $this->getIdFromJWT(); }
        ];
        
        foreach ($sources as $source => $getter) {
            $id = $getter();
            if (!empty($id) && is_numeric($id)) {
                log_message('info', "User ID obtained from: {$source}");
                return $id;
            }
        }
        
        return null;
    }
    
    /**
     * ユーザーIDの妥当性をチェック
     */
    private function isValidUserId($userId): bool
    {
        return !is_null($userId) 
            && is_numeric($userId) 
            && $userId > 0 
            && $userId <= 2147483647; // INT_MAX check
    }
    
    /**
     * エラーレスポンスを返す
     */
    private function respondWithError(string $errorCode, string $message)
    {
        $this->output
            ->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'error_code' => $errorCode,
                'message' => $message,
                'timestamp' => date('c')
            ]));
    }
    
    /**
     * 成功レスポンスを返す
     */
    private function respondWithSuccess($data)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'data' => $data,
                'timestamp' => date('c')
            ]));
    }
    
    /**
     * システムエラーを安全に処理
     */
    private function handleSystemError(Exception $e)
    {
        $errorId = uniqid('ERR_');
        
        // 詳細ログ記録
        log_message('error', "Error ID: {$errorId} - " . $e->getMessage());
        log_message('error', "Stack trace: " . $e->getTraceAsString());
        
        // ユーザーフレンドリーなレスポンス
        $this->output
            ->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'error_code' => 'system_error',
                'message' => 'システムエラーが発生しました。サポートまでお問い合わせください。',
                'error_id' => $errorId,
                'timestamp' => date('c')
            ]));
    }
    
    /**
     * JWTトークンからユーザーIDを取得（例）
     */
    private function getIdFromJWT()
    {
        $token = $this->input->get_request_header('Authorization');
        if (!$token) return null;

        // 署名鍵が設定されていないなら、検証を試みずに失敗させる。
        //
        // 修正前は未定義プロパティ経由で null が渡っていた。
        // 鍵が空のまま検証を通してしまう実装だと、
        // 攻撃者が自分で署名したトークンを送るだけで
        // 任意の user_id になりすませる。
        // 「鍵が無いので検証できない」は「検証に失敗した」として扱う。
        if ($this->jwtKey === '') {
            log_message('error', 'JWT key is not configured; rejecting token.');
            return null;
        }

        try {
            // JWT処理のロジック（ライブラリに依存）
            $decoded = JWT::decode(str_replace('Bearer ', '', $token), $this->jwtKey, ['HS256']);
            return $decoded->user_id ?? null;
        } catch (Exception $e) {
            log_message('warning', 'JWT validation failed: ' . $e->getMessage());
            return null;
        }
    }
}
