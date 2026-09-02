<?php

declare(strict_types=1);

/**
 * お問い合わせフォームの入力チェック。
 *
 * 修正前は関数の中身が空で、検証コードはすべて関数の外に置かれていた。
 *
 *     function validation($request){
 *     }                              // ← ここで関数が閉じている
 *     $errors = [];
 *     if(empty($request['your_name']) || ...) { ... }   // ← 関数の外
 *
 * このため validation($_POST) は常に null を返し、
 * 呼び出し側の `if (empty($errors))` が必ず真になっていた。
 * つまり入力チェックが一切効いていない状態だった。
 *
 * @param array<string, mixed> $request $_POST 相当の連想配列
 * @return list<string> エラーメッセージの配列（問題なければ空配列）
 */
function validation(array $request): array
{
    $errors = [];

    // 氏名
    $yourName = trim((string) ($request['your_name'] ?? ''));
    if ($yourName === '' || mb_strlen($yourName) > 20) {
        $errors[] = '「氏名」は必須です。20文字以内で入力してください。';
    }

    // メールアドレス
    $email = trim((string) ($request['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '「メールアドレス」は必須です。正しい形式で入力してください。';
    }

    // ホームページ URL（任意）
    $url = trim((string) ($request['url'] ?? ''));
    if ($url !== '') {
        // FILTER_VALIDATE_URL はスキームを検証しない。
        //
        // PHP 8.4 で実際に確かめた結果:
        //
        //     javascript:alert(1)                    弾く
        //     javascript://example.com/%0Aalert(1)   通る  ← XSS として成立する
        //     file:///etc/passwd                     通る
        //     ftp://example.com/x                    通る
        //
        // "//" を含む形にすると javascript: でも通ってしまう
        // (// が JavaScript のコメント、%0A が改行になり、その後が実行される)。
        // リンクとして出力する値なのでスキームまで確認する。
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $isHttpUrl = filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_string($scheme)
            && in_array(strtolower($scheme), ['http', 'https'], true);

        if (!$isHttpUrl) {
            $errors[] = '「ホームページ」は http:// または https:// から始まる形式で入力してください。';
        }
    }

    // 性別
    // 修正前は isset() だけを見ていたため、想定外の値でも通っていた。
    // ラジオボタンの value は '0' か '1' なので、その 2 つに限定する。
    $gender = (string) ($request['gender'] ?? '');
    if (!in_array($gender, ['0', '1'], true)) {
        $errors[] = '「性別」は必須です。';
    }

    // 年齢
    //
    // 修正前は
    //     if (empty($request['age'] || 6 < $request['age']))
    // と、|| が empty() の「中」に入っていた。
    // empty() の引数は bool になるので、実際には
    // empty(true) / empty(false) を評価しているだけで、
    // 意図した「未入力または範囲外」の判定になっていない。
    $age = (string) ($request['age'] ?? '');
    $allowedAges = ['10', '20', '30', '40', '50', '60'];
    if (!in_array($age, $allowedAges, true)) {
        $errors[] = '「年齢」は必須です。';
    }

    // お問い合わせ内容
    //
    // 修正前はこのチェックが年齢チェックの if の内側に入れ子になっており、
    // 年齢が未入力のときしか実行されなかった。
    // またメッセージは「200文字以内」と書きつつ 20 で判定していた。
    $contact = trim((string) ($request['contact'] ?? ''));
    if ($contact === '' || mb_strlen($contact) > 200) {
        $errors[] = '「お問い合わせ内容」は必須です。200文字以内で入力してください。';
    }

    // 注意事項のチェックボックス
    //
    // 修正前はこのチェックが return の後ろ、かつ関数の外に置かれていて
    // 一度も実行されなかった。
    if (empty($request['caution'])) {
        $errors[] = '注意事項をご確認ください。';
    }

    return $errors;
}
