<?php
declare(strict_types=1);
error_reporting(E_ALL);

class HachikoGame {
    // ゲーム定数
    private const LOCATIONS = [
        '渋谷駅前',
        'センター街', 
        '宮益坂',
        'ハチ公前広場',
        '道玄坂'
    ];
    
    private const MAX_TURNS = 15;
    private const MIN_POSITION = 0;
    private const MAX_POSITION = 4;
    private const HACHIKO_MOVE_CHANCE = 0.3; // 30%の確率で移動
    
    private array $gameState;
    
    public function __construct() {
        $this->initializeGame();
    }
    
    /**
     * ゲームの初期化
     */
    private function initializeGame(): void {
        $this->gameState = [
            'player_position' => 2, // 中央からスタート
            'hachiko_position' => random_int(self::MIN_POSITION, self::MAX_POSITION),
            'turns' => 0,
            'caught' => false,
            'previous_hachiko_position' => null,
            'hints_given' => 0
        ];
    }
    
    /**
     * 位置名を取得
     */
    private function getLocationName(int $position): string {
        if ($position < 0 || $position >= count(self::LOCATIONS)) {
            throw new InvalidArgumentException("無効な位置: {$position}");
        }
        return self::LOCATIONS[$position];
    }
    
    /**
     * プレイヤーの移動処理
     */
    private function movePlayer(string $direction): bool {
        $oldPosition = $this->gameState['player_position'];
        
        switch (strtolower(trim($direction))) {
            case 'left':
            case 'l':
            case '←':
                if ($this->gameState['player_position'] > self::MIN_POSITION) {
                    $this->gameState['player_position']--;
                    return true;
                }
                echo "これ以上左には行けません。\n";
                return false;
                
            case 'right':
            case 'r':
            case '→':
                if ($this->gameState['player_position'] < self::MAX_POSITION) {
                    $this->gameState['player_position']++;
                    return true;
                }
                echo "これ以上右には行けません。\n";
                return false;
                
            case 'hint':
            case 'h':
            case 'ヒント':
                $this->giveHint();
                return false;
                
            case 'quit':
            case 'q':
            case '終了':
                echo "ゲームを終了します。\n";
                exit(0);
                
            default:
                echo "無効な入力です。left/right/hint/quit を入力してください。\n";
                return false;
        }
    }
    
    /**
     * ハチ公の移動処理（ランダム）
     */
    private function moveHachiko(): void {
        // 30%の確率で移動
        if (mt_rand() / mt_getrandmax() < self::HACHIKO_MOVE_CHANCE) {
            $this->gameState['previous_hachiko_position'] = $this->gameState['hachiko_position'];
            
            // ランダムに隣の位置に移動
            $directions = [];
            
            if ($this->gameState['hachiko_position'] > self::MIN_POSITION) {
                $directions[] = $this->gameState['hachiko_position'] - 1;
            }
            if ($this->gameState['hachiko_position'] < self::MAX_POSITION) {
                $directions[] = $this->gameState['hachiko_position'] + 1;
            }
            
            if (!empty($directions)) {
                $this->gameState['hachiko_position'] = $directions[array_rand($directions)];
                echo "遠くで犬の鳴き声が聞こえました...\n";
            }
        }
    }
    
    /**
     * 捕獲判定
     */
    private function checkCatch(): bool {
        return $this->gameState['player_position'] === $this->gameState['hachiko_position'];
    }
    
    /**
     * ヒント提供
     */
    private function giveHint(): void {
        $this->gameState['hints_given']++;
        $distance = abs($this->gameState['player_position'] - $this->gameState['hachiko_position']);
        
        if ($distance === 0) {
            echo "ハチ公はここにいます！よく探してみてください！\n";
        } elseif ($distance === 1) {
            echo "ハチ公がとても近くにいるようです...\n";
        } elseif ($distance === 2) {
            echo "ハチ公の匂いがかすかに感じられます。\n";
        } else {
            echo "ハチ公はまだ遠くにいるようです。\n";
        }
        
        // 方向のヒント
        if ($this->gameState['hachiko_position'] > $this->gameState['player_position']) {
            echo "東の方角から音が聞こえるような...\n";
        } elseif ($this->gameState['hachiko_position'] < $this->gameState['player_position']) {
            echo "西の方角から音が聞こえるような...\n";
        }
    }
    
