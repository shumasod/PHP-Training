<?php
function getPdoConnection(): PDO {
    // セキュリティ向上のため、環境変数から認証情報を読み込む
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'your_database';
    $user = getenv('DB_USER') ?: 'your_user';
    $password = getenv('DB_PASSWORD') ?: '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $user, $password, $options);
}
