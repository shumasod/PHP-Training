<?php
use PHPUnit\Framework\TestCase;

// テスト対象のコードをインポート
require_once 'hachiko_game.php'; // オリジナルコードを含むファイル

class HachikoGameTest extends TestCase
{
    /**
     * ゲーム初期化のテスト
     */
    public function testInitializeGame()
    {
        $game = initializeGame();
        
        // ゲーム状態の構造をテスト
        $this->assertArrayHasKey('player_position', $game);
        $this->assertArrayHasKey('hachiko_position', $game);
        $this->assertArrayHasKey('turns', $game);
        $this->assertArrayHasKey('caught', $game);
        
        // 初期値の検証
        $this->assertEquals(0, $game['player_position']);
        $this->assertTrue($game['hachiko_position'] >= 0 && $game['hachiko_position'] <= 4);
        $this->assertEquals(0, $game['turns']);
        $this->assertFalse($game['caught']);
    }
    
    /**
     * 位置表示機能のテスト
     * 
     * @dataProvider locationProvider
     */
    public function testDisplayLocation($position, $expectedLocation)
    {
        $this->assertEquals($expectedLocation, displayLocation($position));
    }
    
    /**
     * 位置とロケーション名のデータプロバイダー
     */
    public function locationProvider()
    {
        return [
            [0, '渋谷駅前'],
            [1, 'センター街'],
            [2, '宮益坂'],
            [3, 'ハチ公前広場'],
            [4, '道玄坂']
        ];
    }
    
    /**
     * プレイヤー移動のテスト - 左方向
     */
    public function testMovePlayerLeft()
    {
        $game = [
            'player_position' => 2,
            'hachiko_position' => 0,
            'turns' => 0,
            'caught' => false
        ];
        
        movePlayer('left', $game);
        
        $this->assertEquals(1, $game['player_position']);
        $this->assertEquals(1, $game['turns']);
    }
    
    /**
     * プレイヤー移動のテスト - 右方向
     */
    public function testMovePlayerRight()
    {
        $game = [
            'player_position' => 2,
            'hachiko_position' => 0,
            'turns' => 0,
            'caught' => false
        ];
        
        movePlayer('right', $game);
        
        $this->assertEquals(3, $game['player_position']);
        $this->assertEquals(1, $game['turns']);
    }
    
    /**
     * 境界値のテスト - 左端での左移動
     */
    public function testMovePlayerLeftBoundary()
    {
        $game = [
            'player_position' => 0,
            'hachiko_position' => 4,
            'turns' => 0,
            'caught' => false
        ];
        
        movePlayer('left', $game);
        
        // 左端なので位置は変わらない
        $this->assertEquals(0, $game['player_position']);
        // ターンは増える
        $this->assertEquals(1, $game['turns']);
    }
    
    /**
     * 境界値のテスト - 右端での右移動
     */
    public function testMovePlayerRightBoundary()
    {
        $game = [
            'player_position' => 4,
            'hachiko_position' => 0,
            'turns' => 0,
            'caught' => false
        ];
        
        movePlayer('right', $game);
        
        // 右端なので位置は変わらない
        $this->assertEquals(4, $game['player_position']);
        // ターンは増える
        $this->assertEquals(1, $game['turns']);
    }
    
    /**
     * 捕獲チェックのテスト - 捕獲成功
     */
    public function testCheckCatchSuccess()
    {
        $game = [
            'player_position' => 3,
            'hachiko_position' => 3,
            'turns' => 5,
            'caught' => false
        ];
        
        checkCatch($game);
        
        $this->assertTrue($game['caught']);
    }
    
    /**
     * 捕獲チェックのテスト - 捕獲失敗
     */
    public function testCheckCatchFailure()
    {
        $game = [
            'player_position' => 2,
            'hachiko_position' => 3,
            'turns' => 5,
            'caught' => false
        ];
        
        checkCatch($game);
        
        $this->assertFalse($game['caught']);
    }
    
    /**
     * ゲームシナリオの統合テスト
     * 実際のplayGame()関数ではなく、内部ロジックを検証
     */
    public function testGameScenario()
    {
        // ハチ公の位置を固定してテスト可能にする
        $game = [
            'player_position' => 0,
            'hachiko_position' => 2, // 宮益坂に固定
            'turns' => 0,
            'caught' => false
        ];
        
        // プレイヤーを右に移動（センター街へ）
        movePlayer('right', $game);
        checkCatch($game);
        $this->assertEquals(1, $game['player_position']);
        $this->assertFalse($game['caught']);
        
        // さらに右に移動（宮益坂へ）
        movePlayer('right', $game);
        checkCatch($game);
        $this->assertEquals(2, $game['player_position']);
        $this->assertTrue($game['caught']);
        $this->assertEquals(2, $game['turns']);
    }
}
