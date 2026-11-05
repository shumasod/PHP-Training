<?php

declare(strict_types=1);

/**
 * Laravel の old() を素の PHP で真似たもの。
 *
 * 直前の POST に同じ名前の値があればそれを返し、無ければ空文字を返す。
 * 検証エラーでフォームを再表示するときに、入力し直させないために使う。
 *
 * 修正点は 2 つ。
 *
 * 1. htmlspecialchars のフラグが ENT_QUOTES だけだった
 *
 *    フラグを明示すると既定値 (PHP 8.1 以降は
 *    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) を上書きするため、
 *    ENT_SUBSTITUTE が外れる。
 *    すると不正な UTF-8 を含む入力に対して空文字が返る。
 *
 *        $ php -r '$bad = "yama" . chr(0xC3) . chr(0x28) . "da";
 *                  var_dump(htmlspecialchars($bad, ENT_QUOTES));
 *                  var_dump(htmlspecialchars($bad, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));'
 *        string(0) ""
 *        string(10) "yama\xEF\xBF\xBD(da"
 *
 *    入力の一部が壊れていただけで値が丸ごと消え、
 *    利用者には「入力したのに空になった」としか見えない。
 *    文字コードも明示する。
 *
 * 2. ファイルスコープに実行コードが置かれていた
 *
 *    old() を使いたいだけで require すると、
 *    POST の場合はフォームの HTML まで出力されていた。
 *
 *        $ php -r '$_SERVER["REQUEST_METHOD"] = "POST";
 *                  $_POST = ["username" => "yamada", ...];
 *                  require "old_helper.php";'
 *        <form method="post">Username: <input ... value="yamada"> ...</form>
 *
 *    呼び出し側が自分の HTML を出す前にヘッダを送れなくなる
 *    (header() は出力より先に呼ぶ必要がある) うえ、
 *    意図しない <form> が画面に混ざる。
 *    使用例は下の if の中に閉じ込めた。
 */

/**
 * 直前の POST の値を HTML 属性値として安全な形で返す。
 *
 * 注意: 戻り値はエスケープ済みなので、HTML に出す用途にだけ使う。
 * 検証やデータベースへの保存には $_POST の生の値を使うこと
 * (エスケープ済みの値を保存すると &amp;quot; などがそのまま残る)。
 */
function old(string $inputName): string
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return '';
    }

    $value = $_POST[$inputName] ?? null;
    if (!is_string($value)) {
        // 配列やチェックボックス未選択の null はここでは扱わない。
        return '';
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ここから下は old() の使用例。
//
// このファイルを require しただけでは動かないよう、
// 直接実行されたときに限定している。
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $username = old('username');
    $email = old('email');

    // 入力検証やその他の処理はここで行う。
    // (old() の戻り値ではなく $_POST の生の値を使う)

    echo '<form method="post">';
    echo 'Username: <input type="text" name="username" value="' . $username . '"><br>';
    echo 'Email: <input type="email" name="email" value="' . $email . '"><br>';
    echo '<input type="submit" value="Submit">';
    echo '</form>';
}
