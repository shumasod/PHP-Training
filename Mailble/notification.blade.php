<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>通知メール</title>
</head>
<body>
    <h2>{{ $mailData['title'] }}</h2>
    <p>{{ $mailData['body'] }}</p>
    
    <p>送信者情報：{{ $mailData['name'] }}</p>
    
    <footer>
        <p>このメールは自動送信されています。</p>
    </footer>
</body>
</html>
