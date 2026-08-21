<?php

declare(strict_types=1);

/**
 * ファイル書き込みの練習（fopen / fwrite / fclose）。
 */

// __DIR__ を基準にする。相対パスだと実行ディレクトリ次第で書き先が変わる。
$contactFile = __DIR__ . '/.contact.dat';

// 追記モードで開く。
//
// 修正前は 'a+' を使っていたが、このコードは読み取りをしないので
// 書き込み専用の 'a' で足りる。必要以上の権限で開かない。
$handle = fopen($contactFile, 'a');

// fopen() は失敗すると false を返す。
// 修正前はこの確認が無く、権限不足やディスク不足のときに
// fwrite(false, ...) で TypeError になっていた。
if ($handle === false) {
    exit('ファイルを開けませんでした。');
}

try {
    // 複数のプロセスが同時に追記すると行が混ざることがあるため、
    // 排他ロックを取る。
    if (!flock($handle, LOCK_EX)) {
        exit('ファイルをロックできませんでした。');
    }

    $addText = '1行追記' . "\n";

    // fwrite() の戻り値も確認する。
    // 書き込めなかった場合に黙って成功したことにしない。
    if (fwrite($handle, $addText) === false) {
        exit('書き込みに失敗しました。');
    }

    fflush($handle);
} finally {
    flock($handle, LOCK_UN);
    fclose($handle);
}

echo '追記しました。';
$contactFile = '.contact.dat';

$contents = fopen($contactFile, 'a+');

$addText= '1行追記'. "\n";

fwrite($contents, $addText);

fclose($contents);
