<?php
declare(strict_types=1);

/**
 * ハチ公探しゲーム - 改善版
 * 
 * PHP 8.1以降推奨
 * 改善点:
 * - Enumの使用
 * - カスタム例外クラス
 * - 依存性注入
 * - 統計機能の追加
 * - より堅牢なエラーハンドリング
 * - テスタビリティの向上
 */

// エラー報告の設定
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * ゲームコマンドEnum
 */
enum GameCommand: string {
    case LEFT = 'left';
    case RIGHT = 'right';
    case HINT = 'hint';
    case QUIT = 'quit';
    case STATUS = 'status';
    case SAVE = 'save';
    
    /**
     * 文字列からコマンドを取得
     */
    public static function fromString(string $input): ?self {
        $input = strtolower(trim($input));
        
        return match($input) {
            'left', 'l', '←', '左' => self::LEFT,
            'right', 'r', '→', '右' => self::RIGHT,
            'hint', 'h', 'ヒント' => self::HINT,
            'quit', 'q', '終了', 'exit' => self::QUIT,
            'status', 's', 'ステータス' => self::STATUS,
            'save', 'セーブ' => self::SAVE,
            default => null,
        };
    }
    
    public function getDescription(): string {
        return match($this) {
            self::LEFT => '左に移動',
            self::RIGHT => '右に移動',
            self::HINT => 'ヒントを見る',
            self::QUIT => 'ゲーム終了',
            self::STATUS => 'ステータス表示',
            self::SAVE => 'ゲーム保存',
        };
    }
}

/**
 * 難易度Enum
 */
enum Difficulty: string {
    case EASY = 'easy';
    case NORMAL = 'normal';
    case HARD = 'hard';
    
    public function getMaxTurns(): int {
        return match($this) {
            self::EASY => 20,
            self::NORMAL => 15,
            self::HARD => 10,
        };
    }
    
    public function getMoveChance(): float {
        return match($this) {
            self::EASY => 0.2,
            self::NORMAL => 0.3,
            self::HARD => 0.5,
        };
    }
    
    public function getDescription(): string {
        return match($this) {
            self::EASY => '簡単（ターン: 20, ハチ公移動率: 20%）',
            self::NORMAL => '普通（ターン: 15, ハチ公移動率: 30%）',
            self::HARD => '難しい（ターン: 10, ハチ公移動率: 50%）',
        };
    }
}

/**
 * カスタム例外: ゲーム例外
 */
class GameException extends Exception {}

/**
 * カスタム例外: 入力例外
 */
class InvalidInputException extends GameException {}

/**
 * カスタム例外: 移動例外
 */
class InvalidMoveException extends GameException {}

/**
 * ゲーム設定クラス
 */
readonly class GameConfig {
    public function __construct(
        public array $locations = [
            '渋谷駅前',
            'センター街',
            '宮益坂',
            'ハチ公前広場',
            '道玄坂'
        ],
        public int $minPosition = 0,
        public int $maxPosition = 4,
        public Difficulty $difficulty = Difficulty::NORMAL,
    ) {}
    
    public function getMaxTurns(): int {
        return $this->difficulty->getMaxTurns();
    }
    
    public function getMoveChance(): float {
        return $this->difficulty->getMoveChance();
    }
    
    public function isValidPosition(int $position): bool {
        return $position >= $this->minPosition && $position <= $this->maxPosition;
    }
    
    public function getLocationName(int $position): string {
        if (!$this->isValidPosition($position)) {
            throw new InvalidArgumentException("無効な位置: {$position}");
        }
        return $this->locations[$position];
    }
}

/**
 * ゲーム状態クラス
 */
class GameState {
    private int $playerPosition;
    private int $hachikoPosition;
    private int $turns = 0;
    private bool $caught = false;
    private ?int $previousHachikoPosition = null;
    private int $hintsGiven = 0;
    private array $moveHistory = [];
    
