<?php
session_start();
header('X-FRAME-OPTIONS:DENY');

if (!empty($_POST)) {
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';
}

function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$pageFlag = 0;

if (!empty($_POST['btn_confirm'])){
    $pageFlag = 1;
}
if (!empty($_POST['btn_submit'])){
    $pageFlag = 2;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Your Title Here</title>
    <style>
        /* フォームのスタイル */
        form {
            max-width: 400px;
            margin: 0 auto;
        }

        /* 各項目のスタイル */
        label {
            display: block;
            margin-bottom: 8px;
        }

        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }

        input[type="checkbox"] {
            width: auto; /* チェックボックスの場合、幅を自動にして元のサイズを維持 */
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<?php if ($pageFlag === 0): ?>
    <!-- ページフラグが0の場合の表示 -->
<?php endif; ?>

<?php if ($pageFlag === 1): ?>
    <?php if (isset($_POST['csrf']) && $_POST['csrf'] === $_SESSION['csrfToken']): ?>
        <form method="POST" action="input.php">
            氏名: <?php echo h($_POST['your_name']); ?><br>
            メールアドレス: <?php echo h($_POST['email']); ?><br>
            ホームページ: <?php echo h($_POST['url']); ?><br>
            性別: <?php echo h($_POST['gender'] === '0' ? '男性' : '女性'); ?><br>
            年齢: <?php echo h($_POST['age']); ?><br>
            お問い合わせ内容: <?php echo nl2br(h($_POST['contact'])); ?><br>
            注意事項にチェック: <?php echo isset($_POST['caution']) ? 'はい' : 'いいえ'; ?><br>
            <input type="hidden" name="your_name" value="<?php echo h($_POST['your_name']); ?>">
            <input type="hidden" name="email" value="<?php echo h($_POST['email']); ?>">
            <input type="hidden" name="url" value="<?php echo h($_POST['url']); ?>">
            <input type="hidden" name="gender" value="<?php echo h($_POST['gender']); ?>">
            <input type="hidden" name="age" value="<?php echo h($_POST['age']); ?>">
            <input type="hidden" name="contact" value="<?php echo h($_POST['contact']); ?>">
            <input type="hidden" name="caution" value="<?php echo isset($_POST['caution']) ? '1' : '0'; ?>">
            <input type="hidden" name="csrf" value="<?php echo h($_POST['csrf']); ?>">
            <input type="submit" name="back" value="戻る">
            <input type="submit" name="btn_submit" value="送信する">
        </form>
    <?php endif; ?>
<?php endif; ?>

<?php if ($pageFlag === 2): ?>
    <?php if (isset($_POST['csrf']) && $_POST['csrf'] === $_SESSION['csrfToken']): ?>
        送信が完了しました。<br>
        <?php unset($_SESSION['csrfToken']); ?>
    <?php endif; ?>
<?php endif; ?>

<?php if ($pageFlag === 0): ?>
    <?php
    if (!isset($_SESSION['csrfToken'])){
        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrfToken'] = $csrfToken;
    }
    $token = $_SESSION['csrfToken'];
    ?>
<?php endif; ?>

<form method="POST" action="input.php">
    氏名: <input type="text" name="your_name" value="<?php echo !empty($_POST['your_name']) ? h($_POST['your_name']) : ''; ?>"><br>
    メールアドレス: <input type="email" name="email" value="<?php echo !empty($_POST['email']) ? h($_POST['email']) : ''; ?>"><br>
    ホームページ: <input type="url" name="url" value="<?php echo !empty($_POST['url']) ? h($_POST['url']) : ''; ?>"><br>
    性別: 
    <input type="radio" name="gender" value="0" <?php echo !empty($_POST['gender']) && $_POST['gender'] === '0' ? 'checked' : ''; ?>>男性
    <input type="radio" name="gender" value="1" <?php echo !empty($_POST['gender']) && $_POST['gender'] === '1' ? 'checked' : ''; ?>>女性
    <br>
    年齢: 
    <select name="age">
        <option value="">選択してください。</option>
        <option value="1" <?php echo !empty($_POST['age']) && $_POST['age'] === '1' ? 'selected' : ''; ?>>~19歳</option>
        <option value="2" <?php echo !empty($_POST['age']) && $_POST['age'] === '2' ? 'selected' : ''; ?>>20歳～29歳</option>
        <option value="3" <?php echo !empty($_POST['age']) && $_POST['age'] === '3' ? 'selected' : ''; ?>>30歳~39歳</option>
        <option value="4" <?php echo !empty($_POST['age']) && $_POST['age'] === '4' ? 'selected' : ''; ?>>40歳~49歳</option>
        <option value="5" <?php echo !empty($_POST['age']) && $_POST['age'] === '5' ? 'selected' : ''; ?>>50歳~59歳</option>
        <option value="6" <?php echo !empty($_POST['age']) && $_POST['age'] === '6' ? 'selected' : ''; ?>>60歳 </option>
    </select>
    <br>
    お問い合わせ内容: <textarea name="contact"><?php echo !empty($_POST['contact']) ? h($_POST['contact']) : ''; ?></textarea><br>
    <br>
    <input type="checkbox" name="caution" value="1" <?php echo !empty($_POST['caution']) ? 'checked' : ''; ?>>注意事項にチェックする。
    <br>
    <input type="submit" name="btn_confirm" value="確認する">
    <input type="hidden" name="csrf" value="<?php echo $token; ?>">
</form>

</body>
</html>
