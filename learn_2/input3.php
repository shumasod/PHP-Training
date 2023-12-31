<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $gender = isset($_POST["gender"]) ? $_POST["gender"] : '';
    $homepage = $_POST["homepage"];
    $message = $_POST["message"];

    // ここでデータベースに保存したり、メール送信したり、適切な処理を行います。

    // 仮の例として、フォームデータを表示
    echo "<h2>お問い合わせ内容</h2>";
    echo "<p><strong>お名前:</strong> $name</p>";
    echo "<p><strong>メールアドレス:</strong> $email</p>";
    echo "<p><strong>性別:</strong> $gender</p>";
    echo "<p><strong>ホームページURL:</strong> $homepage</p>";
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
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4">お問い合わせフォーム</h2>
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
            <label>性別:</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="男性">
                <label class="form-check-label" for="gender_male">男性</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="女性">
                <label class="form-check-label" for="gender_female">女性</label>
            </div>
        </div>
        <div class="form-group">
            <label for="homepage">ホームページURL:</label>
            <input type="url" class="form-control" id="homepage" name="homepage">
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