    public function __construct(
        int $initialPlayerPosition,
        int $initialHachikoPosition
    ) {
        $this->playerPosition = $initialPlayerPosition;
        $this->hachikoPosition = $initialHachikoPosition;
    }
    
    // Getters
    public function getPlayerPosition(): int { return $this->playerPosition; }
    public function getHachikoPosition(): int { return $this->hachikoPosition; }
    public function getTurns(): int { return $this->turns; }
    public function isCaught(): bool { return $this->caught; }
    public function getHintsGiven(): int { return $this->hintsGiven; }
    public function getPreviousHachikoPosition(): ?int { return $this->previousHachikoPosition; }
    public function getMoveHistory(): array { return $this->moveHistory; }
    
    // Setters
    public function setPlayerPosition(int $position): void {
        $this->playerPosition = $position;
    }
    
    public function setHachikoPosition(int $position): void {
        $this->previousHachikoPosition = $this->hachikoPosition;
        $this->hachikoPosition = $position;
    }
    
    public function incrementTurns(): void {
        $this->turns++;
    }
    
    public function setCaught(bool $caught): void {
        $this->caught = $caught;
    }
    
    public function incrementHints(): void {
        $this->hintsGiven++;
    }
    
    public function addMoveToHistory(string $move): void {
        $this->moveHistory[] = [
            'turn' => $this->turns,
            'move' => $move,
            'player_position' => $this->playerPosition,
            'timestamp' => time()
        ];
    }
    
    /**
     * 配列形式でエクスポート（保存用）
     */
    public function toArray(): array {
        return [
            'player_position' => $this->playerPosition,
            'hachiko_position' => $this->hachikoPosition,
            'turns' => $this->turns,
            'caught' => $this->caught,
            'previous_hachiko_position' => $this->previousHachikoPosition,
            'hints_given' => $this->hintsGiven,
            'move_history' => $this->moveHistory,
        ];
    }
    
    /**
     * 配列からインポート（読み込み用）
     */
    public static function fromArray(array $data): self {
        $state = new self(
            $data['player_position'] ?? 2,
            $data['hachiko_position'] ?? 0
        );
        
        $state->turns = $data['turns'] ?? 0;
        $state->caught = $data['caught'] ?? false;
        $state->previousHachikoPosition = $data['previous_hachiko_position'] ?? null;
        $state->hintsGiven = $data['hints_given'] ?? 0;
        $state->moveHistory = $data['move_history'] ?? [];
        
        return $state;
    }
}

/**
 * 入出力インターフェース
 */
interface IOInterface {
    public function readLine(string $prompt): string;
    public function writeLine(string $message): void;
    public function write(string $message): void;
}

/**
 * コンソール入出力クラス
 */
class ConsoleIO implements IOInterface {
    public function readLine(string $prompt): string {
        $this->write($prompt);
        $input = fgets(STDIN);
        
        if ($input === false) {
            throw new InvalidInputException("入力の読み取りに失敗しました");
        }
        
        return trim($input);
    }
    
    public function writeLine(string $message): void {
        echo $message . PHP_EOL;
    }
    
    public function write(string $message): void {
        echo $message;
    }
}

/**
 * スコア計算クラス
 */
class ScoreCalculator {
    private const BASE_SCORE = 1000;
    private const TURN_PENALTY = 10;
    private const HINT_PENALTY = 50;
    
    public function calculate(GameState $state): int {
        if (!$state->isCaught()) {
            return 0;
        }
        
        $score = self::BASE_SCORE;
        $score -= $state->getTurns() * self::TURN_PENALTY;
        $score -= $state->getHintsGiven() * self::HINT_PENALTY;
        
        return max(0, $score);
    }
    
    public function getRating(int $score): string {
        return match(true) {
            $score >= 800 => '素晴らしい！ハチ公マスター！🏆',
            $score >= 600 => 'とても良い！優秀な探偵です！🥇',
            $score >= 400 => '良い！もう少しで完璧でした！🥈',
            default => 'ハチ公を見つけられて良かった！🥉',
        };
    }
}

