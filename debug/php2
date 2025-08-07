<?php
// ==========================================
// デバッグ関数のサンプル集
// ファイル名: debug_functions.php
// ==========================================

// 1. var_dump() - 変数の詳細情報を出力
function debug_with_var_dump() {
    $user = [
        'name' => '山田太郎',
        'age' => 30,
        'roles' => ['admin', 'editor']
    ];
    
    echo "<pre>";
    var_dump($user); // 型情報と値を出力
    echo "</pre>";
    
    // 複数の変数を一度に確認することも可能
    $a = 10;
    $b = "こんにちは";
    $c = null;
    
    echo "<pre>";
    var_dump($a, $b, $c);
    echo "</pre>";
}

// 2. print_r() - 見やすい形式で配列やオブジェクトを出力
function debug_with_print_r() {
    $user = [
        'name' => '山田太郎',
        'age' => 30,
        'roles' => ['admin', 'editor']
    ];
    
    echo "<pre>";
    print_r($user); // var_dump()より読みやすいが型情報は含まれない
    echo "</pre>";
    
    // 出力ではなく結果を変数に格納することも可能
    $output = print_r($user, true);
    $log_message = date('Y-m-d H:i:s') . ": " . $output;
    // file_put_contents('debug.log', $log_message, FILE_APPEND);
}

// 3. error_log() - ログファイルに出力
function debug_with_error_log() {
    $debug_info = "ユーザーID: 123, 処理時間: " . date('Y-m-d H:i:s');
    
    // デフォルトのログファイルに出力
    error_log($debug_info);
    
    // カスタムファイルに出力
    error_log($debug_info, 3, 'debug.log');
}

// 4. バックトレース - 呼び出し元を追跡
function show_backtrace() {
    echo "<pre>";
    debug_print_backtrace();
    echo "</pre>";
}

function call_function_a() {
    call_function_b();
}

function call_function_b() {
    show_backtrace(); // どこから呼ばれたかを表示
}

// 5. 条件付きデバッグ出力
function conditional_debug($message, $level = 'INFO') {
    $debug_enabled = true; // 本番環境では false に設定
    
    if ($debug_enabled) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] [{$level}] {$message}\n";
    }
}

// 実行例
if (isset($_GET['test'])) {
    echo "<h2>var_dump()のテスト</h2>";
    debug_with_var_dump();
    
    echo "<h2>print_r()のテスト</h2>";
    debug_with_print_r();
    
    echo "<h2>バックトレースのテスト</h2>";
    call_function_a();
    
    echo "<h2>条件付きデバッグのテスト</h2>";
    conditional_debug("処理開始");
    conditional_debug("エラーが発生しました", "ERROR");
}
?>