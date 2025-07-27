/ ====================================================================
// 4. InputValidator クラス（フェーズ3：予防システム）
// ====================================================================

/**
 * InputValidator.php - 共通バリデーションクラス
 * 型安全性を保証する汎用バリデーター
 */
class InputValidator
{
    private static $errorMessages = [
        'null' => 'ユーザーIDが設定されていません',
        'empty' => 'ユーザーIDが空です',
        'non_numeric' => 'ユーザーIDは数値である必要があります',
        'negative' => 'ユーザーIDは正の整数である必要があります',
        'too_large' => 'ユーザーIDが範囲外です',
        'invalid_format' => 'ユーザーIDの形式が正しくありません'
    ];
    
    /**
     * ユーザーIDの厳密なバリデーション
     */
    public static function validateUserId($userId, bool $allowNull = false): int
    {
        // null チェック
        if (is_null($userId)) {
            if ($allowNull) {
                return 0;
            }
            throw new InvalidArgumentException(self::$errorMessages['null']);
        }
        
        // 空文字チェック
        if ($userId === '' || $userId === '0') {
            throw new InvalidArgumentException(self::$errorMessages['empty']);
        }
        
        // 型別処理
        if (is_string($userId)) {
            return self::validateStringUserId($userId);
        }
        
        if (is_int($userId)) {
            return self::validateIntUserId($userId);
        }
        
        if (is_float($userId)) {
            return self::validateFloatUserId($userId);
        }
        
        throw new InvalidArgumentException(self::$errorMessages['invalid_format']);
    }
    
    /**
     * 文字列型ユーザーIDのバリデーション
     */
    private static function validateStringUserId(string $userId): int
    {
        $trimmed = trim($userId);
        
        if (!ctype_digit($trimmed)) {
            throw new InvalidArgumentException(self::$errorMessages['non_numeric']);
        }
        
        $intValue = (int) $trimmed;
        return self::validateIntUserId($intValue);
    }
    
    /**
     * 整数型ユーザーIDのバリデーション
     */
    private static function validateIntUserId(int $userId): int
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException(self::$errorMessages['negative']);
        }
        
        if ($userId > 2147483647) {
            throw new InvalidArgumentException(self::$errorMessages['too_large']);
        }
        
        return $userId;
    }
    
    /**
     * 浮動小数点型ユーザーIDのバリデーション
     */
    private static function validateFloatUserId(float $userId): int
    {
        if ($userId != floor($userId)) {
            throw new InvalidArgumentException(self::$errorMessages['invalid_format']);
        }
        
        $intValue = (int) $userId;
        return self::validateIntUserId($intValue);
    }
    
    /**
     * 安全なバリデーション（例外を投げない）
     */
    public static function validateUserIdSafe($userId): ?int
    {
        try {
            return self::validateUserId($userId);
        } catch (Exception $e) {
            log_message('warning', 'User ID validation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 複数のユーザーIDを一括バリデーション
     */
    public static function validateUserIds(array $userIds): array
    {
        $validIds = [];
        $errors = [];
        
        foreach ($userIds as $index => $userId) {
            try {
                $validIds[] = self::validateUserId($userId);
            } catch (Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        return [
            'valid_ids' => $validIds,
            'errors' => $errors
        ];
    }
    
    /**
     * メールアドレスのバリデーション
     */
    public static function validateEmail(string $email): string
    {
        $email = trim($email);
        
        if (empty($email)) {
            throw new InvalidArgumentException('メールアドレスが入力されていません');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('メールアドレスの形式が正しくありません');
        }
        
        return $email;
    }
    
    /**
     * パスワードの強度チェック
     */
    public static function validatePassword(string $password): string
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('パスワードは8文字以上である必要があります');
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('パスワードには大文字を含める必要があります');
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('パスワードには小文字を含める必要があります');
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('パスワードには数字を含める必要があります');
        }
        
        return $password;
    }
}
