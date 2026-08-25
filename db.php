<?php

declare(strict_types=1);

/**
 * PDO 接続と、接続先に応じた識別子のクォート。
 */

/**
 * 使用するドライバ。
 *
 * 修正前は pgsql 固定だったが、table.php はバッククォートで識別子を
 * 囲んでいた。バッククォートは MySQL の記法で、PostgreSQL は
 * 受け付けない（SQL 標準は二重引用符）。
 * つまり db.php と table.php の前提が食い違っており、
 * 設定どおりに動かすと必ず構文エラーになる状態だった。
 *
 * 環境変数で切り替えられるようにし、クォート方法も
 * ドライバに合わせて選ぶようにする。
 */
function dbDriver(): string
{
    $driver = strtolower(getenv('DB_DRIVER') ?: 'mysql');

    if (!in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
        throw new InvalidArgumentException("Unsupported DB_DRIVER: {$driver}");
    }

    return $driver;
}

function getPdoConnection(): PDO
{
    // 認証情報はソースに直書きせず、環境変数から読み込む
    $driver = dbDriver();
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $dbname = getenv('DB_NAME') ?: 'your_database';
    $user = getenv('DB_USER') ?: 'your_user';
    $password = getenv('DB_PASSWORD') ?: '';

    // ポートの既定値はドライバごとに違う
    $port = getenv('DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306');

    $dsn = match ($driver) {
        // MySQL は接続時に文字セットを指定しておく。
        // 指定しないとサーバ既定の文字セットに従うため、
        // 環境によって文字化けや照合順序の違いが出る。
        'mysql' => sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname),
        'pgsql' => sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname),
        'sqlite' => 'sqlite:' . $dbname,
    };

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // 真のプリペアドステートメントを使う
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $password, $options);
    } catch (PDOException $e) {
        // 例外メッセージには DSN（ホスト名・DB 名）とユーザー名、
        // 認証の失敗理由が含まれる。画面には出さずログへ送る。
        //
        // 修正前は例外を捕まえておらず、display_errors が有効な環境では
        // これらがそのままブラウザへ出ていた。
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('データベースに接続できませんでした。');
    }
}

/**
 * 識別子（テーブル名・カラム名）をクォートする。
 *
 * 識別子はプレースホルダにできないため、使ってよい文字を限定したうえで
 * ドライバに合った引用符で囲むしかない。
 *
 * @throws InvalidArgumentException 使用できない文字が含まれる場合
 */
function quoteIdent(string $identifier): string
{
    if (!preg_match('/\A[A-Za-z_][A-Za-z0-9_]{0,63}\z/', $identifier)) {
        throw new InvalidArgumentException("Invalid SQL identifier: {$identifier}");
    }

    // MySQL      -> `name`
    // PostgreSQL -> "name"   (SQL 標準)
    // SQLite     -> どちらも受け付けるが標準に合わせる
    return dbDriver() === 'mysql'
        ? '`' . $identifier . '`'
        : '"' . $identifier . '"';
}