    /**
     * ゲーム状態の表示
     */
    private function displayGameState(): void {
        echo str_repeat("=", 50) . "\n";
        echo "ターン: {$this->gameState['turns']}/" . self::MAX_TURNS . "\n";
        echo "現在の位置: " . $this->getLocationName($this->gameState['player_position']) . "\n";
        
        // 位置の視覚的表示
        echo "\n位置: ";
        for ($i = self::MIN_POSITION; $i <= self::MAX_POSITION; $i++) {
            if ($i === $this->gameState['player_position']) {
                echo "[あなた] ";
            } else {
                echo "[　　　] ";
            }
        }
        echo "\n\n";
        
        // 残りターン警告
        $remainingTurns = self::MAX_TURNS - $this->gameState['turns'];
        if ($remainingTurns <= 3) {
            echo "⚠️  残り{$remainingTurns}ターンです！\n";
        }
    }
    
    /**
     * 入力を取得
     */
    private function getInput(): string {
        echo "コマンド (left/right/hint/quit): ";
        $input = fgets(STDIN);
        
        if ($input === false) {
            echo "入力エラーが発生しました。\n";
            exit(1);
        }
        
        return trim($input);
    }
    
    /**
     * 最終スコアの計算
     */
    private function calculateScore(): int {
        $baseScore = 1000;
        $turnPenalty = $this->gameState['turns'] * 10;
        $hintPenalty = $this->gameState['hints_given'] * 50;
        
        return max(0, $baseScore - $turnPenalty - $hintPenalty);
    }
    
    /**
     * ゲーム結果の表示
     */
    private function displayResult(): void {
        echo str_repeat("=", 50) . "\n";
        
        if ($this->gameState['caught']) {
            $score = $this->calculateScore();
            echo "🎉 おめでとうございます！\n";
            echo "ハチ公を {$this->gameState['turns']} ターンで見つけました！\n";
            echo "ヒント使用回数: {$this->gameState['hints_given']} 回\n";
            echo "スコア: {$score} 点\n";
            
            // 評価
            if ($score >= 800) {
                echo "評価: 素晴らしい！ハチ公マスター！🏆\n";
            } elseif ($score >= 600) {
                echo "評価: とても良い！優秀な探偵です！🥇\n";
            } elseif ($score >= 400) {
                echo "評価: 良い！もう少しで完璧でした！🥈\n";
            } else {
                echo "評価: ハチ公を見つけられて良かった！🥉\n";
            }
        } else {
            echo "😢 残念！ハチ公を見つけられませんでした。\n";
            echo "ハチ公は「" . $this->getLocationName($this->gameState['hachiko_position']) . "」にいました。\n";
            echo "また挑戦してみてください！\n";
        }
        echo str_repeat("=", 50) . "\n";
    }
    
    /**
     * メインゲームループ
     */
    public function play(): void {
        echo "🐕 ハチ公探しゲームへようこそ！\n";
        echo "渋谷駅周辺でハチ公を探して捕まえてください。\n";
        echo "ハチ公は時々移動するので注意深く探してください。\n";
        echo "コマンド: left(左), right(右), hint(ヒント), quit(終了)\n\n";
        
        while (!$this->gameState['caught'] && $this->gameState['turns'] < self::MAX_TURNS) {
            $this->displayGameState();
            $direction = $this->getInput();
            
            $moved = $this->movePlayer($direction);
            
            if ($moved) {
                $this->gameState['turns']++;
                
                // プレイヤー移動後にハチ公をチェック
                if ($this->checkCatch()) {
                    $this->gameState['caught'] = true;
                    break;
                }
                
                // ハチ公の移動
                $this->moveHachiko();
                
                // ハチ公移動後に再度チェック
                if ($this->checkCatch()) {
                    $this->gameState['caught'] = true;
                    echo "ハチ公があなたのところにやってきました！\n";
                    break;
                }
            }
        }
        
        $this->displayResult();
    }
}

// ゲーム実行
try {
    $game = new HachikoGame();
    $game->play();
} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
    exit(1);
}
?>
