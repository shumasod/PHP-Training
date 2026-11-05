<?php

// 2. 緊急修正版 UserController（フェーズ1：5分で対応）
// ====================================================================

/**
 * UserController.php - 緊急修正版
 * 即座にサービスを復旧させるための防御的プログラミング
 */
class UserController extends CI_Controller
{
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
     * 「自分が誰か」を、サーバ側が検証済みの情報だけから取得する。
     *
     * 修正前はここが次のようになっていた。
     *
     *     $sources = [
     *         'session' => function() { return $_SESSION['user_id'] ?? null; },
     *         'post'    => function() { return $this->input->post('user_id'); },
     *         'get'     => function() { return $this->input->get('id'); },
     *         'jwt'     => function() { return $this->getIdFromJWT(); }
     *     ];
     *
     * 4 つの「ソース」のうち 2 つ (post / get) は
     * リクエストに付いてくるただのパラメータで、攻撃者が自由に決められる。
     * これは「ユーザー ID の取得元」ではなく「ユーザーの自己申告」である。
     *
     * しかも session が最初なので、ログインしていない相手には
     * session が null を返し、そのまま get の ?id=... が採用されていた。
     * つまり
     *
     *     GET /user/getUserOptions?id=1
     *
     * を投げるだけで、ログインせずに他人のオプション情報が読めた。
     * isValidUserId() は数値かどうかと範囲しか見ておらず、
     * 「その ID が本当に呼び出し元のものか」は誰も確認していない。
     *
     * 身元はサーバが検証した情報からしか決めてはいけないので、
     * post / get を取得元から外した。残すのは
     *
     *   - session: ログイン時にサーバが書き込んだ値
     *   - jwt:     署名を検証して初めて中身を信用する値
     *
     * の 2 つだけ。
     *
     * 他人の ID を指定して代わりに操作する機能が本当に必要なら、
     * それは「自分が誰か」とは別の引数として受け取り、
     * 管理者権限の確認を通したうえで扱うこと。
     */
    private function getValidUserId()
    {
        // 優先順位付きでID取得を試行
        //
        // 並び順の意味: session はサーバが自分で書いた値なので最も確実。
        // jwt は署名検証を通った場合にのみ値を返す (getIdFromJWT() 参照)。
        $sources = [
            'session' => function() { return $_SESSION['user_id'] ?? null; },
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
     * ユーザーIDの「書式」をチェックする。
     *
     * ここで見ているのは数値かどうかと範囲だけで、
     * 「その ID が呼び出し元のものか」は一切確認していない。
     * つまりこれは認可チェックではない。
     *
     * 名前が isValid... なので認可まで済んでいるように読めてしまい、
     * 実際 getValidUserId() が ?id=... を受け付けていた間は
     * 「検証済みの ID」のように扱われていた。
     * 身元の確定は getValidUserId() 側の責任である。
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
