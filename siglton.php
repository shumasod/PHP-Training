<?php

class DatabaseConnection {
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct() {
        // 実際のデータベース接続処理
        //
        // 修正前は認証情報がリテラルで直書きされていた。
        //
        //     new PDO('mysql:host=localhost;dbname=myapp', 'username', 'password')
        //
        // 動かすには編集するしかなく、編集した瞬間に本物の認証情報が
        // ソースへ入り、そのままコミットされる形になっていた。
        // 環境変数から読めば、コードを触らずに動かせる。
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'myapp';

        $this->connection = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
            getenv('DB_USER') ?: 'app_user',
            getenv('DB_PASSWORD') ?: '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
        );
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // クローンを禁止
    private function __clone() {}

    // シリアル化からの復元を禁止
    // __wakeup() は PHP 8 以降 public 以外だと Warning になる。
    // private にしても unserialize() は呼び出しを試みるため、
    // 例外を投げて singleton の複製を確実に防ぐ。
    public function __wakeup()
    {
        throw new \LogicException('Cannot unserialize a singleton.');
    }
}

// 使用例
//
// このファイルを require した瞬間に DB へ接続してしまわないよう、
// CLI から直接実行したときだけ動かす。
// 修正前はファイルスコープに置かれていたので、
// クラスを使いたいだけの include でも接続が走っていた。
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $db1 = DatabaseConnection::getInstance();
    $db2 = DatabaseConnection::getInstance();

    var_dump($db1 === $db2); // 出力: bool(true)
}
