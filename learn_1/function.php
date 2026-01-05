<?php
function memo_table_value(int $id)
{
  $dbcon = new mysqli('ホスト名', 'ユーザー名', 'パスワード', 'データベース名');

  if ($dbcon->connect_error) {
    echo "データベースに接続できません:" . htmlspecialchars($dbcon->connect_error, ENT_QUOTES, 'UTF-8');
    exit ();
  }

  // SQLインジェクション対策: プリペアドステートメントを使用
  $stmt = $dbcon->prepare('SELECT name FROM memo WHERE kind = ?');
  if ($stmt === false) {
    echo "ステートメントの準備に失敗しました";
    exit();
  }

  $stmt->bind_param('i', $id);
  $stmt->execute();
  $result = $stmt->get_result();

  foreach ($result as $col) {
    // XSS対策: 出力をエスケープ
    echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8') . "<br>";
  }

  $stmt->close();
  mysqli_close($dbcon);
}
?>
