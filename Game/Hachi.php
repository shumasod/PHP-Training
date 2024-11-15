<?php

function initializeGame() {
    return [
        'player_position' => 0,
        'hachiko_position' => rand(0, 4),
        'turns' => 0,
        'caught' => false
    ];
}

function displayLocation($position) {
    $locations = [
        '渋谷駅前',
        'センター街',
        '宮益坂',
        'ハチ公前広場',
        '道玄坂'
    ];
    return $locations[$position];
}

function movePlayer($direction, &$game) {
    if ($direction === 'left' && $game['player_position'] > 0) {
        $game['player_position']--;
    } elseif ($direction === 'right' && $game['player_position'] < 4) {
        $game['player_position']++;
    }
    $game['turns']++;
}

function checkCatch(&$game) {
    if ($game['player_position'] === $game['hachiko_position']) {
        $game['caught'] = true;
    }
}

function playGame() {
    $game = initializeGame();
    
    echo "ハチを捕まえるゲームへようこそ！\n";
    echo "渋谷駅周辺でハチ公を探し、捕まえてください。\n";

    while (!$game['caught']) {
        echo "\n現在の位置: " . displayLocation($game['player_position']) . "\n";
        echo "どちらに移動しますか？ (left/right): ";
        $direction = trim(fgets(STDIN));

        movePlayer($direction, $game);
        checkCatch($game);

        if ($game['turns'] >= 10 && !$game['caught']) {
            echo "ハチ公を見失ってしまいました。ゲームオーバー！\n";
            break;
        }
    }

    if ($game['caught']) {
        echo "おめでとうございます！" . $game['turns'] . "ターンでハチ公を捕まえました！\n";
    }
}

playGame();
?>
