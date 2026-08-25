<?php

declare(strict_types=1);

/**
 * ファイル読み込みの練習（file_get_contents / file）。
 *
 * .contact.dat は「お問い合わせフォームから書き込まれた CSV」を想定した
 * データファイル。つまり中身は利用者が入力した値であり、
 * 信用できない文字列として扱う必要がある。
 */

// __DIR__ を基準にする。
// 相対パスのままだと、どのディレクトリから実行したかで
// 読みに行く先が変わってしまう。
$contactFile = __DIR__ . '/.contact.dat';

/**
 * HTML エスケープ。
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ファイルの存在確認。
//
// 修正前は存在チェックをせず file_get_contents() / file() を呼んでいた。
// ファイルが無いと Warning が出たうえで false が返り、
// その false を foreach に渡してさらに TypeError になる。
// （このリポジトリには .contact.dat が含まれていないので、
//   現状は必ずこの経路に入る）
if (!is_readable($contactFile)) {
    exit('データファイルがありません: ' . h(basename($contactFile)));
}

// 行ごとに読み込む。
// FILE_IGNORE_NEW_LINES で行末の改行を落とし、
// FILE_SKIP_EMPTY_LINES で空行を飛ばす。
$allData = file($contactFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($allData === false) {
    exit('データファイルを読み込めませんでした。');
}

foreach ($allData as $lineNo => $lineData) {
    $lines = explode(',', $lineData);

    // 列数の確認。
    //
    // 修正前は $lines[0] / $lines[1] / $lines[2] を無条件で参照していた。
    // カンマが 2 つ未満の行があると
    //   Warning: Undefined array key 1
    // になる。CSV の項目内にカンマが入るだけで簡単に起きる。
    if (count($lines) < 3) {
        echo h(sprintf('%d 行目の形式が不正です', $lineNo + 1)) . '<br>';
        continue;
    }

    // 出力は必ずエスケープする。
    //
    // 修正前は echo $lines[0] . '<br>'; と素通しだった。
    // このファイルの中身はフォームから書き込まれた値なので、
    // <script> を投稿されればそのまま実行される（蓄積型 XSS）。
    echo h($lines[0]) . '<br>';
    echo h($lines[1]) . '<br>';
    echo h($lines[2]) . '<br>';
}
