<?php
session_start();

require_once __DIR__ . '/../learn/validation.php';

header('X-FRAME-OPTIONS:DENY');

// スーパーグローバル変数　php 9種類
// 連想配列

// デバッグ情報は本番環境では削除すること
// if (!empty($_POST)) {
//     echo '<pre>';
//     var_dump($_POST);
//     echo '</pre>';
// }

// ファンクション　string型変数
function h(?string $str): string
{
    // PHP 8.1 以降、htmlspecialchars() に null を渡すと Deprecated になる。
    // ENT_SUBSTITUTE を足して、不正な UTF-8 バイト列は空文字ではなく
    // 置換文字へ落とす（空文字になるとエスケープが素通りしたように見えるため）。
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * CSRF トークンを検証する。
 *
 * 修正前は次のように書かれていた。
 *
 *     if (verifyCsrfToken())
 *
 * この比較には 2 つの問題がある。
 *
 * 1. トークン検証を素通りできる
 *    どちらのキーも未定義だと、PHP 8 では Warning が出たうえで
 *    両辺とも null になり、null === null が true になる。
 *    つまりセッションにトークンが無い状態で、csrf を一切含まない
 *    POST を送ると検証を通過してしまう。
 *
 * 2. タイミング攻撃に対して安全でない
 *    === は最初に異なるバイトが見つかった時点で false を返すため、
 *    比較にかかる時間からトークンを 1 バイトずつ推測できる。
 *    hash_equals() は長さに比例した一定時間で比較する。
 */
function verifyCsrfToken(): bool
{
    $sessionToken = $_SESSION['csrfToken'] ?? '';
    $postedToken  = $_POST['csrf'] ?? '';

    // 空同士が一致してしまわないよう、実在することを先に確認する。
    if (!is_string($sessionToken) || $sessionToken === ''
        || !is_string($postedToken) || $postedToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $postedToken);
}

// 入力、確認、完了　input.php, confirm.php, thanks.php
// CSRF 偽物のinput.php→悪意のあるページ
// input.php

$pageFlag = 0;
$errors = validation($_POST);

if (!empty($_POST['btn_confirm']) && empty($errors)) {
    $pageFlag = 1;
}
if (!empty($_POST['btn_submit'])) {
    $pageFlag = 2;
}
?>
<!doctype html>
<html lang="ja">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <title>Hello, world!</title>
</head>

<body>

    <?php
    // CSRF 検証に失敗したときに何も表示されない問題への対応。
    //
    // 修正前は if (verifyCsrfToken()) が偽のとき、その分岐の中身が
    // まるごと出力されないだけだった。利用者からは真っ白なページに見え、
    // 原因が分からないまま再送信を繰り返すことになる。
    // （セッション切れでトークンが消えた場合に普通に起きる）
    $csrfFailed = ($pageFlag === 1 || $pageFlag === 2) && !verifyCsrfToken();
    ?>
    <?php if ($csrfFailed) : ?>
        <div class="container mt-5">
            <div class="alert alert-danger" role="alert">
                セッションの有効期限が切れたか、リクエストが正しくありません。
                <a href="<?php echo h(basename(__FILE__)); ?>">最初からやり直してください。</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($pageFlag === 1) : ?>
        <?php if (verifyCsrfToken()) : ?>
            <?php
            // セキュリティ: セッション固定攻撃対策としてセッションIDを再生成
            session_regenerate_id(true);
            ?>
            <form method="POST" action="input2.php">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">確認画面</h5>

                                    <div class="form-group">
                                        <label for="your_name">氏名</label>
                                        <?php echo h($_POST['your_name'] ?? ''); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">メールアドレス</label>
                                        <?php echo h($_POST['email'] ?? ''); ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="url">ホームページ</label>
                                        <input type="url" class="form-control" id="url" value="<?php echo h($_POST['url'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>性別</label>
                                        <?php
                                        if (($_POST['gender'] ?? '') === '0') {
                                            echo '男性';
                                        }
                                        if (($_POST['gender'] ?? '') === '1') {
                                            echo '女性';
                                        }
                                        ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="age">年齢</label>
                                        <?php
                                        if (($_POST['age'] ?? '') === '10') {
                                            echo '~19歳';
                                        } elseif (($_POST['age'] ?? '') === '20') {
                                            echo '20歳~29歳';
                                        } elseif (($_POST['age'] ?? '') === '30') {
                                            echo '30歳~39歳';
                                        } elseif (($_POST['age'] ?? '') === '40') {
                                            echo '40歳~49歳';
                                        } elseif (($_POST['age'] ?? '') === '50') {
                                            echo '50歳~59歳';
                                        } elseif (($_POST['age'] ?? '') === '60') {
                                            echo '60歳~';
                                        }
                                        ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="contact">お問い合わせ内容</label>
                                        <?php echo h($_POST['contact'] ?? ''); ?>
                                    </div>

                                    <input type="submit" name="back" class="btn btn-secondary" value="戻る">
                                    <input type="submit" name="btn_submit" class="btn btn-primary" value="送信する">
                                    <input type="hidden" name="your_name" value="<?php echo h($_POST['your_name'] ?? ''); ?>">
                                    <input type="hidden" name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
                                    <input type="hidden" name="url" value="<?php echo h($_POST['url'] ?? ''); ?>">
                                    <input type="hidden" name="gender" value="<?php echo h($_POST['gender'] ?? ''); ?>">
                                    <input type="hidden" name="age" value="<?php echo h($_POST['age'] ?? ''); ?>">
                                    <input type="hidden" name="contact" value="<?php echo h($_POST['contact'] ?? ''); ?>">
                                    <input type="hidden" name="csrf" value="<?php echo h($_POST['csrf'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($pageFlag === 1 && verifyCsrfToken()) : ?>
        <form method="POST" action="input2.php">
            <?php if (!empty($_SESSION)) : ?>
                <pre>
            <?php var_dump($_SESSION); ?>
        </pre>
            <?php endif; ?>
            <!-- ... フォームフィールド ... -->
        </form>
    <?php endif; ?>


    <?php if ($pageFlag === 2) : ?>
        <?php if (verifyCsrfToken()) : ?>
            <?php
            // セキュリティ: セッション固定攻撃対策としてセッションIDを再生成
            session_regenerate_id(true);
            ?>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">送信完了</h5>
                                送信が完了しました。
                                <?php unset($_SESSION['csrfToken']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($pageFlag === 0) : ?>
        <?php
        if (!isset($_SESSION['csrfToken'])) {
            $csrfToken = bin2hex(random_bytes(32));
            $_SESSION['csrfToken'] = $csrfToken;
        }
        $token = $_SESSION['csrfToken'];
        ?>

        <?php if (!empty($errors) && !empty($_POST['btn_confirm'])) : ?>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="alert alert-danger" role="alert">
                            <strong>エラーが発生しました：</strong>
                            <ul>
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo h($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form method="POST" action="input2.php">
                        <div class="form-group">
                            <label for="your_name">氏名</label>
                            <input type="text" class="form-control" id="your_name" name="your_name" value="<?php echo !empty($_POST['your_name']) ? h($_POST['your_name'] ?? '') : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo !empty($_POST['email']) ? h($_POST['email'] ?? '') : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="url">ホームページ</label>
                            <input type="url" class="form-control" id="url" value="<?php echo !empty($_SESSION['url']) ? h($_SESSION['url']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>性別</label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="gender" id="gender1" value="0" <?php echo (!empty($_POST['gender']) && ($_POST['gender'] ?? '') === '0') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender1">男性</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="gender" id="gender2" value="1" <?php echo (!empty($_POST['gender']) && ($_POST['gender'] ?? '') === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender2">女性</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="age">年齢</label>
                            <select class="form-control" id="age" name="age">
                                <option value="">選択してください。</option>
                                <option value="10" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '10') ? 'selected' : ''; ?>>~19歳</option>
                                <option value="20" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '20') ? 'selected' : ''; ?>>20歳~29歳</option>
                                <option value="30" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '30') ? 'selected' : ''; ?>>30歳~39歳</option>
                                <option value="40" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '40') ? 'selected' : ''; ?>>40歳~49歳</option>
                                <option value="50" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '50') ? 'selected' : ''; ?>>50歳~59歳</option>
                                <option value="60" <?php echo (!empty($_POST['age']) && ($_POST['age'] ?? '') === '60') ? 'selected' : ''; ?>>60歳~</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="contact">お問い合わせ内容</label>
                            <textarea class="form-control" id="contact" rows="3" name="contact"><?php echo !empty($_POST['contact']) ? h($_POST['contact'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="caution" name="caution" required>
                            <label class="form-check-label" for="caution">注意事項にチェックする</label>
                        </div>

                        <input class="btn btn-info" type="submit" name="btn_confirm" value="確認する">
                        <input type="hidden" name="csrf" value="<?php echo $token; ?>">
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>

</html>
?>
