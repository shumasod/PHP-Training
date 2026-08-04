<?php

declare(strict_types=1);

// セッション破棄のサンプル。
// ログアウト処理はここと同じ 3 手順を必ずセットで行う。

// クッキー削除の Set-Cookie ヘッダを送る必要があるため、
// HTML を出力する前に処理を済ませる。
session_start();

// 1. サーバ側のセッション変数を空にする
$_SESSION = [];

// 2. セッションクッキーを削除する
//
//    修正前の問題:
//      - クッキー名を 'PHPSESSID' と直書きしていた。session.name は
//        php.ini で変更できるため、名前が違うと削除されない。
//        session_name() で実際の名前を取る。
//      - path しか指定していなかった。削除用の Set-Cookie は
//        発行時と属性 (domain / secure / httponly / samesite) が
//        一致しないとブラウザに無視され、クッキーが残る。
//        session_get_cookie_params() から実際の設定を引く。
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 1800,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}

// 3. サーバ側のセッションファイルを破棄する
session_destroy();

?>

<html>
    <head><meta charset="UTF-8"></head>
    <body>

    <?php

    echo 'セッション破棄しました';

    echo 'セッション';
    echo '<pre>';
    var_dump($_SESSION);
    echo '</pre>';

    // 注意: $_COOKIE は「このリクエストで受け取った値」なので、
    // setcookie() を呼んでもこの場では変わらない。
    // 削除されたことは次のリクエストで確認する。
    echo 'クッキー';
    echo '<pre>';
    var_dump($_COOKIE);
    echo '</pre>';

    ?>

    </body></html>