/**
 * ゲーム統計クラス
 */
class GameStatistics {
    private string $statsFile = 'hachiko_stats.json';
    
    public function __construct(
        private array $stats = [
            'games_played' => 0,
            'games_won' => 0,
            'total_turns' => 0,
            'best_score' => 0,
            'average_turns' => 0.0,
        ]
    ) {
        $this->loadStats();
    }
    
    public function recordGame(GameState $state, int $score): void {
        $this->stats['games_played']++;
        
        if ($state->isCaught()) {
            $this->stats['games_won']++;
            $this->stats['total_turns'] += $state->getTurns();
            $this->stats['average_turns'] = 
                $this->stats['total_turns'] / $this->stats['games_won'];
            
            if ($score > $this->stats['best_score']) {
                $this->stats['best_score'] = $score;
            }
        }
        
        $this->saveStats();
    }
    
    public function display(): string {
        $winRate = $this->stats['games_played'] > 0
            ? round(($this->stats['games_won'] / $this->stats['games_played']) * 100, 1)
            : 0;
        
        return sprintf(
            "統計情報:\n" .
            "  プレイ回数: %d\n" .
            "  勝利数: %d\n" .
            "  勝率: %.1f%%\n" .
            "  最高スコア: %d\n" .
            "  平均ターン数: %.1f\n",
            $this->stats['games_played'],
            $this->stats['games_won'],
            $winRate,
            $this->stats['best_score'],
            $this->stats['average_turns']
        );
    }
    
    private function loadStats(): void {
        if (file_exists($this->statsFile)) {
            $data = file_get_contents($this->statsFile);
            if ($data !== false) {
                $loaded = json_decode($data, true);
                if (is_array($loaded)) {
                    $this->stats = array_merge($this->stats, $loaded);
                }
            }
        }
    }
    
