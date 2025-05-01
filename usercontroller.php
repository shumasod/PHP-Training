<?php
class UserController {
    private $userModel;
    
    /**
     * コンストラクタ - 依存性の注入
     */
    public function __construct($userModel) {
        $this->userModel = $userModel;
    }
    
    /**
     * ユーザーの一覧を表示するメソッド
     * @return void
     */
    public function index() {
        try {
            // ユーザーのデータを取得する処理
            $users = $this->userModel->getAllUsers();
            // ビューにデータを渡して表示
            $this->renderView('user/index', ['users' => $users]);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * ユーザーを新規作成するメソッド
     * @return void
     */
    public function create() {
        // CSRFトークンの検証
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // CSRFトークンの検証
                $this->validateCSRFToken($_POST['csrf_token'] ?? '');
                
                // 入力データのバリデーション
                $userData = $this->validateUserData($_POST);
                
                // データベースに新規ユーザーを保存
                $userId = $this->userModel->createUser($userData);
                
                if ($userId) {
                    // 成功メッセージをセット
                    $_SESSION['flash_message'] = 'ユーザーが正常に作成されました';
                    // ユーザー一覧ページにリダイレクト
                    $this->redirect('/user/index');
                }
            } catch (ValidationException $e) {
                // バリデーションエラーの場合、フォームを再表示して入力値を保持
                $this->renderView('user/create', [
                    'errors' => $e->getErrors(),
                    'input' => $_POST
                ]);
                return;
            } catch (Exception $e) {
                $this->handleError($e->getMessage());
                return;
            }
        }
        
        // 新規ユーザー作成のフォームを表示（GETリクエスト時）
        $this->renderView('user/create', [
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }
    
    /**
     * ユーザーの詳細情報を表示するメソッド
     * @param int $userId ユーザーID
     * @return void
     */
    public function show($userId) {
        try {
            // IDの検証
            $userId = $this->validateId($userId);
            
            // 特定のユーザーのデータを取得
            $user = $this->userModel->getUserById($userId);
            
            // ユーザーが存在しない場合
            if (!$user) {
                throw new NotFoundException('指定されたユーザーは存在しません');
            }
            
            // ユーザー詳細のビューを表示
            $this->renderView('user/show', ['user' => $user]);
        } catch (NotFoundException $e) {
            // 404エラー処理
            $this->renderError(404, $e->getMessage());
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * ユーザー情報を更新するメソッド
     * @param int $userId ユーザーID
     * @return void
     */
    public function update($userId) {
        try {
            // IDの検証
            $userId = $this->validateId($userId);
            
            // ユーザーの存在チェック
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new NotFoundException('指定されたユーザーは存在しません');
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // CSRFトークンの検証
                $this->validateCSRFToken($_POST['csrf_token'] ?? '');
                
                // 入力データのバリデーション
                $userData = $this->validateUserData($_POST, true);
                
                // データベースのユーザー情報を更新
                $result = $this->userModel->updateUser($userId, $userData);
                
                if ($result) {
                    // 成功メッセージをセット
                    $_SESSION['flash_message'] = 'ユーザー情報が正常に更新されました';
                    // ユーザー詳細ページにリダイレクト
                    $this->redirect('/user/show/' . $userId);
                }
            }
            
            // ユーザー情報更新のフォームを表示（GETリクエスト時またはバリデーションエラー時）
            $this->renderView('user/update', [
                'user' => $user,
                'csrf_token' => $this->generateCSRFToken()
            ]);
        } catch (ValidationException $e) {
            // バリデーションエラーの場合、フォームを再表示して入力値を保持
            $this->renderView('user/update', [
                'user' => $user,
                'errors' => $e->getErrors(),
                'input' => $_POST,
                'csrf_token' => $this->generateCSRFToken()
            ]);
        } catch (NotFoundException $e) {
            // 404エラー処理
            $this->renderError(404, $e->getMessage());
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * ユーザーを削除するメソッド
     * @param int $userId ユーザーID
     * @return void
     */
    public function delete($userId) {
        try {
            // POSTメソッドかつ削除確認フォームからのリクエストであることを確認
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new BadRequestException('不正なリクエストです');
            }
            
            // CSRFトークンの検証
            $this->validateCSRFToken($_POST['csrf_token'] ?? '');
            
            // IDの検証
            $userId = $this->validateId($userId);
            
            // ユーザーの存在チェック
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new NotFoundException('指定されたユーザーは存在しません');
            }
            
            // データベースからユーザーを削除
            $result = $this->userModel->deleteUser($userId);
            
            if ($result) {
                // 成功メッセージをセット
                $_SESSION['flash_message'] = 'ユーザーが正常に削除されました';
            } else {
                // エラーメッセージをセット
                $_SESSION['flash_error'] = 'ユーザーの削除に失敗しました';
            }
            
            // ユーザー一覧ページにリダイレクト
            $this->redirect('/user/index');
        } catch (BadRequestException $e) {
            // 400エラー処理
            $this->renderError(400, $e->getMessage());
        } catch (NotFoundException $e) {
            // 404エラー処理
            $this->renderError(404, $e->getMessage());
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * 入力データのバリデーションを行うメソッド
     * @param array $data 検証するデータ
     * @param bool $isUpdate 更新時のバリデーションかどうか
     * @return array 検証済みのデータ
     * @throws ValidationException バリデーションエラーが発生した場合
     */
    private function validateUserData(array $data, bool $isUpdate = false): array {
        $errors = [];
        $validatedData = [];
        
        // 名前のバリデーション
        if (empty($data['name'])) {
            $errors['name'] = '名前は必須です';
        } elseif (mb_strlen($data['name']) > 50) {
            $errors['name'] = '名前は50文字以内で入力してください';
        } else {
            $validatedData['name'] = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
        }
        
        // メールアドレスのバリデーション
        if (empty($data['email'])) {
            $errors['email'] = 'メールアドレスは必須です';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '有効なメールアドレスを入力してください';
        } else {
            $validatedData['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            
            // メールアドレスの重複チェック（更新時は自分自身を除外）
            if (!$isUpdate && $this->userModel->isEmailExists($validatedData['email'])) {
                $errors['email'] = 'このメールアドレスは既に使用されています';
            }
        }
        
        // パスワードのバリデーション（新規作成時は必須、更新時は任意）
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password']) && !$isUpdate) {
                $errors['password'] = 'パスワードは必須です';
            } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
                $errors['password'] = 'パスワードは8文字以上で入力してください';
            } elseif (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'パスワードと確認用パスワードが一致しません';
            } elseif (!empty($data['password'])) {
                // パスワードをハッシュ化
                $validatedData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }
        
        // その他必要なバリデーションをここに追加
        
        // エラーがある場合は例外をスロー
        if (!empty($errors)) {
            throw new ValidationException('入力内容に誤りがあります', $errors);
        }
        
        return $validatedData;
    }
    
    /**
     * IDの検証を行うメソッド
     * @param mixed $id 検証するID
     * @return int 検証済みのID
     * @throws InvalidArgumentException 無効なIDの場合
     */
    private function validateId($id): int {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new InvalidArgumentException('無効なIDです');
        }
        return $id;
    }
    
    /**
     * CSRFトークンを生成するメソッド
     * @return string 生成されたトークン
     */
    private function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRFトークンを検証するメソッド
     * @param string $token 検証するトークン
     * @return bool 検証結果
     * @throws SecurityException トークンが無効な場合
     */
    private function validateCSRFToken(string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new SecurityException('セキュリティトークンが無効です');
        }
        return true;
    }
    
    /**
     * リダイレクト処理を行うメソッド
     * @param string $url リダイレクト先URL
     * @return void
     */
    private function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * ビューをレンダリングするメソッド
     * @param string $view 表示するビューのパス
     * @param array $data ビューに渡すデータ
     * @return void
     */
    private function renderView(string $view, array $data = []): void {
        // 与えられた変数を抽出してビュー内で使えるようにする
        extract($data);
        
        // ビューファイルのパス
        $viewPath = 'views/' . $view . '.php';
        
        // ビューファイルの存在確認
        if (!file_exists($viewPath)) {
            throw new Exception('ビューファイルが見つかりません: ' . $viewPath);
        }
        
        // ビューの読み込み
        require $viewPath;
    }
    
    /**
     * エラー画面をレンダリングするメソッド
     * @param int $statusCode HTTPステータスコード
     * @param string $message エラーメッセージ
     * @return void
     */
    private function renderError(int $statusCode, string $message): void {
        http_response_code($statusCode);
        $this->renderView('error/index', [
            'statusCode' => $statusCode,
            'message' => $message
        ]);
        exit;
    }
    
    /**
     * エラーハンドリングを行うメソッド
     * @param string $message エラーメッセージ
     * @return void
     */
    private function handleError(string $message): void {
        // エラーログの記録
        error_log('Error in UserController: ' . $message);
        
        // 500エラー画面の表示
        $this->renderError(500, 'サーバーエラーが発生しました');
    }
}

/**
 * バリデーションエラー用の例外クラス
 */
class ValidationException extends Exception {
    private $errors = [];
    
    public function __construct(string $message, array $errors = [], int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
}

/**
 * リソースが見つからない場合の例外クラス
 */
class NotFoundException extends Exception {
}

/**
 * 不正なリクエストの場合の例外クラス
 */
class BadRequestException extends Exception {
}

/**
 * セキュリティ関連の例外クラス
 */
class SecurityException extends Exception {
}
?>
