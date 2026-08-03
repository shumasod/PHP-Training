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

// CHAPTER8でミドルウェア追加
//
// 修正前はこのグループの middleware(['auth','admin']) 行だけが
// コメントアウトされ、中のルートは有効なままだった。
// その結果 /post と /post/create が未ログインでも誰でも開ける状態で、
// 管理者向けの投稿一覧が公開されていた。
//
// POST /post も同じグループの外にあり、認証ミドルウェアが付いていない。
// PostController::store() の Gate::authorize('test') が
// 未ログインを弾いてはいるが、認可 (Gate) だけに頼ると
// 誰かが Gate の定義を緩めた瞬間に無認証で書き込めるようになる。
// 認証はミドルウェアで、認可は Gate で、と二段構えにする。
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('post', [PostController::class, 'index']);
    Route::get('post/create', [PostController::class, 'create']);
    Route::post('post', [PostController::class, 'store'])->name('post.store');
});

require __DIR__.'/auth.php';
