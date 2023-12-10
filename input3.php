<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $message = $_POST["message"];

    // ここでデータベースに保存したり、メール送信したり、適切な処理を行います。

    // 仮の例として、フォームデータを表示
    echo "<h2>お問い合わせ内容</h2>";
    echo "<p><strong>お名前:</strong> $name</p>";
    echo "<p><strong>メールアドレス:</strong> $email</p>";
    echo "<p><strong>お問い合わせ内容:</strong><br> $message</p>";
} else {
    // POST メソッド以外でアクセスされた場合のエラーハンドリング
    echo "このページには直接アクセスできません。";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お問い合わせフォーム</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>お問い合わせフォーム</h2>
    <form action="process_form.php" method="post">
        <div class="form-group">
            <label for="name">お名前:</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">メールアドレス:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="message">お問い合わせ内容:</label>
            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">送信</button>
    </form>
</div>

<!-- Bootstrap JS and dependencies (jQuery, Popper.js) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
