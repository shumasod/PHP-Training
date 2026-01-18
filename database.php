<?php

// -----------------------------------------------------------------------------
// 設定セクション / Configuration Section
// -----------------------------------------------------------------------------
// ここでデータベース接続情報やテーブルのスキーマを自由にカスタマイズします。
// Customize your database connection and table schema here.
// -----------------------------------------------------------------------------

// セキュリティ向上のため、環境変数から認証情報を読み込む
$databaseConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',         // データベースのホスト名 (e.g., 'localhost' or '127.0.0.1')
    'username' => getenv('DB_USER') ?: 'your_username', // データベースのユーザー名
    'password' => getenv('DB_PASSWORD') ?: '',          // データベースのパスワード (環境変数から取得)
    'dbname' => getenv('DB_NAME') ?: 'your_database',   // データベース名
];

$tableSchema = [
    // テーブルの物理名（データベース内で使用される名前）
    'tableName' => 'business_directory',

    // カラムの定義
    // 'db_column_name' => [
    //     'display' => '表示名',
    //     'type' => 'データ型(制約)',
    // ]
    'columns' => [
        'id' => [
            'display' => 'ID',
            'type' => 'INT AUTO_INCREMENT PRIMARY KEY',
        ],
        'store_name' => [
            'display' => '店舗・会社名',
            'type' => 'VARCHAR(255) NOT NULL',
        ],
        'category' => [
            'display' => 'カテゴリ',
            'type' => 'VARCHAR(100)',
        ],
        'address' => [
            'display' => '住所',
            'type' => 'VARCHAR(255)',
        ],
        'phone_number' => [
            'display' => '電話番号',
            'type' => 'VARCHAR(20)',
        ],
        'description' => [
            'display' => '説明・PR',
            'type' => 'TEXT',
        ],
        'website' => [
            'display' => 'ウェブサイト',
            'type' => 'VARCHAR(255)',
        ],
        'posted_date' => [
            'display' => '掲載日',
            'type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        ]
    ]
];

// -----------------------------------------------------------------------------
// PHPロジックセクション / PHP Logic Section
// -----------------------------------------------------------------------------
// 通常、このセクションを編集する必要はありません。
// You usually don't need to edit this section.
// -----------------------------------------------------------------------------

/**
 * データベースに接続し、接続オブジェクトを返します。
 * Connects to the database and returns the connection object.
 * @param array $config データベース設定
 * @return mysqli|null 接続オブジェクトまたはエラー時にnull
 */
function connectDatabase(array $config): ?mysqli
{
    try {
        // mysqliのレポートモードを設定して、エラーを例外としてスローするようにする
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (Exception $e) {
        // エラーメッセージをHTMLに表示するためにグローバル変数に格納
        $GLOBALS['errorMessage'] = $e->getMessage();
        return null;
    }
}

/**
 * スキーマ定義に基づいてテーブルを作成します。
 * Creates the table based on the schema definition if it doesn't exist.
 * @param mysqli $conn データベース接続オブジェクト
 * @param array $schema テーブルスキーマ定義
 * @return bool 成功した場合はtrue
 */
function createTableIfNotExists(mysqli $conn, array $schema): bool
{
    try {
        $tableName = $conn->real_escape_string($schema['tableName']);
        $columnsSql = [];
        foreach ($schema['columns'] as $colName => $colDetails) {
            $columnsSql[] = "`{$colName}` {$colDetails['type']}";
        }
        $columnsSqlString = implode(', ', $columnsSql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` ({$columnsSqlString}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sql);

        // テーブルが空の場合、サンプルデータを挿入
        $result = $conn->query("SELECT COUNT(*) as count FROM `{$tableName}`");
        $row = $result->fetch_assoc();
        if ($row['count'] == 0) {
            insertSampleData($conn, $schema);
        }
        return true;
    } catch (Exception $e) {
        $GLOBALS['errorMessage'] = "Table operation failed: " . $e->getMessage();
        return false;
    }
}

/**
 * スキーマ定義に基づいてサンプルデータを挿入します。
 * Inserts sample data based on the schema definition.
 * @param mysqli $conn データベース接続オブジェクト
 * @param array $schema テーブルスキーマ定義
 */
function insertSampleData(mysqli $conn, array $schema) {
    $tableName = $conn->real_escape_string($schema['tableName']);

    // 挿入するカラムのリストを作成 (AUTO_INCREMENTは除く)
    $columnNames = array_keys($schema['columns']);
    $insertColumns = array_filter($columnNames, function($colName) use ($schema) {
        return strpos($schema['columns'][$colName]['type'], 'AUTO_INCREMENT') === false;
    });
    $columnsSqlString = '`' . implode('`, `', $insertColumns) . '`';

    // サンプルデータ
    $samples = [
        [
            'store_name' => '特製ラーメン 麺屋とも',
            'category' => '飲食店',
            'address' => '大阪府大阪市北区梅田1-1-1',
            'phone_number' => '06-1234-5678',
            'description' => '濃厚豚骨スープが自慢のラーメン店です。ホール・キッチンスタッフ募集中！',
            'website' => 'https://example.com/menyado',
            'posted_date' => 'NOW()'
        ],
        [
            'store_name' => '株式会社クリエイティブデザイン',
            'category' => 'IT・Webサービス',
            'address' => '大阪府大阪市中央区難波2-2-2',
            'phone_number' => '06-8765-4321',
            'description' => '最新技術を使ったWebサイトを制作します。Webデザイナー・エンジニアを募集しています。',
            'website' => 'https://example.com/creative',
            'posted_date' => 'NOW()'
        ],
        [
            'store_name' => '旅する本屋',
            'category' => '小売',
            'address' => '大阪府大阪市天王寺区茶臼山町3-3-3',
            'phone_number' => '06-1122-3344',
            'description' => '世界中の珍しい本を取り揃えています。週末だけのアルバイト募集中。',
            'website' => 'https://example.com/bookstore',
            'posted_date' => 'NOW()'
        ]
    ];

    // プリペアドステートメントの準備
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $stmt = $conn->prepare("INSERT INTO `{$tableName}` ({$columnsSqlString}) VALUES ({$placeholders})");

    // 各データをバインドして実行
    foreach ($samples as $sample) {
        $values = [];
        $types = '';
        foreach($insertColumns as $col) {
            $values[] = $sample[$col];
            // データ型に応じてs, i, d, bなどを指定
            $types .= 's'; // 今回は全て文字列として扱う
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
    }
    $stmt->close();
}


/**
 * テーブルから全てのデータを取得します。
 * Fetches all data from the table.
 * @param mysqli $conn データベース接続オブジェクト
 * @param string $tableName テーブル名
 * @return array 取得したデータの配列
 */
function fetchData(mysqli $conn, string $tableName): array
{
    try {
        $tableName = $conn->real_escape_string($tableName);
        $sql = "SELECT * FROM `{$tableName}` ORDER BY `posted_date` DESC";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $GLOBALS['errorMessage'] = "Error fetching data: " . $e->getMessage();
        return [];
    }
}

// --- メイン処理 ---
$errorMessage = null;
$tableData = [];
$conn = connectDatabase($databaseConfig);

if ($conn) {
    if (createTableIfNotExists($conn, $tableSchema)) {
        $tableData = fetchData($conn, $tableSchema['tableName']);
    }
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP 電話帳アプリケーション</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f7f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            word-wrap: break-word;
        }
        thead th {
            background-color: #4a90e2;
            color: #fff;
            font-weight: 600;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tbody tr:hover {
            background-color: #eaf2fb;
        }
        .error-message {
            background-color: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        code {
            background-color: #eee;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: "SF Mono", "Consolas", "Menlo", monospace;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>事業所一覧</h1>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                <strong>エラー:</strong> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!$errorMessage && !empty($tableData)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($tableSchema['columns'] as $column): ?>
                            <th style="width: <?php echo ($column['display'] === '説明・PR') ? '30%' : 'auto'; ?>;"><?php echo htmlspecialchars($column['display']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $row): ?>
                        <tr>
                            <?php foreach ($tableSchema['columns'] as $colName => $colDetails): ?>
                                <td>
                                    <?php
                                        $cellValue = htmlspecialchars($row[$colName]);
                                        if ($colName === 'website' && !empty($row[$colName])) {
                                            echo '<a href="' . $cellValue . '" target="_blank" rel="noopener noreferrer">' . $cellValue . '</a>';
                                        } else {
                                            echo $cellValue;
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!$errorMessage): ?>
            <p>データが見つかりませんでした。データベースの接続情報が正しいか確認してください。</p>
        <?php endif; ?>
    </div>
</body>
</html>
