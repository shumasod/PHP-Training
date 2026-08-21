<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// 投稿
//
// 修正前は auth ミドルウェアが付いていなかった。
//
//     Route::get('post/create', [PostController::class, 'create']);
//     Route::post('post', [PostController::class, 'store'])->name('post.store');
//
// PostController::store() は
//
//     $validated['user_id'] = auth()->id();
//
// としているが、未ログインだと auth()->id() は null を返す。
// つまり誰でも投稿でき、user_id が null のレコードが作られる
// （posts.user_id に NOT NULL 制約があれば SQLSTATE エラー、
//   無ければ投稿者不明のレコードが残る）。
//
// また index() が定義されているのに一覧のルートが無く、
// メソッドに到達できなかったので追加した。
Route::middleware('auth')->group(function () {
    Route::get('post', [PostController::class, 'index'])->name('post.index');
    Route::get('post/create', [PostController::class, 'create'])->name('post.create');
    Route::post('post', [PostController::class, 'store'])->name('post.store');
});

require __DIR__.'/auth.php';
