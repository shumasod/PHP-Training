// ====================================================================
// 3. 改善版 OptionService_model（フェーズ2：堅牢化）
// ====================================================================

/**
 * OptionService_model.php - 改善版
 * 防御的プログラミングとエラーハンドリング強化
 */
class OptionService_model extends CI_Model
{
    /**
     * ユーザー詳細情報を取得（nullable型宣言で安全性向上）
     */
    public function getUserDetail(string $param1, ?int $userId = null): array
    {
        // 入力バリデーション
        $this->validateInput($param1, $userId);
        
        // ユーザー存在確認
        $this->ensureUserExists($userId);
        
        // データ取得
        return $this->fetchUserOptions($userId);
    }
    
    /**
     * 入力パラメータの妥当性検証
     */
    private function validateInput(string $param1, ?int $userId): void
    {
        if (empty($param1)) {
            throw new InvalidArgumentException('Parameter 1 cannot be empty');
        }
        
        if (is_null($userId)) {
            throw new InvalidArgumentException('有効なユーザーIDが必要です');
        }
        
        if ($userId <= 0) {
            throw new InvalidArgumentException('ユーザーIDは正の整数である必要があります');
        }
        
        if ($userId > 2147483647) {
            throw new InvalidArgumentException('ユーザーIDが範囲外です');
        }
    }
    
    /**
     * ユーザーの存在確認
     */
    private function ensureUserExists(int $userId): void
    {
        $userExists = $this->db
            ->where('id', $userId)
            ->where('status', 'active')
            ->count_all_results('users');
        
        if ($userExists === 0) {
            throw new RuntimeException("ユーザーID {$userId} は存在しないか、無効です");
        }
    }
    
    /**
     * ユーザーオプションデータを取得
     */
    private function fetchUserOptions(int $userId): array
    {
        $query = $this->db
            ->where('user_id', $userId)
            ->where('deleted_at IS NULL')
            ->order_by('created_at', 'DESC')
            ->get('user_options');
        
        if ($query->num_rows() === 0) {
            log_message('info', "No options found for user {$userId}");
            return [];
        }
        
        $results = $query->result_array();
        log_message('info', "Retrieved " . count($results) . " options for user {$userId}");
        
        return $results;
    }
    
    /**
     * より安全な代替メソッド（型変換含む）
     */
    public function getUserDetailSafe(string $param1, $userId): array
    {
        try {
            // 安全な型変換
            $safeUserId = $this->convertToSafeInteger($userId);
            return $this->getUserDetail($param1, $safeUserId);
        } catch (Exception $e) {
            log_message('error', 'getUserDetailSafe failed: ' . $e->getMessage());
            return $this->getDefaultUserOptions();
        }
    }
    
    /**
     * 値を安全に整数に変換
     */
    private function convertToSafeInteger($value): int
    {
        if (is_null($value)) {
            throw new InvalidArgumentException('Value cannot be null');
        }
        
        if (is_int($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || !ctype_digit($trimmed)) {
                throw new InvalidArgumentException('Value must be numeric string');
            }
            return (int) $trimmed;
        }
        
        throw new InvalidArgumentException('Value must be integer or numeric string');
    }
    
    /**
     * デフォルトのユーザーオプションを返す
     */
    private function getDefaultUserOptions(): array
    {
        return [
            'options' => [],
            'message' => 'デフォルト設定を使用しています',
            'is_default' => true
        ];
    }
}
