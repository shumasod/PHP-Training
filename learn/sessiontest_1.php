<?php
// セキュアなセッション設定
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
?>

<html>
<head></head>
<body>

<?php
if (!isset($_SESSION['visited'])) {
    echo '初回訪問です';
    $_SESSION['visited'] = 1;
    $_SESSION['date'] = date('c');
} else {
    $visited = $_SESSION['visited'];
    $visited++;
    $_SESSION['visited'] = $visited;

    // XSS対策: セッション値をエスケープして出力
    echo htmlspecialchars($_SESSION['visited'], ENT_QUOTES, 'UTF-8') . '回目の訪問です<br>';

    if (isset($_SESSION['date'])) {
        echo '前回訪問は' . htmlspecialchars($_SESSION['date'], ENT_QUOTES, 'UTF-8') . 'です';
        $_SESSION['date'] = date('c');
    }

    // setcookie("id", 'aaa')
    echo `<pre>`;
    var_dump($_SESSION);
    echo `</pre>`;

    echo `<pre>`;
    var_dump($_COOKIE);
    echo `</pre>`;

}
?>
</body>
</html>
