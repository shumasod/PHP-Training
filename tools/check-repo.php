<?php

declare(strict_types=1);

/**
 * リポジトリ健全性チェック。
 *
 * このリポジトリで実際に繰り返し発生した壊れ方だけを検査する。
 * 外部依存は無く、PHP さえあれば動く。
 *
 *   php tools/check-repo.php
 *
 * 検査項目:
 *   1. 構文エラー                    php -l
 *   2. <?php 開始タグの欠落          Web 経由でソースが平文で返る
 *   3. 拡張子の無い PHP ファイル      PHP として実行されず lint も素通り
 *   4. クラス名の重複                同時に読み込むと Fatal error
 *
 * 終了コード: 問題が 1 件でもあれば 1、無ければ 0。
 */

const REPO_ROOT = __DIR__ . '/../';

/** 走査から除外するディレクトリ */
const EXCLUDED_DIRS = ['.git', 'vendor', 'node_modules'];

/*
 * クラス名の重複について
 *
 * このリポジトリは learn_1 〜 learn_8 のように、章ごとに独立した
 * スナップショットを並べた構成になっている。learn_5/Kernel.php と
 * learn_8/Kernel.php が同じ App\Http\Kernel を宣言しているのは
 * 意図した重複であり、同時に読み込まれることはない。
 *
 * 一方で、同じディレクトリの中に同名クラスが 2 つあるのは事故で、
 * 実際に UserController が 3 箇所、StoreRequest が 2 箇所で
 * 宣言されている状態になっていた。
 *
 * そこで「トップレベルのディレクトリ」を独立した単位とみなし、
 * 同じ単位の中で重複したものだけを問題として報告する。
 * リポジトリ直下のファイルも 1 つの単位として扱う。
 */

/** @var list<string> */
$problems = [];

/**
 * @return list<string> リポジトリルートからの相対パス
 */
function listFiles(): array
{
    $root = realpath(REPO_ROOT);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file): bool {
                return !($file->isDir() && in_array($file->getFilename(), EXCLUDED_DIRS, true));
            }
        )
    );

    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }
    sort($files);

    return $files;
}

function isPhpFile(string $path): bool
{
    return str_ends_with($path, '.php') && !str_ends_with($path, '.blade.php');
}

/**
 * HTML / Blade テンプレートかどうか。
 * 最初の非空行が '<' か '@' で始まるものはテンプレートとみなす。
 * (JavaScript の function / class を PHP と誤検出しないため)
 */
function looksLikeTemplate(string $absolute): bool
{
    $handle = fopen($absolute, 'r');
    if ($handle === false) {
        return false;
    }

    try {
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            return $trimmed[0] === '<' || $trimmed[0] === '@';
        }
    } finally {
        fclose($handle);
    }

    return false;
}

// ---------------------------------------------------------------------------
// 1. 構文エラー
// ---------------------------------------------------------------------------
function checkSyntax(array $files, array &$problems): int
{
    $checked = 0;

    foreach ($files as $path) {
        if (!isPhpFile($path)) {
            continue;
        }
        $checked++;

        $absolute = REPO_ROOT . $path;
        exec(sprintf('php -l %s 2>&1', escapeshellarg($absolute)), $output, $status);
        if ($status !== 0) {
            $problems[] = sprintf('[syntax] %s: %s', $path, trim($output[0] ?? 'parse error'));
        }
        $output = [];
    }

    return $checked;
}

// ---------------------------------------------------------------------------
// 2. <?php 開始タグの欠落
// ---------------------------------------------------------------------------
function checkOpeningTags(array $files, array &$problems): int
{
    $checked = 0;

    foreach ($files as $path) {
        if (!isPhpFile($path)) {
            continue;
        }

        $absolute = REPO_ROOT . $path;
        $contents = (string) file_get_contents($absolute);

        if (str_contains($contents, '<?php') || str_contains($contents, '<?=')) {
            continue;
        }
        if (looksLikeTemplate($absolute)) {
            continue;
        }

        $checked++;

        // PHP のコードでしか出てこない書き出しがあるか
        if (preg_match('/^\s*(namespace|use|declare|abstract|final|class|interface|trait|enum|function|public|private|protected)\s/m', $contents)) {
            $problems[] = sprintf(
                '[open-tag] %s: <?php が無い。Web 経由でソースが平文で返る。',
                $path
            );
        }
    }

    return $checked;
}

