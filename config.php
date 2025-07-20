<?php
// 表示させたいテーブルのカラム定義（自由に変更可）
return [
    'table' => 'users',
    'columns' => [
        'id' => 'ID',
        'name' => '氏名',
        'email' => 'メールアドレス',
        'created_at' => '登録日'
    ],
    'order_by' => 'id ASC'
];
