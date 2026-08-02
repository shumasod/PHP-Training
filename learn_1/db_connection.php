<?php

declare(strict_types=1);

/**
 * PDO 接続サンプル。
 *
 * 認証情報はソースに直書きせず環境変数から読み込む。
 * getenv() は実行時に評価されるため const では宣言できない
 * (const は定数式しか受け付けず Fatal error になる)。
 */

$dbHost     = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort     = getenv('DB_PORT') ?: '3306';
$dbName     = getenv('DB_NAME') ?: 'udemy_php';
$dbUser     = getenv('DB_USER') ?: 'php_user';
$dbPassword = getenv('DB_PASSWORD') ?: '';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 連想配列で取得
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 例外を送出
        PDO::ATTR_EMULATE_PREPARES   => false, // 真のプリペアドステートメントを使用
    ]);
} catch (PDOException $e) {
    // 接続文字列や認証情報が漏れるため、例外メッセージは画面に出さずログへ送る
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('データベースに接続できませんでした。');
}
