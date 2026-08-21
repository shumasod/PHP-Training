<?php

declare(strict_types=1);

/**
 * パスワードハッシュの練習。
 *
 * 修正前は次の 2 つをそのまま画面へ出力していた。
 *
 *     echo __FILE__;                                  // 絶対パス
 *     echo password_hash('password123', PASSWORD_BCRYPT);
 *
 * __FILE__ はサーバ上の絶対パス（/var/www/... など）を含む。
 * ディレクトリ構成やユーザー名が分かると、他の脆弱性と組み合わせた
 * 攻撃の足がかりになるため、画面には出さない。
 *
 * また、先頭のコメントが「パスワードを記録したファイルの場所」と
 * なっていたが、実際に記録しているわけではない。誤解を招くので直した。
 */

// 1. ハッシュ化
//
// PASSWORD_BCRYPT ではなく PASSWORD_DEFAULT を使う。
// PASSWORD_DEFAULT は「その PHP における推奨アルゴリズム」を指し、
// PHP のバージョンが上がると自動的に強いものへ切り替わる。
// アルゴリズムを固定すると、その恩恵を受けられない。
//
// bcrypt はパスワードを 72 バイトで切り捨てるという制限もある。
$plain = 'password123';
$hash = password_hash($plain, PASSWORD_DEFAULT);

echo 'アルゴリズム: ' . password_get_info($hash)['algoName'] . PHP_EOL;

// 2. 検証
//
// ハッシュ同士を === で比べてはいけない。
// password_hash() は毎回異なるソルトを使うため、同じパスワードでも
// 生成されるハッシュは毎回変わる。必ず password_verify() を使う。
var_dump(password_verify($plain, $hash));        // true
var_dump(password_verify('wrong', $hash));       // false

// 同じパスワードでもハッシュが毎回変わることの確認
var_dump(password_hash($plain, PASSWORD_DEFAULT) === $hash); // false

// 3. 再ハッシュの要否
//
// PHP を上げたり cost を変えたりすると、既存のハッシュが
// 現在の推奨より弱くなることがある。ログイン成功時にこれを確認し、
// 必要なら平文が手元にあるうちにハッシュを作り直す。
if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    echo '再ハッシュしました' . PHP_EOL;
}
