<?php

$commonVarible = "共通の変数です";

function commonTest(){
    echo '外部ファイルの関数です';
}

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'throttle:api'])->group(function () {
    
    // クレジット情報を取得
    Route::get('/credits', function (Request $request) {
        // セッションまたはデータベースからクレジットを取得
        $credits = Session::get('user_credits', 1000); // デフォルト1000
        
        return response()->json([
            'credits' => $credits,
            'session_id' => Session::getId()
        ]);
    });

    // クレジットを更新
    Route::post('/update-credits', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'credits' => 'required|integer|min:0|max:999999'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credit amount',
                'errors' => $validator->errors()
            ], 400);
        }

        $credits = $request->input('credits');
        
        // セッションに保存（本格的なアプリケーションではデータベースを使用）
        Session::put('user_credits', $credits);
        
        // ゲーム履歴をログに保存（オプション）
        $gameLog = Session::get('game_log', []);
        $gameLog[] = [
            'timestamp' => now()->toISOString(),
            'credits' => $credits,
            'action' => 'update_credits'
        ];
        Session::put('game_log', array_slice($gameLog, -100)); // 最新100件を保持
        
        return response()->json([
            'success' => true,
            'newCredits' => $credits,
            'message' => 'Credits updated successfully'
        ]);
    });

    // ゲームスコアを記録
    Route::post('/record-score', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'score' => 'required|integer|min:0',
            'multiplier' => 'required|integer|min:1',
            'pocket_index' => 'required|integer|min:0|max:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $score = $request->input('score');
        $multiplier = $request->input('multiplier');
        $pocketIndex = $request->input('pocket_index');
        
        // ゲーム統計を更新
        $stats = Session::get('game_stats', [
            'total_games' => 0,
            'total_score' => 0,
            'highest_score' => 0,
            'pocket_hits' => [0, 0, 0, 0, 0]
        ]);
        
        $stats['total_games']++;
        $stats['total_score'] += $score;
        $stats['highest_score'] = max($stats['highest_score'], $score);
        $stats['pocket_hits'][$pocketIndex]++;
        
        Session::put('game_stats', $stats);
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    });

    // ゲーム統計を取得
    Route::get('/game-stats', function () {
        $stats = Session::get('game_stats', [
            'total_games' => 0,
            'total_score' => 0,
            'highest_score' => 0,
            'pocket_hits' => [0, 0, 0, 0, 0]
        ]);
        
        $gameLog = Session::get('game_log', []);
        
        return response()->json([
            'stats' => $stats,
            'recent_games' => array_slice($gameLog, -10) // 最新10件
        ]);
    });

    // ゲームをリセット
    Route::post('/reset-game', function () {
        Session::forget(['user_credits', 'game_stats', 'game_log']);
        Session::put('user_credits', 1000); // 初期クレジットにリセット
        
        return response()->json([
            'success' => true,
            'message' => 'Game reset successfully',
            'credits' => 1000
        ]);
    });

    // デイリーボーナス（簡単な実装例）
    Route::post('/daily-bonus', function () {
        $lastBonus = Session::get('last_bonus_date');
        $today = now()->format('Y-m-d');
        
        if ($lastBonus !== $today) {
            $bonusAmount = rand(50, 200); // ランダムボーナス
            $currentCredits = Session::get('user_credits', 1000);
            $newCredits = $currentCredits + $bonusAmount;
            
            Session::put('user_credits', $newCredits);
            Session::put('last_bonus_date', $today);
            
            return response()->json([
                'success' => true,
                'bonus_received' => true,
                'bonus_amount' => $bonusAmount,
                'new_credits' => $newCredits
            ]);
        }
        
        return response()->json([
            'success' => true,
            'bonus_received' => false,
            'message' => 'Daily bonus already claimed today'
        ]);
    });
});

// 認証が必要なルート（オプション）
Route::middleware(['auth:sanctum'])->group(function () {
    
    // ユーザープロフィール更新
    Route::post('/profile', function (Request $request) {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'nickname' => 'string|max:20',
            'favorite_pocket' => 'integer|min:0|max:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        // プロフィール更新ロジック
        // ...

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    });
});