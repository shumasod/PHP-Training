<?php

declare(strict_types=1);

/**
 * 外部ファイル読み込み (include / require) の練習用。
 *
 * 以前はこのファイルの末尾に Laravel のゲーム用 API ルート定義が
 * 2 つ目の <?php タグ付きで連結されており、Parse error になっていた。
 * ルート定義は Game/routes/api.php へ分離済み。
 */

$commonVariable = '共通の変数です';

function commonTest(): void
{
    echo '外部ファイルの関数です';
}
