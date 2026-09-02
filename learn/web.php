<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ContactFormController;
// use App\Http\Controllers\ShopController;  // 実体が無いためコメントアウト

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

Route::get('tests/test', [ TestController::class, 'index' ]);

// ShopController はこのリポジトリに存在しない。
// このままアクセスすると Target class [App\Http\Controllers\ShopController]
// does not exist. で 500 になる。
// 参考として残すためコメントアウトする（use も同様）。
// Route::get('shops', [ ShopController::class, 'index' ]);

// Route::resource('contacts', ContactFormController::class);



// show / edit / update / destroy は ContactFormController の docblock 上
// 「管理者用」とされているが、middleware は ['auth'] だけ。
// ログインさえしていれば ID を差し替えるだけで他人の問い合わせ内容を
// 閲覧・編集・削除できる。nww/web.php と同じ問題 (PR #66 参照)。
Route::prefix('contacts')
->middleware(['auth'])
->controller(ContactFormController::class)
->name('contacts.')
->group(function(){
    Route::get('/', 'index')->name('index');

    // '/create' は '/{id}' より先に登録する。
    // 逆順だと /contacts/create が {id} = "create" として拾われる。
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');

    // {id} を数値に限定する。
    // 制約が無いと /contacts/abc のようなリクエストもコントローラまで届く。
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');

    // 更新・削除は PUT / DELETE を使う。
    //
    // 修正前はどちらも POST だった。動きはするが、
    //   - 同じ URI (/contacts/{id}) に store 相当と update 相当が同居する
    //   - nww/web.php は同じコントローラに PUT / DELETE を割り当てており、
    //     どちらが正なのか分からない
    // という状態だった。nww 側に合わせる。
    Route::put('/{id}', 'update')->whereNumber('id')->name('update');
    Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
});


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';
