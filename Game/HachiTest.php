<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Hachi.php';

/**
 * Game/Hachi.php のテスト。
 *
 * 修正前の hachi-test.php は、存在しない hachiko_game.php を require し、
 * 存在しない手続き型 API (initializeGame() / displayLocation() /
 * movePlayer() / checkCatch()) をテストしていたため一度も実行できなかった。
 * 実際の Hachi.php はクラスベースなので、その API に合わせて書き直した。
 */
final class HachiTest extends TestCase
{
    // -----------------------------------------------------------------
    // GameConfig
    // -----------------------------------------------------------------

    public function testLocationNames(): void
    {
        $config = new GameConfig();

        $this->assertSame('渋谷駅前', $config->getLocationName(0));
        $this->assertSame('センター街', $config->getLocationName(1));
        $this->assertSame('宮益坂', $config->getLocationName(2));
        $this->assertSame('ハチ公前広場', $config->getLocationName(3));
        $this->assertSame('道玄坂', $config->getLocationName(4));
    }

    public function testGetLocationNameRejectsOutOfRange(): void
    {
        $config = new GameConfig();

        $this->expectException(InvalidArgumentException::class);
        $config->getLocationName(5);
    }

    #[DataProvider('positionProvider')]
    public function testIsValidPosition(int $position, bool $expected): void
    {
        $this->assertSame($expected, (new GameConfig())->isValidPosition($position));
    }

    /**
     * @return list<array{int, bool}>
     */
    public static function positionProvider(): array
    {
        return [
            [-1, false],
            [0, true],
            [2, true],
            [4, true],
            [5, false],
        ];
    }

    // -----------------------------------------------------------------
    // Difficulty
    // -----------------------------------------------------------------

    public function testDifficultyAffectsTurnsAndMoveChance(): void
    {
        // 難しいほどターン数は少なく、ハチ公の移動率は高い
        $this->assertGreaterThan(
            Difficulty::HARD->getMaxTurns(),
            Difficulty::EASY->getMaxTurns()
        );
        $this->assertLessThan(
            Difficulty::HARD->getMoveChance(),
            Difficulty::EASY->getMoveChance()
        );
    }

    // -----------------------------------------------------------------
    // GameState
    // -----------------------------------------------------------------

    public function testInitialState(): void
    {
        $state = new GameState(2, 0);

        $this->assertSame(2, $state->getPlayerPosition());
        $this->assertSame(0, $state->getHachikoPosition());
        $this->assertSame(0, $state->getTurns());
        $this->assertFalse($state->isCaught());
        $this->assertSame(0, $state->getHintsGiven());
        $this->assertNull($state->getPreviousHachikoPosition());
        $this->assertSame([], $state->getMoveHistory());
    }

    public function testSetHachikoPositionRecordsPreviousPosition(): void
    {
        $state = new GameState(2, 0);

        $state->setHachikoPosition(1);

        $this->assertSame(1, $state->getHachikoPosition());
        $this->assertSame(0, $state->getPreviousHachikoPosition());
    }

    public function testIncrementTurns(): void
    {
        $state = new GameState(2, 0);

        $state->incrementTurns();
        $state->incrementTurns();

        $this->assertSame(2, $state->getTurns());
    }

    public function testMoveHistoryRecordsTurnAndPosition(): void
    {
        $state = new GameState(2, 0);

        $state->incrementTurns();
        $state->setPlayerPosition(3);
        $state->addMoveToHistory('right');

        $history = $state->getMoveHistory();

        $this->assertCount(1, $history);
        $this->assertSame(1, $history[0]['turn']);
        $this->assertSame('right', $history[0]['move']);
        $this->assertSame(3, $history[0]['player_position']);
    }

    /**
     * toArray() と fromArray() が往復すること。
     * セーブ／ロードはこの 2 つに依存しているので、
     * 片方だけ変更したときに気付けるようにしておく。
     */
    public function testToArrayFromArrayRoundTrip(): void
    {
        $original = new GameState(1, 4);
        $original->incrementTurns();
        $original->setHachikoPosition(3);
        $original->incrementHints();
        $original->addMoveToHistory('left');
        $original->setCaught(true);

        $restored = GameState::fromArray($original->toArray());

        $this->assertSame($original->getPlayerPosition(), $restored->getPlayerPosition());
        $this->assertSame($original->getHachikoPosition(), $restored->getHachikoPosition());
        $this->assertSame($original->getTurns(), $restored->getTurns());
        $this->assertSame($original->isCaught(), $restored->isCaught());
        $this->assertSame($original->getHintsGiven(), $restored->getHintsGiven());
        $this->assertSame(
            $original->getPreviousHachikoPosition(),
            $restored->getPreviousHachikoPosition()
        );
        $this->assertSame($original->getMoveHistory(), $restored->getMoveHistory());
    }

    public function testFromArrayFallsBackToDefaults(): void
    {
        // 古い形式のセーブデータを想定し、キーが欠けていても落ちないこと
        $state = GameState::fromArray([]);

        $this->assertSame(2, $state->getPlayerPosition());
        $this->assertSame(0, $state->getHachikoPosition());
        $this->assertSame(0, $state->getTurns());
        $this->assertFalse($state->isCaught());
    }
}
