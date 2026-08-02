<?php

// 使用例とテストケース
class SqlCorrectorExample
{
    public static function examples(): array
    {
        return [
            // エラーのあるSQL例
            'SELECT FROM users',                           // カラム指定なし
            'SELECT * users WHERE id = 1',                // FROM句なし
            'SELECT name, FROM users',                     // 不要なカンマ
            'SELECT * FROM users WHERE name = John',       // 引用符なし
            'INSERT INTO users VALUES',                    // VALUES句不完全
            'UPDATE users WHERE id = 1',                   // SET句なし
            'SELECT * FROM users JOIN orders WHERE',       // JOIN条件なし
            'SELECT * FROM users GROUP BY',                // GROUP BY カラムなし
        ];
    }
}
