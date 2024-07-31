<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PachinkoController extends Controller
{
    public function index()
    {
        $initialCredits = 1000;
        return view('pachinko', ['credits' => $initialCredits]);
    }

    public function launchBall(Request $request)
    {
        // ボール発射のロジックをここに実装
        // 実際のゲームでは、このメソッドでクレジットの減算や
        // スコアの計算を行い、結果をJSONで返します
        $launchPower = $request->input('launchPower');
        $credits = $request->input('credits');

        if ($credits >= 10) {
            $credits -= 10;
            $score = rand(0, 500); // 仮のスコア計算
            $newCredits = $credits + $score;

            return response()->json([
                'success' => true,
                'score' => $score,
                'newCredits' => $newCredits
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Not enough credits'
            ]);
        }
    }
}
