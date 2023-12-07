<?php
//$_POST連想配列
function validation($request){

$errors = [];

if(empty($request['your_name'])|| 20<mb<strlen($request['your_name']))){
    $errors[] = '氏名は必須です.20文字以内で入力してください。';
}

return $errors;

}

?>
