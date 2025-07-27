// ====================================================================
// 5. SessionManager クラス（セッション管理の改善）
// ====================================================================

/**
 * SessionManager.php - セッション管理クラス
 * セキュアで堅牢なセッション管理システム
 */
class SessionManager
{
    private $sessionTimeout = 3600; // 1時間
    private $regenerateInterval = 300; // 5分毎にセッションID再生成
    
    public function __construct()
    {
        $this->initializeSession();
    }
    
    /**
     * セッションの初期化
     */
    private function initializeSession(): void
    {
        // セキュアなセッション設定
        ini_set('session.gc_maxlifetime', $this->sessionTimeout);
        ini_set('session.cookie_lifetime', $this->sessionTimeout);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $this->isHttps());
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * ユーザーIDの安全な取得
     */
    public function getUserId(): ?int
    {
        if (!$this->isSessionValid()) {
            $this->destroySession();
            return null;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            // 最終アクセス時刻更新
            $_SESSION['last_access'] = time();
            
            try {
                return InputValidator::validateUserId($userId);
            } catch (Exception $e) {
                log_message('error', 'Invalid user ID in session: ' . $e->getMessage());
                $this->destroySession();
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * ユーザーIDの安全な設定
     */
    public function setUserId(int $userId): bool
    {
        try {
            $validUserId = InputValidator::validateUserId($userId);
            
            $_SESSION['user_id'] = $validUserId;
            $_SESSION['login_time'] = time();
            $_SESSION['last_access'] = time();
            $_SESSION['fingerprint'] = $this->generateFingerprint();
            
            // セッション再生成（セキュリティ向上）
            session_regenerate_id(true);
            
            log_message('info', "User {$validUserId} logged in successfully");
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to set user ID in session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * セッションの妥当性確認
     */
    private function isSessionValid(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        // タイムアウトチェック
        $lastAccess = $_SESSION['last_access'] ?? 0;
        if (time() - $lastAccess > $this->sessionTimeout) {
            log_message('info', 'Session timeout detected');
            return false;
        }
        
        // セッションハイジャック対策
        if (!$this->validateSessionFingerprint()) {
            log_message('warning', 'Session fingerprint validation failed');
            return false;
        }
        
        // 定期的なセッションID再生成
        $loginTime = $_SESSION['login_time'] ?? time();
        if (time() - $loginTime > $this->regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['login_time'] = time();
        }
        
        return true;
    }
    
    /**
     * セッションフィンガープリントの生成
     */
    private function generateFingerprint(): string
    {
        return hash('sha256', 
            ($_SERVER['HTTP_USER_AGENT'] ?? '') .
            ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') .
            ($_SERVER['REMOTE_ADDR'] ?? '') .
            ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')
        );
    }
    
    /**
     * セッションフィンガープリントの検証
     */
    private function validateSessionFingerprint(): bool
    {
        $currentFingerprint = $this->generateFingerprint();
        $sessionFingerprint = $_SESSION['fingerprint'] ?? null;
        
        if ($sessionFingerprint === null) {
            $_SESSION['fingerprint'] = $currentFingerprint;
            return true;
        }
        
        return hash_equals($sessionFingerprint, $currentFingerprint);
    }
    
    /**
     * セッションの安全な破棄
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // セッションデータクリア
            session_unset();
            session_destroy();
            
            // クッキー削除
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            log_message('info', 'Session destroyed successfully');
        }
    }
    
    /**
     * HTTPS接続の確認
     */
    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * セッション情報の取得（デバッグ用）
     */
    public function getSessionInfo(): array
    {
        return [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'last_access' => $_SESSION['last_access'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'is_valid' => $this->isSessionValid()
        ];
    }
    
    /**
     * ユーザー権限の確認
     */
    public function hasPermission(string $permission): bool
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return false;
        }
        
        $userPermissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $userPermissions) || in_array('admin', $userPermissions);
    }
    
    /**
     * Flash メッセージの設定
     */
    public function setFlashMessage(string $type, string $message): void
    {
        $_SESSION['flash_messages'][$type][] = $message;
    }
    
    /**
     * Flash メッセージの取得
     */
    public function getFlashMessages(string $type = null): array
    {
        if ($type) {
            $messages = $_SESSION['flash_messages'][$type] ?? [];
            unset($_SESSION['flash_messages'][$type]);
            return $messages;
        }
        
        $allMessages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $allMessages;
    }
}
