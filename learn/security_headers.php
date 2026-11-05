<?php

declare(strict_types=1);

/**
 * セキュリティ関連の HTTP レスポンスヘッダをまとめて送る。
 *
 * learn_2/input.php と learn_2/input2.php は
 *
 *     header('X-FRAME-OPTIONS:DENY');
 *
 * の 1 行だけを送っていた。クリックジャッキング対策にはなるが、
 * それ以外のヘッダは何も付いていなかった。
 *
 *     $ grep -rn "header(" --include=*.php learn_2/ learn/ | grep -iv location
 *     learn_2/input.php:6:header('X-FRAME-OPTIONS:DENY');
 *     learn_2/input2.php:16:header('X-FRAME-OPTIONS:DENY');
 *
 * 2 ファイルで同じ内容を二重管理するのを避けるため、
 * ここに切り出して両方から呼ぶ。
 *
 * ヘッダは出力より先に送らなければならないので、
 * この関数は HTML を 1 バイトも出す前に呼ぶこと。
 */

/**
 * CSP で許可する外部ホスト。
 *
 * learn_2 の 2 ファイルが読み込んでいる CDN をそのまま列挙している。
 * 読み込み先を変えたらここも直す。
 *
 * @var list<string>
 */
const LEARN2_CDN_HOSTS = [
    'https://code.jquery.com',
    'https://cdn.jsdelivr.net',
    'https://stackpath.bootstrapcdn.com',
];

function sendSecurityHeaders(): void
{
    // すでに出力が始まっていたら何もしない。
    // headers_sent() を見ずに header() を呼ぶと警告が出るだけで
    // ヘッダは付かないため、黙って諦めるより条件を明示しておく。
    if (headers_sent()) {
        return;
    }

    // 文字コードをヘッダで明示する。
    //
    // 念のための一行で、これ自体は欠落していた穴ではない。
    // PHP は php.ini の default_charset (既定で UTF-8) に従って
    // Content-Type に charset を自動で付けるため、
    // 修正前のレスポンスでもすでに
    //
    //     Content-type: text/html; charset=UTF-8
    //
    // は送られていた。
    //
    // それでも書いておくのは、default_charset が空に設定された環境では
    // charset が消え、ブラウザが中身から文字コードを推測する余地が
    // 生まれるため。設定に依存せずこのページ自身で宣言しておく。
    header('Content-Type: text/html; charset=UTF-8');

    // MIME タイプの推測を止める。
    //
    // 無いと、Content-Type が text/plain のレスポンスでも
    // 中身が HTML っぽければブラウザが HTML として解釈しうる。
    header('X-Content-Type-Options: nosniff');

    // リファラを送らない。
    //
    // このフォームは GET のクエリを使わないが、
    // 入力内容が URL に出る作りに変えられたときに
    // 外部サイトへ漏れるのを防ぐ既定にしておく。
    header('Referrer-Policy: no-referrer');

    // クリックジャッキング対策。
    //
    // 修正前は 'X-FRAME-OPTIONS:DENY' という書き方だった。
    // コロンの後ろに空白が無い形も HTTP の文法上は許されるので
    // 壊れてはいなかったが、通例に合わせて空白を入れ、
    // ヘッダ名も正式な綴りにした。
    //
    // 下の CSP の frame-ancestors と役割が重なる。
    // frame-ancestors を解釈しない古いブラウザのために両方送る。
    header('X-Frame-Options: DENY');

    // Content-Security-Policy
    //
    // learn_2 の 2 ファイルを確認した結果、
    //
    //     $ grep -n "<script\|style=\|onclick\|<style" learn_2/input.php learn_2/input2.php
    //
    // インラインの <script>、style 属性、onclick はいずれも 0 件で、
    // 読み込んでいるのは CDN 上の 3 ファイルだけだった。
    // そのため 'unsafe-inline' を付けずに済み、
    // CSP が XSS 対策として実際に機能する。
    //
    // default-src 'none' から始めて、必要なものだけを足している。
    // 新しく何かを読み込むようにしたらここで弾かれるので、
    // そのときに意図して許可を足すことになる。
    $csp = implode('; ', [
        "default-src 'none'",
        'script-src ' . implode(' ', LEARN2_CDN_HOSTS),
        'style-src ' . implode(' ', LEARN2_CDN_HOSTS),

        // 画像は自分のオリジンと data: だけ。
        "img-src 'self' data:",

        // フォームの送信先を自分のオリジンに限定する。
        // XSS で <form> を差し込まれても外部へ送らせない。
        "form-action 'self'",

        // <base> による相対 URL の付け替えを禁止する。
        "base-uri 'none'",

        // 自分を iframe に入れさせない (X-Frame-Options と同じ役割)。
        "frame-ancestors 'none'",
    ]);
    header('Content-Security-Policy: ' . $csp);

    // Strict-Transport-Security は付けていない。
    //
    // HSTS は HTTPS で配信されているときだけ意味を持ち、
    // 平文の HTTP で送られた場合ブラウザは無視する。
    // この教材はローカルの http:// で動かす前提なので、
    // 効かないヘッダを足して「付けた」ことにはしない。
    //
    // 実際に HTTPS で公開するなら、アプリ側ではなく
    // Web サーバ側で設定するのが一般的:
    //
    //     Strict-Transport-Security: max-age=31536000; includeSubDomains
    //
    // max-age を長くすると取り消しが効かなくなるため、
    // 短い値から始めて確認してから延ばすこと。
}
