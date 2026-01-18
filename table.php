<?php
require_once 'db.php';
$config = require 'config.php';

$pdo = getPdoConnection();

// 構成情報の取得
$table = $config['table'];
$columns = $config['columns'];
$orderBy = $config['order_by'] ?? '';

// セキュリティ: テーブル名とカラム名のバリデーション
// SQLインジェクション対策として、英数字とアンダースコアのみを許可
if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
    throw new InvalidArgumentException('Invalid table name');
}

$columnKeys = array_keys($columns);
foreach ($columnKeys as $col) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
        throw new InvalidArgumentException('Invalid column name');
    }
}

// ORDER BY句のバリデーション
if ($orderBy && !preg_match('/^[a-zA-Z0-9_]+(\s+(ASC|DESC))?$/i', $orderBy)) {
    throw new InvalidArgumentException('Invalid order by clause');
}

// SQL動的生成（バリデーション済みの値を使用）
$sql = "SELECT " . implode(', ', $columnKeys) . " FROM `$table`";
if ($orderBy) {
    $sql .= " ORDER BY $orderBy";
}

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($table) ?> 一覧</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>

<h2><?= htmlspecialchars($table) ?> 一覧</h2>

<table>
    <thead>
        <tr>
            <?php foreach ($columns as $key => $label): ?>
                <th><?= htmlspecialchars($label) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <?php foreach ($columns as $key => $_): ?>
                    <td><?= htmlspecialchars($row[$key]) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
