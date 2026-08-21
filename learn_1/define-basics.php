<?php

declare(strict_types=1);

/**
 * 定数の定義。
 *
 * 元は拡張子の無い「基本」というファイルに 2 行のメモとして
 * 置かれており、全角スペースが混ざっていて PHP としては動かなかった。
 */

// define() は実行時に評価される。条件分岐の中でも定義できる。
define('APP_NAME', 'PHP-Training');

// const は「コンパイル時」に決まるため、トップレベルか
// クラス内でしか書けず、値も定数式に限られる。
// getenv() のような関数呼び出しは書けない（Fatal error になる）。
const APP_VERSION = '1.0.0';

echo APP_NAME . ' ' . APP_VERSION . PHP_EOL;
