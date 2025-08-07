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

// ==========================================
// 3. error_log() - サーバーログにメッセージを書き込む
// ==========================================
function debug_with_error_log() {
    // 配列やオブジェクトはJSON形式に変換
    $data = ['status' => 'error', 'code' => 500];
    error_log('APIエラー発生: ' . json_encode($data));
    
    // 変数の状態をログに記録
    $username = "user123";
    $is_logged_in = false;
    error_log("ログイン試行: ユーザー名={$username}, ログイン状態={$is_logged_in}");
    
    try {
        // 何らかの処理
        throw new Exception('テスト例外');
    } catch (Exception $e) {
        error_log('例外キャッチ: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }
}

// ==========================================
// 4. カスタムデバッグ関数の作成
// ==========================================
function debug($data, $die = false) {
    echo "<pre style='background:#f4f4f4;padding:10px;border:1px solid #ddd;border-radius:5px;'>";
    if (is_array($data) || is_object($data)) {
        print_r($data);
    } else {
        var_dump($data);
    }
    echo "</pre>";
    
    if ($die) {
        die('デバッグ終了'); // 処理を停止
    }
}

// 使用例
function use_custom_debug_function() {
    $user = new stdClass();
    $user->name = '鈴木一郎';
    $user->email = 'suzuki@example.com';
    
    debug($user);
    
    $complex_array = [
        'products' => [
            ['id' => 1, 'name' => '商品A', 'price' => 1000],
            ['id' => 2, 'name' => '商品B', 'price' => 2000],
        ]
    ];
    
    debug($complex_array, true); // ここで処理が停止する
    
    echo "この行は実行されません";
}

// ==========================================
// 5. デバッグバックトレース
// ==========================================
function debug_backtrace_example() {
    function level3() {
        echo "<h3>バックトレース情報:</h3>";
        echo "<pre>";
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        echo "</pre>";
        
        // より詳細な情報を取得して処理
        $trace = debug_backtrace();
        echo "<h3>カスタム整形したバックトレース:</h3>";
        echo "<div style='background:#f8f8f8;padding:10px;border:1px solid #eee;'>";
        foreach ($trace as $i => $call) {
            $file = isset($call['file']) ? $call['file'] : 'unknown';
            $line = isset($call['line']) ? $call['line'] : 'unknown';
            $function = isset($call['function']) ? $call['function'] : 'unknown';
            echo "<strong>#{$i}</strong> {$function}() called at [{$file}:{$line}]<br>";
        }
        echo "</div>";
    }
    
    function level2() {
        level3();
    }
    
    function level1() {
        level2();
    }
    
    level1();
}

// ==========================================
// 6. xdebug拡張モジュールの使用例
// ==========================================
function xdebug_example() {
    // 注意: xdebugがインストールされている必要があります
    
    // xdebugがインストールされているか確認
    if (!extension_loaded('xdebug')) {
        echo "xdebug拡張モジュールがインストールされていません。<br>";
        echo "インストール方法については公式サイトを参照してください: https://xdebug.org/docs/install";
        return;
    }
    
    // xdebugを使用した変数ダンプ
    $complex_data = [
        'user' => [
            'id' => 1001,
            'name' => '佐藤花子',
            'metadata' => [
                'last_login' => '2023-04-15',
                'preferences' => (object)['theme' => 'dark', 'notifications' => true]
            ]
        ]
    ];
    
    // xdebug用の出力関数を使用（インストール時に使用可能）
    // var_dump($complex_data); // xdebugが有効なら整形された出力になる
    
    // ブレークポイントの例（実際のコード内で使用）
    // xdebug_break(); // IDEのデバッガが接続されている場合に停止
}

// ==========================================
// 7. PHPファイル実行時間の計測 
// ==========================================
function measure_execution_time() {
    // 開始時間を記録
    $start_time = microtime(true);
    
    // 処理時間を計測したいコード
    for ($i = 0; $i < 1000000; $i++) {
        $dummy = $i * 2;
    }
    
    // 配列操作の例
    $big_array = range(1, 10000);
    $mapped = array_map(function($item) {
        return $item * 3;
    }, $big_array);
    
    // 終了時間を記録して差分を計算
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time);
    
    echo "<div style='background:#e8f8e8;padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "処理実行時間: " . number_format($execution_time, 6) . " 秒";
    echo "</div>";
    
    // 特定の処理ブロックだけを計測する例
    $start = microtime(true);
    
    // 計測対象の処理
    usort($big_array, function($a, $b) {
        return $b <=> $a; // 降順ソート
    });
    
    $time_taken = microtime(true) - $start;
    echo "配列ソート処理時間: " . number_format($time_taken, 6) . " 秒";
}

// ==========================================
// 8. メモリ使用量の計測とデバッグ
// ==========================================
function debug_memory_usage() {
    // 現在のメモリ使用量を表示
    echo "初期メモリ使用量: " . format_bytes(memory_get_usage()) . "<br>";
    
    // 大きな配列を作成
    $data = [];
    for ($i = 0; $i < 100000; $i++) {
        $data[] = "項目 " . $i . " の値: " . str_repeat('X', 10);
    }
    
    echo "配列作成後のメモリ使用量: " . format_bytes(memory_get_usage()) . "<br>";
    
    // 配列をクリア
    unset($data);
    
    echo "配列削除後のメモリ使用量: " . format_bytes(memory_get_usage()) . "<br>";
    echo "ピークメモリ使用量: " . format_bytes(memory_get_peak_usage()) . "<br>";
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// ==========================================
// 9. クラスとオブジェクト情報のデバッグ
// ==========================================
function debug_class_info() {
    class Product {
        private $id;
        protected $name;
        public $price;
        
        public function __construct($id, $name, $price) {
            $this->id = $id;
            $this->name = $name;
            $this->price = $price;
        }
        
        private function calculateTax() {
            return $this->price * 0.1;
        }
        
        public function getPriceWithTax() {
            return $this->price + $this->calculateTax();
        }
    }
    
    class DigitalProduct extends Product {
        public $downloadUrl;
        
        public function __construct($id, $name, $price, $url) {
            parent::__construct($id, $name, $price);
            $this->downloadUrl = $url;
        }
    }
    
    // オブジェクトを作成
    $product = new DigitalProduct(1, 'eBook', 1500, 'https://example.com/download/123');
    
    // クラス情報の表示
    echo "<h3>リフレクションAPIを使用したクラス情報:</h3>";
    
    $reflection = new ReflectionClass($product);
    
    echo "<pre>";
    echo "クラス名: " . $reflection->getName() . "\n";
    echo "親クラス: " . $reflection->getParentClass()->getName() . "\n\n";
    
    echo "プロパティ一覧:\n";
    foreach ($reflection->getProperties() as $property) {
        echo "  " . $property->getName() . " (" . 
             ($property->isPublic() ? 'public' : 
             ($property->isProtected() ? 'protected' : 'private')) . ")\n";
    }
    
    echo "\nメソッド一覧:\n";
    foreach ($reflection->getMethods() as $method) {
        echo "  " . $method->getName() . " (" . 
             ($method->isPublic() ? 'public' : 
             ($method->isProtected() ? 'protected' : 'private')) . ")\n";
    }
    echo "</pre>";
    
    // オブジェクトのプロパティ値を表示（アクセス可能なもののみ）
    echo "<h3>オブジェクトの状態:</h3>";
    echo "<pre>";
    var_dump($product);
    echo "</pre>";
}

// ==========================================
// 10. エラーハンドリングとHTTPリクエスト/レスポンスのデバッグ
// ==========================================
function debug_error_handling_and_http() {
    // カスタムエラーハンドラーを設定
    function custom_error_handler($errno, $errstr, $errfile, $errline) {
        echo "<div style='background:#ffebeb;border:1px solid #ffcaca;padding:10px;margin:5px 0;'>";
        echo "<strong>エラー({$errno}):</strong> {$errstr}<br>";
        echo "ファイル: {$errfile} 行: {$errline}";
        echo "</div>";
        
        // エラーログにも記録
        error_log("エラー({$errno}): {$errstr} in {$errfile} on line {$errline}");
        
        // trueを返すと標準のエラーハンドラーを無効化
        return true;
    }
    
    // エラーハンドラーを設定
    set_error_handler("custom_error_handler");
    
    // 意図的にエラーを発生させる
    echo $undefined_variable;
    
    // HTTP関連のデバッグ情報
    echo "<h3>HTTP & サーバー情報:</h3>";
    echo "<div style='background:#f0f8ff;padding:10px;border:1px solid #d0e0f0;'>";
    
    echo "<strong>リクエストメソッド:</strong> " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "<strong>リクエストURI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "<strong>ユーザーエージェント:</strong> " . $_SERVER['HTTP_USER_AGENT'] . "<br>";
    
    // GETパラメータの表示
    if (!empty($_GET)) {
        echo "<strong>GETパラメータ:</strong><br>";
        echo "<pre>";
        print_r($_GET);
        echo "</pre>";
    }
    
    // POSTパラメータの表示（安全のために実際のアプリでは注意が必要）
    if (!empty($_POST)) {
        echo "<strong>POSTパラメータ:</strong><br>";
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
    }
    
    // セッション情報（セッションが開始されている場合）
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<strong>セッション情報:</strong><br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
    }
    
    echo "</div>";
    
    // 元のエラーハンドラーに戻す
    restore_error_handler();
}

// 実行例
if (isset($_GET['test'])) {
    echo "<h2>var_dump()のテスト</h2>";
    debug_with_var_dump();
    
    echo "<h2>print_r()のテスト</h2>";
    debug_with_print_r();
    
    echo "<h2>error_log()のテスト</h2>";
    debug_with_error_log();
    
    echo "<h2>カスタムデバッグ関数のテスト</h2>";
    // use_custom_debug_function(); // 処理が停止するため、必要時にコメント解除
    
    echo "<h2>バックトレースのテスト</h2>";
    debug_backtrace_example();
    
    echo "<h2>実行時間計測のテスト</h2>";
    measure_execution_time();
    
    echo "<h2>メモリ使用量のテスト</h2>";
    debug_memory_usage();
    
    echo "<h2>クラス情報のテスト</h2>";
    debug_class_info();
}

// 各デバッグ機能のデモ実行（必要に応じてコメントアウト）
// debug_with_var_dump();
// debug_with_print_r();
// debug_with_error_log();
// use_custom_debug_function();
// debug_backtrace_example();
// xdebug_example();
// measure_execution_time();
// debug_memory_usage();
// debug_class_info();
// debug_error_handling_and_http();

// このファイルを実行する際は、上記の関数から使用したいものをコメント解除して実行してください
?>