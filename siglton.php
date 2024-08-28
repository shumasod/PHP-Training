class DatabaseConnection {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // 実際のデータベース接続処理
        $this->connection = new PDO('mysql:host=localhost;dbname=myapp', 'username', 'password');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // クローンを禁止
    private function __clone() {}

    // シリアル化を禁止
    private function __wakeup() {}
}

// 使用例
$db1 = DatabaseConnection::getInstance();
$db2 = DatabaseConnection::getInstance();

var_dump($db1 === $db2); // 出力: bool(true)