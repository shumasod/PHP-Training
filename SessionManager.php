<?php

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
        // セッション開始後に ini_set しても効果がないので、
        // すでに開始済みなら何もしない。
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        // セキュアなセッション設定
        ini_set('session.gc_maxlifetime', (string) $this->sessionTimeout);

        // cookie_lifetime は 0 (ブラウザを閉じるまで) にする。
        // 有効期限を持つ cookie はディスクへ永続化されるため、
        // 共用端末では次の利用者がファイルを読める。
        // セッションの寿命は $sessionTimeout によるサーバ側判定で管理する。
        ini_set('session.cookie_lifetime', '0');

        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $this->isHttps() ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');

        // セッション ID を URL に載せない。
        // 有効だと Referer やアクセスログ経由で ID が漏れる。
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_start();
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

            // セッション固定化対策。
            // ログイン「前」に攻撃者が握らせた ID を無効にする必要があるので、
            // 権限をセッションへ書き込む前に ID を振り直す。
            session_regenerate_id(true);

            $now = time();
            $_SESSION['user_id'] = $validUserId;
            $_SESSION['login_time'] = $now;
            $_SESSION['last_access'] = $now;
            $_SESSION['last_regenerated'] = $now;
            $_SESSION['fingerprint'] = $this->generateFingerprint();

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
        //
        // 修正前は再生成のたびに login_time を上書きしていたため、
        //   - ログイン時刻が分からなくなる（監査・絶対有効期限の判定に使えない）
        //   - 5 分ごとに login_time が更新され続けるので、
        //     絶対有効期限を足したくても実装できない
        // という問題があった。再生成の時刻は別のキーで管理する。
        $lastRegenerated = $_SESSION['last_regenerated'] ?? 0;
        if (time() - $lastRegenerated > $this->regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['last_regenerated'] = time();
        }

        return true;
    }

    /**
     * セッションフィンガープリントの生成
     *
     * リクエストごとに変わりうる値を混ぜるとセッションが無用に切れるため、
     * 比較的安定した値だけを使う。
     *
     * 除外した理由:
     *   REMOTE_ADDR         モバイル回線や企業プロキシでは IP が頻繁に変わる。
     *                       同一 NAT 配下からの攻撃も防げないため費用対効果が悪い。
     *   HTTP_ACCEPT_ENCODING  プロキシや CDN が書き換えることがある。
     *
     * なお、これらの値はクライアントが自由に送れるヘッダなので、
     * フィンガープリントはあくまで多層防御の一枚であり、
     * これ単体でセッションハイジャックを防げるものではない。
     */
    private function generateFingerprint(): string
    {
        return hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' .
            ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')
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
            $sessionName = session_name();
            // 設定値は session_destroy() の前に読んでおく。
            $params = session_get_cookie_params();
            $useCookies = (bool) ini_get('session.use_cookies');

            // セッションデータクリア
            session_unset();
            session_destroy();

            // クッキー削除
            // 元のコードは SameSite 属性を指定していなかった。
            // 削除用の Set-Cookie は発行時と属性が一致しないと
            // ブラウザに無視され、クッキーが残ることがある。
            if ($useCookies) {
                setcookie($sessionName, '', [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?: 'Strict',
                ]);
            }

            log_message('info', 'Session destroyed successfully');
        }
    }

    /**
     * HTTPS接続の確認
     */
    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        // CLI では SERVER_PORT が存在しないため、元のコードは
        // Warning: Undefined array key "SERVER_PORT" を出していた。
        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }

        // X-Forwarded-Proto はクライアントが自由に送れるヘッダなので、
        // 信頼できるリバースプロキシ配下でのみ参照すること。
        // プロキシがこのヘッダを必ず上書きする設定になっているかを確認する。
        return isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
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

        // in_array の第 3 引数を省略すると緩い比較になる。
        // 権限リストに数値が紛れ込んでいる場合、
        // in_array('admin', [0]) が true になり全権限が通ってしまう。
        // 厳密比較を必ず指定する。
        return in_array($permission, $userPermissions, true)
            || in_array('admin', $userPermissions, true);
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
    // PHP 8.4 以降、null デフォルト値による暗黙の nullable は Deprecated。
    public function getFlashMessages(?string $type = null): array
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
