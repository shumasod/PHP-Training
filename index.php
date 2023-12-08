<?php

$array = [1, 2, 3];

$array_2 = [
    ['赤', '青', '黄'],
    ['緑', '紫', '黒'] // 修正: '紫' と '黒' を別々の要素として追加
];

echo '<pre>';
var_dump($array_2);
echo '</pre>';

$array_member = [
    'name' => '本田',
    'height' => 170,
    'hobby' => 'サッカー'
];

echo $array_member['hobby'];

echo '<pre>';
var_dump($array_member);
echo '</pre>';

$array_member_2 = [
    '本田' => [
        'height' => 170,
        'hobby' => 'サッカー'
    ],
    '香川' => [
        'height' => 165,
        'hobby' => 'サッカー'
    ]
];

echo $array_member_2['香川']['height'];

echo '<pre>';
var_dump($array_member_2);
echo '</pre>';

$array_member_3 = [
    '1kumi' => [
        // 修正: 1組の要素を追加
        '佐藤' => [
            'height' => 175,
            'hobby' => 'バスケットボール'
        ]
    ],
    '2kumi' => [
        '長友' => [
            'height' => 160,
            'hobby' => 'サッカー'
        ],
        '乾' => [
            'height' => 168,
            'hobby' => 'サッカー'
        ]
    ]
];

echo $array_member_3['2kumi']['長友']['height'];

echo '<pre>';
var_dump($array_member_3);
echo '</pre>';
?>
