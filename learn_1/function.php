<?php

declare(strict_types=1);

/**
 * プリペアドステートメントの練習。
 *
 * 修正前は接続情報がリテラルで直書きされていた。
 *
 *     new mysqli('ホスト名', 'ユーザー名', 'パスワード', 'データベース名')
 *
 * 動かすには編集するしかなく、編集した瞬間に本物の認証情報が
 * ソースへ入り、そのままコミットされる形になっていた。
 * 環境変数から読むようにすれば、コードを触らずに動かせる。
 */

/**
 * DB へ接続する。
 *
 * 接続を関数ごとに張り直すと、呼ぶたびにハンドシェイクが発生する。
 * 1 リクエスト内では使い回す。
 */
function memo_db_connection(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    // 例外を投げるモードにする。
    // これを指定しないと、接続や実行の失敗を毎回自前で判定する必要があり、
    // 判定を 1 箇所書き忘れるだけで「失敗したのに処理が続く」状態になる。
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $connection = new mysqli(
            getenv('DB_HOST') ?: '127.0.0.1',
            getenv('DB_USER') ?: 'php_user',
            getenv('DB_PASSWORD') ?: '',
            getenv('DB_NAME') ?: 'udemy_php',
            (int) (getenv('DB_PORT') ?: 3306),
        );
        $connection->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        // 例外メッセージには接続先ホスト・ユーザー名・認証の失敗理由が
        // 含まれる。修正前は connect_error をそのまま画面に出していた。
        // エスケープしてあっても内容自体が内部情報なので、ログへ送る。
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('データベースに接続できませんでした。');
    }

    return $connection;
}

/**
 * memo テーブルから、指定した種別の名前を取得する。
 *
 * @return list<string>
 */
function memo_table_value(int $kind): array
{
    $dbcon = memo_db_connection();

    // プレースホルダを使う。$kind を文字列連結すると SQL インジェクションになる。
    $stmt = $dbcon->prepare('SELECT name FROM memo WHERE kind = ?');
    $stmt->bind_param('i', $kind);
    $stmt->execute();

    $result = $stmt->get_result();

    // 修正前は foreach ($result as $col) と結果セットを直接回していた。
    // 動きはするが、取得件数が分からず、途中で return すると
    // 結果セットが開いたままになる。fetch_all で配列にして扱う。
    $names = array_column($result->fetch_all(MYSQLI_ASSOC), 'name');

    $stmt->close();

    return $names;
}

/**
 * 取得した名前を HTML として出力する。
 *
 * 修正前は memo_table_value() が取得と出力を兼ねていた。
 * 分けておくと、値をそのまま使いたい場面（API 応答や集計）で
 * 出力に引きずられずに済む。
 */
function memo_table_render(int $kind): void
{
    foreach (memo_table_value($kind) as $name) {
        // DB の値も信用しない。書き込み経路のどこかに漏れがあれば
        // ここが最後の防波堤になる。
        echo htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>';
    }
}
