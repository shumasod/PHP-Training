<?php

const DB_HOST = 'mysql:host=127.0.0.1;dbname=udemy_php;charset=utf8mb4';
const DB_USER = 'php_user';
const DB_PASSWORD = 'password123';

// 例外処理 Exception
try {
    $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 連想配列
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // 例外
        PDO::ATTR_EMULATE_PREPARES => false,             // SQLインジェクション対策
    ]);
    // 以下は接続成功時の処理なので、適切な処理を追加してください。
    echo '接続成功';
} catch (PDOException $e) {
    echo '接続失敗: ' . $e->getMessage() . "\n";
    exit();
}
