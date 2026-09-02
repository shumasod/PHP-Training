<?php

declare(strict_types=1);

use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mail Routes
|--------------------------------------------------------------------------
*/

// 修正前は次の 1 行だけだった。
//
//     Route::post('', [MailController::class, 'send']);
//
// 問題が 3 つある。
//
// 1. use が無い
//    このリポジトリの他のルートファイル (learn/web.php, nww/web.php,
//    learn_7/web.php, learn_8/web.php) はいずれも use を書いている。
//    ここだけ無いため MailController は解決できない
//    (Route は Laravel が用意するグローバルエイリアスで解決できるが、
//     MailController は App\Http\Controllers\ 配下なので解決できない)。
//
// 2. URI が空文字
//    Route::post('') はサイトのルート (/) への POST になる。
//    メール送信のエンドポイントとしては意図が読み取れない。
//
// 3. レート制限が無い
//    MailController::send() はメールを送る。
//    制限が無いと、1 リクエストで 1 通送れる状態を無制限に叩ける。
//    自分のドメインの評判が落ち、SMTP プロバイダの上限にも当たる。
Route::middleware(['throttle:mail'])
    ->post('/mail/send', [MailController::class, 'send'])
    ->name('mail.send');

// throttle:mail は RouteServiceProvider で定義する。
//
//     RateLimiter::for('mail', function (Request $request) {
//         return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
//     });
//
// 宛先を利用者が指定できる作りである点については
// Mailble/MailController.php のコメントを参照。
// 本来は宛先をサーバ側で決めるべきで、その場合はこのルートにも
// 'auth' を付けて送信者を特定できるようにする。
