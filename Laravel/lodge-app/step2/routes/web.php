<?php

use App\Http\Controllers\LodgeController;

// 一覧と詳細は公開。
//
// '/lodges/create' は '/lodges/{lodge}' より先に登録する。
// 逆順だと /lodges/create が {lodge} = "create" として拾われ、
// ルートモデルバインディングが 404 を返す。
Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');

// 作成はログイン必須。
//
// 修正前はこの 2 行に認証が付いておらず、コントローラ側にも
// 認証・認可が一つも無かったため、ログインしていない誰でも
// POST /lodges で lodges にレコードを作れた。
//
// LodgeController 側にも $this->middleware('auth') を入れてあるが、
// ルート定義だけを読んだときにも認証の有無が分かるよう、
// ここにも明示しておく (二重に効いても問題は無い)。
Route::middleware('auth')->group(function (): void {
    Route::get('/lodges/create', [LodgeController::class, 'create'])->name('lodges.create');
    Route::post('/lodges', [LodgeController::class, 'store'])->name('lodges.store');
});

Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');
