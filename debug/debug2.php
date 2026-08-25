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
    // 相対パスだと実行ディレクトリ次第で公開ディレクトリに
    // debug.log が作られ、ブラウザからダウンロードできてしまう。
    error_log($debug_info, 3, sys_get_temp_dir() . '/debug.log');
}

// 4. バックトレース - 呼び出し元を追跡
function show_backtrace() {
    echo "<pre>";
    // 引数には渡された値（パスワードやトークンを含みうる）が入るため、
    // DEBUG_BACKTRACE_IGNORE_ARGS で除外する。
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
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
    // 本番で true のまま残らないよう、既定は無効にして環境変数で開ける。
    // 修正前は true が直書きされており、コメントで「本番では false に」と
    // 書いてあっても実際に切り替える仕組みが無かった。
    $debug_enabled = getenv('APP_DEBUG') === '1';
    
    if ($debug_enabled) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] [{$level}] {$message}\n";
    }
}

// 実行例
//
// 修正前は isset($_GET['test']) だけで動いていた。
// つまりこのファイルが公開ディレクトリに置かれていれば、
// 誰でも ?test=1 を付けるだけでデバッグ出力を引き出せる。
// バックトレースには絶対パスが、$_SESSION にはログイン状態や
// CSRF トークンが含まれる。
//
// デモは CLI から直接実行したときだけ動かす。
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
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