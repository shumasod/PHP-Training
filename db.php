<?php
function getPdoConnection(): PDO {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'your_database';
    $user = 'your_user';
    $password = 'your_password';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $user, $password, $options);
}
