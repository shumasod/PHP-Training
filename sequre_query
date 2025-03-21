<?php
// 環境変数からデータベース接続情報を取得（推奨）
// 実際の環境では.envファイルや環境設定から読み込むことを推奨
$host = getenv('DB_HOST') ?: 'your_host';
$dbname = getenv('DB_NAME') ?: 'your_db';
$user = getenv('DB_USER') ?: 'your_user';
$password = getenv('DB_PASSWORD') ?: 'your_password';
$charset = 'utf8mb4'; // 文字セットを明示的に指定（SQLインジェクション対策の一部）

try {
    // PDOインスタンス作成時にDSNに文字セットを指定
    // エラーモードを例外発生に設定
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー時に例外をスロー
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // デフォルトのフェッチモードを連想配列に
        PDO::ATTR_EMULATE_PREPARES => false, // エミュレートされたプリペアドステートメントを無効化
        PDO::MYSQL_ATTR_FOUND_ROWS => true, // 更新された行数ではなく、マッチした行数を返す
        PDO::ATTR_PERSISTENT => false // 永続的接続を無効化（必要に応じて変更可）
    ];
    
    $pdo = new PDO($dsn, $user, $password, $options);
    
    // 検索するメールアドレスを適切に設定（ユーザー入力から取得する場合は適切なバリデーションが必要）
    $email = 'example@example.com';  // 実際には適切な入力値またはバリデーションされた値を使用
    
    // プリペアドステートメントを使用してSQLインジェクションを防止
    $stmt = $pdo->prepare('SELECT * FROM sims WHERE users_email = :email LIMIT 100');
    
    // パラメータをバインド（型を明示的に指定）
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    
    // クエリを実行
    $stmt->execute();
    
    // 結果を取得（安全のため結果セットのサイズを制限）
    $results = $stmt->fetchAll();
    
    // 結果を出力（実際のアプリでは適切なエスケープ処理を行う）
    if (count($results) > 0) {
        // 機密情報をログに出力しないよう注意
        echo "検索結果: " . count($results) . "件\n";
        
        // 機密データの出力前に適切にフィルタリングまたはエスケープ
        foreach ($results as $row) {
            // 実際のアプリケーションでは、htmlspecialchars等でエスケープ
            echo "ID: " . htmlspecialchars($row['id'] ?? 'N/A') . "\n";
            // メールアドレスなど機密情報は必要に応じて一部をマスク
        }
    } else {
        echo "該当するレコードが見つかりませんでした。\n";
    }
    
    // 接続を明示的に閉じる（ガベージコレクションでも自動的に閉じられるが、明示的な方が良い）
    $pdo = null;
    $stmt = null;
    
} catch (PDOException $e) {
    // エラーログに記録（本番環境では詳細なエラーメッセージを出力しない）
    error_log('データベースエラー: ' . $e->getMessage());
    
    // ユーザーに表示するエラーメッセージ（詳細なエラー情報は含めない）
    echo 'システムエラーが発生しました。管理者にお問い合わせください。';
    
    // デバッグ時のみ詳細エラーを表示（本番環境では削除または無効化）
    if (getenv('APP_DEBUG') === 'true') {
        echo '<pre>デバッグ情報: ' . $e->getMessage() . '</pre>';
    }
    
    exit; // エラー発生時は処理を終了
}
?>