    private function saveStats(): void {
        file_put_contents(
            $this->statsFile,
            json_encode($this->stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}

/**
 * メインゲームクラス
 */
class HachikoGame {
    private GameState $state;
    private GameConfig $config;
    private IOInterface $io;
    private ScoreCalculator $scoreCalculator;
    private GameStatistics $statistics;
    private string $saveFile = 'hachiko_save.json';
    
    public function __construct(
        ?GameConfig $config = null,
        ?IOInterface $io = null,
        ?ScoreCalculator $scoreCalculator = null,
        ?GameStatistics $statistics = null
    ) {
        $this->config = $config ?? new GameConfig();
        $this->io = $io ?? new ConsoleIO();
        $this->scoreCalculator = $scoreCalculator ?? new ScoreCalculator();
        $this->statistics = $statistics ?? new GameStatistics();
        $this->initializeGame();
    }
    
    /**
     * ゲームの初期化
     */
    private function initializeGame(): void {
        $playerStart = (int)floor(($this->config->maxPosition - $this->config->minPosition) / 2);
        $hachikoStart = random_int($this->config->minPosition, $this->config->maxPosition);
        
        $this->state = new GameState($playerStart, $hachikoStart);
    }
    
    /**
     * プレイヤーの移動処理
     */
    private function movePlayer(GameCommand $command): bool {
        $oldPosition = $this->state->getPlayerPosition();
        $newPosition = $oldPosition;
        
        switch ($command) {
            case GameCommand::LEFT:
                if ($this->state->getPlayerPosition() <= $this->config->minPosition) {
                    throw new InvalidMoveException("これ以上左には行けません。");
                }
                $newPosition = $oldPosition - 1;
                break;
                
            case GameCommand::RIGHT:
                if ($this->state->getPlayerPosition() >= $this->config->maxPosition) {
                    throw new InvalidMoveException("これ以上右には行けません。");
                }
                $newPosition = $oldPosition + 1;
                break;
                
            default:
                return false;
        }
        
        $this->state->setPlayerPosition($newPosition);
        $this->state->addMoveToHistory($command->value);
        return true;
    }
    
    /**
     * ハチ公の移動処理
     */
    private function moveHachiko(): void {
        $moveChance = mt_rand() / mt_getrandmax();
        
        if ($moveChance < $this->config->getMoveChance()) {
            $directions = [];
            $currentPos = $this->state->getHachikoPosition();
            
            if ($currentPos > $this->config->minPosition) {
                $directions[] = $currentPos - 1;
            }
            if ($currentPos < $this->config->maxPosition) {
                $directions[] = $currentPos + 1;
            }
            
            if (!empty($directions)) {
                $newPosition = $directions[array_rand($directions)];
                $this->state->setHachikoPosition($newPosition);
                $this->io->writeLine("🐕 遠くで犬の鳴き声が聞こえました...");
            }
        }
    }
    
    /**
     * 捕獲判定
     */
    private function checkCatch(): bool {
        return $this->state->getPlayerPosition() === $this->state->getHachikoPosition();
    }
    
    /**
     * ヒント提供
     */
    private function giveHint(): void {
        $this->state->incrementHints();
        $distance = abs(
            $this->state->getPlayerPosition() - $this->state->getHachikoPosition()
        );
        
        $message = match(true) {
            $distance === 0 => "🎯 ハチ公はここにいます！よく探してみてください！",
            $distance === 1 => "🔥 ハチ公がとても近くにいるようです...",
            $distance === 2 => "👃 ハチ公の匂いがかすかに感じられます。",
            default => "🌫️ ハチ公はまだ遠くにいるようです。",
        };
        
        $this->io->writeLine($message);
        
        // 方向のヒント
        if ($distance > 0) {
            $direction = $this->state->getHachikoPosition() > $this->state->getPlayerPosition()
                ? "東（右）"
                : "西（左）";
            $this->io->writeLine("📍 {$direction}の方角から音が聞こえるような...");
        }
    }
    
    /**
     * ゲーム状態の表示
     */
    private function displayGameState(): void {
        $this->io->writeLine(str_repeat("=", 60));
        $this->io->writeLine(sprintf(
            "ターン: %d/%d | ヒント使用: %d回",
            $this->state->getTurns(),
            $this->config->getMaxTurns(),
            $this->state->getHintsGiven()
        ));
        
        $currentLocation = $this->config->getLocationName(
            $this->state->getPlayerPosition()
        );
        $this->io->writeLine("現在の位置: {$currentLocation}");
        
        // 位置の視覚的表示
        $this->io->write("\n位置: ");
        for ($i = $this->config->minPosition; $i <= $this->config->maxPosition; $i++) {
            $marker = $i === $this->state->getPlayerPosition() ? "🧍" : "　";
            $this->io->write("[{$marker}] ");
        }
        $this->io->writeLine("\n");
        
        // 残りターン警告
        $remainingTurns = $this->config->getMaxTurns() - $this->state->getTurns();
        if ($remainingTurns <= 3) {
            $this->io->writeLine("⚠️  残り{$remainingTurns}ターンです！");
        }
    }
    
    /**
     * ステータス表示
     */
    private function displayStatus(): void {
        $this->io->writeLine("\n" . str_repeat("-", 60));
        $this->io->writeLine("📊 現在のステータス:");
        $this->io->writeLine("  ターン数: {$this->state->getTurns()}");
        $this->io->writeLine("  ヒント使用: {$this->state->getHintsGiven()}回");
        $this->io->writeLine("  移動履歴: " . count($this->state->getMoveHistory()) . "回");
        
        $estimatedScore = $this->scoreCalculator->calculate($this->state);
        if ($estimatedScore > 0) {
            $this->io->writeLine("  推定スコア: {$estimatedScore}点");
        }
        
        $this->io->writeLine(str_repeat("-", 60) . "\n");
    }
    
    /**
     * ゲームの保存
     */
    private function saveGame(): void {
        try {
            $data = [
                'state' => $this->state->toArray(),
                'config' => [
                    'difficulty' => $this->config->difficulty->value,
                ],
                'saved_at' => date('Y-m-d H:i:s'),
            ];
            
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new GameException("データのシリアライズに失敗しました");
            }
            
            if (file_put_contents($this->saveFile, $json) === false) {
                throw new GameException("ファイルの保存に失敗しました");
            }
            
            $this->io->writeLine("✅ ゲームを保存しました");
        } catch (Exception $e) {
            $this->io->writeLine("❌ 保存エラー: " . $e->getMessage());
        }
    }
    
    /**
     * ゲームの読み込み
     */
    public function loadGame(): bool {
        if (!file_exists($this->saveFile)) {
            return false;
        }
        
        try {
            $json = file_get_contents($this->saveFile);
            if ($json === false) {
                throw new GameException("ファイルの読み込みに失敗しました");
            }
            
            $data = json_decode($json, true);
            if (!is_array($data)) {
                throw new GameException("データの解析に失敗しました");
            }

            // 修正前は $data['state'] の存在を確認せず渡していた。
            // セーブファイルが壊れている / 古い形式だと
            // Undefined array key のうえ fromArray(null) で TypeError になる。
            if (!isset($data['state']) || !is_array($data['state'])) {
                throw new GameException("セーブデータの形式が不正です");
            }

            $this->state = GameState::fromArray($data['state']);
            $this->io->writeLine("✅ セーブデータを読み込みました");
            return true;
        } catch (Exception $e) {
            $this->io->writeLine("❌ 読み込みエラー: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ゲーム結果の表示
     */
    private function displayResult(): void {
        $this->io->writeLine("\n" . str_repeat("=", 60));
        
        if ($this->state->isCaught()) {
            $score = $this->scoreCalculator->calculate($this->state);
            $rating = $this->scoreCalculator->getRating($score);
            
            $this->io->writeLine("🎉 おめでとうございます！");
            $this->io->writeLine("ハチ公を {$this->state->getTurns()} ターンで見つけました！");
            $this->io->writeLine("ヒント使用回数: {$this->state->getHintsGiven()} 回");
            $this->io->writeLine("スコア: {$score} 点");
            $this->io->writeLine("評価: {$rating}");
            
            // 統計記録
            $this->statistics->recordGame($this->state, $score);
        } else {
            $hachikoLocation = $this->config->getLocationName(
                $this->state->getHachikoPosition()
            );
            $this->io->writeLine("😢 残念！ハチ公を見つけられませんでした。");
            $this->io->writeLine("ハチ公は「{$hachikoLocation}」にいました。");
            $this->io->writeLine("また挑戦してみてください！");
            
            $this->statistics->recordGame($this->state, 0);
        }
        
        $this->io->writeLine("\n" . $this->statistics->display());
        $this->io->writeLine(str_repeat("=", 60));
    }
    
    /**
     * ヘルプ表示
     */
    private function displayHelp(): void {
        $this->io->writeLine("\n📖 コマンド一覧:");
        foreach (GameCommand::cases() as $command) {
            $this->io->writeLine("  {$command->value}: {$command->getDescription()}");
        }
        $this->io->writeLine("");
    }
    
    /**
     * メインゲームループ
     */
    public function play(): void {
        $this->io->writeLine("🐕 ハチ公探しゲームへようこそ！");
        $this->io->writeLine("渋谷駅周辺でハチ公を探して捕まえてください。");
        $this->io->writeLine("難易度: " . $this->config->difficulty->getDescription());
        $this->displayHelp();
        
        // セーブデータの確認
        if (file_exists($this->saveFile)) {
            $this->io->write("セーブデータが見つかりました。続きからプレイしますか？ (y/n): ");
            $response = strtolower(trim(fgets(STDIN) ?: ''));
            if ($response === 'y' || $response === 'yes') {
                $this->loadGame();
            }
        }
        
        while (!$this->state->isCaught() && 
               $this->state->getTurns() < $this->config->getMaxTurns()) {
            
            try {
                $this->displayGameState();
                $input = $this->io->readLine("コマンド: ");
                
                $command = GameCommand::fromString($input);
                
                if ($command === null) {
                    $this->io->writeLine("❌ 無効なコマンドです。'hint'でヘルプを表示");
                    continue;
                }
                
                // コマンド処理
                switch ($command) {
                    case GameCommand::QUIT:
                        $this->io->writeLine("👋 ゲームを終了します。");
                        return;
                        
                    case GameCommand::HINT:
                        $this->giveHint();
                        continue 2;
                        
                    case GameCommand::STATUS:
                        $this->displayStatus();
                        continue 2;
                        
                    case GameCommand::SAVE:
                        $this->saveGame();
                        continue 2;
                        
                    case GameCommand::LEFT:
                    case GameCommand::RIGHT:
                        $moved = $this->movePlayer($command);
                        break;
                }
                
                if ($moved) {
                    $this->state->incrementTurns();
                    
                    // プレイヤー移動後の捕獲チェック
                    if ($this->checkCatch()) {
                        $this->state->setCaught(true);
                        break;
                    }
                    
                    // ハチ公の移動
                    $this->moveHachiko();
                    
                    // ハチ公移動後の捕獲チェック
                    if ($this->checkCatch()) {
                        $this->state->setCaught(true);
                        $this->io->writeLine("🐕 ハチ公があなたのところにやってきました！");
                        break;
                    }
                }
                
            } catch (InvalidMoveException $e) {
                $this->io->writeLine("⚠️  " . $e->getMessage());
            } catch (GameException $e) {
                $this->io->writeLine("❌ エラー: " . $e->getMessage());
            }
        }
        
        $this->displayResult();
        
        // セーブファイルの削除
        if (file_exists($this->saveFile)) {
            unlink($this->saveFile);
        }
    }
}

/**
 * ゲームファクトリー
 */
class GameFactory {
    public static function create(Difficulty $difficulty = Difficulty::NORMAL): HachikoGame {
        $config = new GameConfig(difficulty: $difficulty);
        return new HachikoGame($config);
    }
    
    public static function selectDifficulty(IOInterface $io): Difficulty {
        $io->writeLine("\n難易度を選択してください:");
        $io->writeLine("1. " . Difficulty::EASY->getDescription());
        $io->writeLine("2. " . Difficulty::NORMAL->getDescription());
        $io->writeLine("3. " . Difficulty::HARD->getDescription());
        
        $choice = $io->readLine("選択 (1-3): ");
        
        return match($choice) {
            '1' => Difficulty::EASY,
            '3' => Difficulty::HARD,
            default => Difficulty::NORMAL,
        };
    }
}

// ========================================
// メイン実行部
// ========================================
//
// このファイルを require しただけでゲームが始まらないようガードする。
//
// 修正前はここがファイルスコープに置かれていたため、
// クラスを使いたいだけの require でも対話ゲームが起動し、
// 標準入力が無い環境では
//
//     🚨 予期しないエラーが発生しました: 入力の読み取りに失敗しました
//
// を出して exit(1) していた。テストから読み込むことができない。
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    try {
        $io = new ConsoleIO();
        $difficulty = GameFactory::selectDifficulty($io);
        $game = GameFactory::create($difficulty);
        $game->play();

    } catch (Exception $e) {
        // スタックトレースにはファイルパスと引数が含まれる。
        // CLI ゲームなので致命的ではないが、既定では出さない。
        echo "🚨 予期しないエラーが発生しました: " . $e->getMessage() . PHP_EOL;
        if (getenv('HACHI_DEBUG') === '1') {
            echo "スタックトレース: " . $e->getTraceAsString() . PHP_EOL;
        }
        exit(1);
    }
}
