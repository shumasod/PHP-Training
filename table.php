<?php

declare(strict_types=1);

// 相対パスだと、どのディレクトリから実行したかで読み込み先が変わる。
require_once __DIR__ . '/db.php';
$config = require __DIR__ . '/config.php';

$pdo = getPdoConnection();

// 構成情報の取得
$table = $config['table'];
$columns = $config['columns'];
$orderBy = $config['order_by'] ?? '';

// 識別子のクォート。
//
// 修正前はここで正規表現の検証だけを行い、SQL は
//
//     "SELECT ... FROM `$table`"
//
// とバッククォートで囲んでいた。バッククォートは MySQL の記法で、
// db.php が接続していた PostgreSQL は受け付けない（SQL 標準は二重引用符）。
// 設定どおりに動かすと必ず構文エラーになる状態だった。
//
// quoteIdent() が検証とドライバに応じたクォートをまとめて行う。
$quotedTable = quoteIdent($table);

$columnKeys = array_keys($columns);
$quotedColumns = array_map('quoteIdent', $columnKeys);

// ORDER BY 句のバリデーション。
//
// カラム名と方向を分けて検証する。カラム名は quoteIdent() を通し、
// 方向は ASC / DESC のどちらかに限定する。
$orderByClause = '';
if ($orderBy !== '') {
    if (!preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)(?:\s+(ASC|DESC))?\z/i', $orderBy, $m)) {
        throw new InvalidArgumentException('Invalid order by clause');
    }

    $orderByClause = ' ORDER BY ' . quoteIdent($m[1]);
    if (isset($m[2])) {
        $orderByClause .= ' ' . strtoupper($m[2]);
    }
}

// SQL 生成（検証・クォート済みの識別子のみを使用）
$sql = 'SELECT ' . implode(', ', $quotedColumns) . ' FROM ' . $quotedTable . $orderByClause;

try {
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    // 例外メッセージにはクエリ本文が含まれる。ログにのみ残す。
    error_log('Query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('データを取得できませんでした。');
}
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
                    <td><?= htmlspecialchars((string) ($row[$key] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
