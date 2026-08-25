<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

// 初期クレジット。4 箇所に 1000 が直書きされていたのをまとめた。
const INITIAL_CREDITS = 1000;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'throttle:api'])->group(function () {
    
    // クレジット情報を取得
    Route::get('/credits', function (Request $request) {
        // セッションまたはデータベースからクレジットを取得
        $credits = Session::get('user_credits', INITIAL_CREDITS);

        // session_id はレスポンスに含めない。
        //
        // セッション ID は Cookie で HttpOnly / Secure を付けて運ぶもので、
        // JSON の本文に入れるとその保護が意味を失う。
        // ページに XSS が 1 つでもあれば JavaScript から読めてしまうし、
        // レスポンスがプロキシや CDN のログに残れば、そこからも漏れる。
        return response()->json([
            'credits' => $credits,
        ]);
    });

    // クレジットを更新
    //
    // 【重要】このエンドポイントはクライアントが送ってきた残高を
    // そのまま採用している。バリデーションは「0〜999999 の整数か」しか
    // 見ていないので、
    //
    //     curl -X POST /api/update-credits -d 'credits=999999'
    //
    // を投げるだけで残高を最大にできる。ゲームを一度もプレイせずに済む。
    //
    // 正しくは、サーバが残高を持ち、クライアントからは
    // 「何が起きたか」だけを受け取って、サーバ側で計算する。
    //
    //     POST /api/launch   -> サーバが LAUNCH_COST を引き、
    //                           抽選もサーバで行って結果を返す
    //
    // クライアントに「いくらになったか」を決めさせてはいけない。
    // 学習用サンプルとしてこの形を残しているが、
    // 実際のアプリでこの実装を使わないこと。
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

        // ここも /update-credits と同じで、スコアをクライアントが決めている。
        // 統計・ランキングに使うなら、サーバ側で抽選して算出した値を
        // 記録しなければ意味がない。
        $score = (int) $request->input('score');
        $pocketIndex = (int) $request->input('pocket_index');

        // $multiplier は受け取っているが一度も使われていなかったため
        // 参照をやめた（バリデーションは互換のため残している）。
        
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
        Session::put('user_credits', INITIAL_CREDITS); // 初期クレジットにリセット
        
        return response()->json([
            'success' => true,
            'message' => 'Game reset successfully',
            'credits' => INITIAL_CREDITS
        ]);
    });

    // デイリーボーナス（簡単な実装例）
    Route::post('/daily-bonus', function () {
        $today = now()->format('Y-m-d');

        // 受け取り済みの日付を「先に」書いてから付与額を決める。
        //
        // 修正前は
        //     if ($lastBonus !== $today) { ... Session::put('last_bonus_date', $today); }
        // という順序だった。判定してから書き込むまでに隙があるため、
        // 同時に 2 本のリクエストを投げると両方が「まだ受け取っていない」と
        // 判定し、ボーナスを 2 回受け取れる。
        //
        // なお、セッションはリクエストごとに読み書きされるだけで
        // 排他制御を持たない。本来は
        //   - ユーザー単位の行ロック (SELECT ... FOR UPDATE)
        //   - (user_id, bonus_date) への UNIQUE 制約
        // のようにデータベース側で一意性を保証する。
        // ここでは学習用サンプルとして順序だけを直している。
        if (Session::get('last_bonus_date') === $today) {
            return response()->json([
                'success' => true,
                'bonus_received' => false,
                'message' => 'Daily bonus already claimed today'
            ]);
        }

        Session::put('last_bonus_date', $today);

        // rand() ではなく random_int() を使う。
        // rand() は線形合同法で、いくつか出力を観測すると次の値を予測できる。
        // ボーナス額程度なら実害は小さいが、
        // 抽選や当たり判定に同じ書き方を持ち込むと結果を先読みされる。
        $bonusAmount = random_int(50, 200);

        $newCredits = Session::get('user_credits', INITIAL_CREDITS) + $bonusAmount;
        Session::put('user_credits', $newCredits);

        return response()->json([
            'success' => true,
            'bonus_received' => true,
            'bonus_amount' => $bonusAmount,
            'new_credits' => $newCredits
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