// ---------------------------------------------------------------------------
// 3. 拡張子の無い PHP ファイル
// ---------------------------------------------------------------------------
function checkExtensions(array $files, array &$problems): void
{
    foreach ($files as $path) {
        $basename = basename($path);
        if (str_contains($basename, '.')) {
            continue;
        }
        // LICENSE などの慣習的な拡張子なしファイルは対象外
        if (in_array($basename, ['LICENSE', 'NOTICE', 'Dockerfile', 'Makefile', 'CODEOWNERS'], true)) {
            continue;
        }

        $contents = (string) file_get_contents(REPO_ROOT . $path);
        if (str_contains($contents, '<?php')) {
            $problems[] = sprintf(
                '[extension] %s: PHP コードだが拡張子が無い。PHP として実行されず、lint も素通りする。',
                $path
            );
        }
    }
}

// ---------------------------------------------------------------------------
// 4. クラス名の重複
// ---------------------------------------------------------------------------
/**
 * 1 つのトップレベルディレクトリの中に、さらに独立したスナップショットが
 * 入っている場所。ここに挙げたパスはそれぞれ別の単位として扱う。
 *
 * @var list<string>
 */
const SNAPSHOT_ROOTS = [
    'Laravel/lodge-app/step1',
    'Laravel/lodge-app/step2',
    'Laravel/lodge-app/step3',
    'Laravel/sql-corrector',
    'codeigniter-typeerror/before',
    'codeigniter-typeerror/after',
];

/**
 * パスが属する「単位」を返す。
 * SNAPSHOT_ROOTS に該当すればそれ、なければトップレベルのディレクトリ名、
 * リポジトリ直下のファイルなら '(root)'。
 */
function collisionGroup(string $path): string
{
    foreach (SNAPSHOT_ROOTS as $root) {
        if (str_starts_with($path, $root . '/')) {
            return $root;
        }
    }

    $slash = strpos($path, '/');

    return $slash === false ? '(root)' : substr($path, 0, $slash);
}

function checkDuplicateClasses(array $files, array &$problems): int
{
    /** @var array<string, list<string>> $declarations 完全修飾名 => 宣言しているファイル */
    $declarations = [];

    foreach ($files as $path) {
        if (!isPhpFile($path)) {
            continue;
        }

        $contents = (string) file_get_contents(REPO_ROOT . $path);

        $namespace = '';
        if (preg_match('/^\s*namespace\s+([^;{\s]+)/m', $contents, $m) === 1) {
            $namespace = $m[1] . '\\';
        }

        if (preg_match_all('/^\s*(?:final\s+|abstract\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $matches) === 0) {
            continue;
        }

        foreach ($matches[1] as $name) {
            $declarations[$namespace . $name][] = $path;
        }
    }

    $duplicates = 0;
    foreach ($declarations as $fqcn => $paths) {
        if (count($paths) < 2) {
            continue;
        }

        // 同じ単位の中で重複しているものだけを報告する
        $byGroup = [];
        foreach ($paths as $path) {
            $byGroup[collisionGroup($path)][] = $path;
        }

        foreach ($byGroup as $group => $groupPaths) {
            if (count($groupPaths) < 2) {
                continue;
            }

            $duplicates++;
            $problems[] = sprintf(
                '[duplicate-class] %s が %s 内の %d 箇所で宣言されている: %s',
                $fqcn,
                $group,
                count($groupPaths),
                implode(', ', $groupPaths)
            );
        }
    }

    return count($declarations);
}

// ---------------------------------------------------------------------------

$files = listFiles();

$syntaxChecked = checkSyntax($files, $problems);
$tagChecked    = checkOpeningTags($files, $problems);
checkExtensions($files, $problems);
$classCount    = checkDuplicateClasses($files, $problems);

printf("走査したファイル: %d\n", count($files));
printf("  構文チェック:       %d ファイル\n", $syntaxChecked);
printf("  開始タグ検査:       %d ファイル (テンプレートは除外)\n", $tagChecked);
printf("  クラス宣言:         %d 件\n", $classCount);
echo "\n";

if ($problems === []) {
    echo "問題は見つかりませんでした。\n";
    exit(0);
}

printf("%d 件の問題:\n\n", count($problems));
foreach ($problems as $problem) {
    echo '  ' . $problem . "\n";
}

exit(1);
