<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route:: get('tests/test', [ TestController::class, 'index' ]);

// Route::resource('contacts', ContactFormController::class);

// お問い合わせ
//
// show / edit / update / destroy は ContactFormController の docblock 上
// 「管理者用」とされているが、このグループの middleware は ['auth'] だけ。
// ログインさえしていれば、ID を差し替えるだけで他人の問い合わせ内容
// （氏名・メールアドレス・本文）を閲覧・編集・削除できる。
//
// 管理者だけに絞るなら 'admin' ミドルウェアや Policy を足し、
// 一般ユーザーにも自分の分だけ見せるなら
// コントローラ側で所有者チェックを入れること。
Route::group(['prefix' => 'contacts', 'middleware' => ['auth'], 'as' => 'contacts.'], function () {
    Route::get('/', [ContactFormController::class, 'index'])->name('index');

    // '/create' は '/{id}' より先に登録する。
    // 逆順だと /contacts/create が {id} = "create" として拾われる。
    Route::get('/create', [ContactFormController::class, 'create'])->name('create');
    Route::post('/', [ContactFormController::class, 'store'])->name('store');

    // {id} を数値に限定する。
    // 制約が無いと /contacts/abc のようなリクエストも
    // コントローラまで届いてしまう。
    Route::get('/{id}', [ContactFormController::class, 'show'])->whereNumber('id')->name('show');
    Route::get('/{id}/edit', [ContactFormController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [ContactFormController::class, 'update'])->whereNumber('id')->name('update');
    Route::delete('/{id}', [ContactFormController::class, 'destroy'])->whereNumber('id')->name('destroy');
});

Route::get('/', function () {
    return view('welcome');
});

// ダッシュボード
//
// 修正前は /dashboard が 2 回定義されていた。
//
//     Route::get('/dashboard', [CustomerController::class, 'index'])->name('dashboard');
//     ...
//     Route::middleware(['auth'])->group(function () {
//         Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');
//     });
//
// Laravel は同じ URI に対して「後から登録した方」を採用するので、
// CustomerController::index() は一度も呼ばれない状態だった。
// ルート名 'dashboard' も重複しており、route('dashboard') は
// 後者を指していた。
//
// 顧客一覧を出すのが本来の意図なのでコントローラ側を残す。
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/search', [CustomerController::class, 'search'])->name('dashboard.search');
});

require __DIR__.'/auth.php';
