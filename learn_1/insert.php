<?php

declare(strict_types=1);

/**
 * お問い合わせ内容を contacts テーブルへ INSERT する。
 *
 * 修正前は文字列リテラルにバッククォート (``) を使っていた。
 * PHP のバッククォートは文字列ではなく shell_exec() と等価な
 * 「実行演算子」なので、`,` や `:` はシェルコマンドとして実行されていた。
 * ここではすべてシングルクォートに直している。
 */

require_once __DIR__ . '/db_connection.php';

/**
 * @param array<string, mixed> $request フォームからの入力値
 * @return int 追加された行の ID
 */
function insertContact(PDO $pdo, array $request): int
{
    // INSERT するカラムはコード側で固定する。
    // $request のキーをそのまま SQL に流すと、リクエスト次第で
    // 任意のカラムへ書き込めてしまう（マスアサインメント）。
    $params = [
        'your_name' => $request['your_name'] ?? null,
        'email'     => $request['email'] ?? null,
        'url'       => $request['url'] ?? null,
        'gender'    => $request['gender'] ?? null,
        'age'       => $request['age'] ?? null,
        'contact'   => $request['contact'] ?? null,
    ];

    // id は AUTO_INCREMENT、created_at は DB のデフォルト値に任せるため
    // どちらも INSERT 対象に含めない。
    $columns      = implode(', ', array_keys($params));
    $placeholders = ':' . implode(', :', array_keys($params));

    $sql = "INSERT INTO contacts ({$columns}) VALUES ({$placeholders})";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}
