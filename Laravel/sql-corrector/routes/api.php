<?php

declare(strict_types=1);

use App\Http\Controllers\SqlCorrectorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SQL Syntax Corrector API Routes
|--------------------------------------------------------------------------
|
| 元は sey.php 内のコメントとして書かれていた定義を実ファイル化したもの。
| routes/api.php にそのまま追記して使う。
|
*/

// 認証とレート制限について
//
// 修正前はこの 2 本に middleware が一つも付いておらず、
// 誰でも無認証・無制限に呼べる POST エンドポイントだった。
//
// 1 リクエストあたりの処理コストは小さい。
// checkKeywordSpelling() は語数 × sqlKeywords (36 件) だけ
// levenshtein() を呼ぶが、上限いっぱいの入力でも
//
//     入力 9999 文字 / 語数 2000 / levenshtein 呼び出し 72000 回 / 9.2 ms
//
// なので、1 リクエストでサーバを潰せるような重さではない。
// つまりこれは DoS の話ではない。
//
// それでも制限を入れる理由は 2 つ。
//
//   - 無認証だと、誰がどれだけ使ったのかを後から追えない。
//     SqlCorrectorController は失敗時に error_id を発行してログに
//     残すが、紐づく利用者が分からないと調査の手がかりにならない。
//   - 制限が無いと、呼び出し回数の上限が
//     「相手が投げるのをやめるまで」になる。
//
// api ミドルウェアグループには既定で throttle が入っている構成も
// あるが、このファイルは routes/api.php に追記して使う抜粋なので、
// 追記先の設定に依存せずこのルートだけで効くよう明示する。
//
// 'auth:sanctum' を付けていない理由:
// このエンドポイントは「SQL 文字列を整形して返す」だけで、
// データを読んだり書いたりしない。社内の開発者向けツールとして
// 公開したいケースも考えられるため、認証の要否は運用の判断に残し、
// ここでは回数制限だけを入れる。
// 公開範囲を絞る場合は下のコメントのように auth を足す。
Route::prefix('sql-corrector')
    ->middleware('throttle:30,1')   // 1 分あたり 30 回 / IP
    // ->middleware(['auth:sanctum', 'throttle:30,1'])  // 認証も要求する場合
    ->group(function (): void {
        Route::post('/correct', [SqlCorrectorController::class, 'correctSql']);
        Route::post('/format', [SqlCorrectorController::class, 'formatSql']);
    });
