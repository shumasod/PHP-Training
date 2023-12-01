<?php
function memo_table_value(int $id)
{
  $dbcon = new mysqli('ホスト名', 'ユーザー名', 'パスワード', 'データベース名');

  if ($dbcon->connect_error) {
    echo "データベースに接続できません:" . $dbcon->connect_error;
    exit ();
  }

  $select = 'SELECT name FROM memo WHERE kind = ' . $id;
  $result = mysqli_query($dbcon, $select);
  foreach ($result as $col) {
    echo $col['name'] . "<br>";
  }
  mysqli_close($dbcon);
}
?>
