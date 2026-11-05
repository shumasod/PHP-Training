<?php

declare(strict_types=1);

use App\Http\Controllers\MailController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
// レートリミッタ 'mail' の定義。
//
// ここを「RouteServiceProvider に書く」とコメントで案内するだけでは
// 足りなかった。throttle:mail という名前のリミッタが実際には
// どこにも定義されていなかったため、このルートは常に 429 を返していた。
//
// Laravel の ThrottleRequests は、与えられた名前のリミッタが
// 登録されていないと、その名前を「最大試行回数」として解釈しようとする
// (Illuminate\Routing\Middleware\ThrottleRequests::resolveMaxAttempts)。
//
//     php -r 'var_dump((int) "mail");'
//     int(0)
//
// 上限 0 回なので 1 通目のリクエストから TooManyRequestsHttpException になる。
// 時間が経っても回復しない。つまりメール送信は恒久的に不可能だった。
//
// 本番の Laravel アプリでは App\Providers\RouteServiceProvider の boot() に
// 置くのが定石だが、このディレクトリは章ごとの抜粋なので、
// ルートファイル単体で動くようにここで登録する。
// routes/*.php は RouteServiceProvider から読み込まれるため、
// リクエスト処理が始まる前に登録が完了する。
RateLimiter::for('mail', function (Request $request): Limit {
    // 利用者を特定できるならユーザー ID、できないなら IP で数える。
    // IP だけで数えると NAT 配下の利用者が共倒れになるため、
    // ログイン済みのリクエストは ID を優先する。
    return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
});

Route::middleware(['throttle:mail'])
    ->post('/mail/send', [MailController::class, 'send'])
    ->name('mail.send');

// 宛先を利用者が指定できる作りである点については
// Mailble/MailController.php のコメントを参照。
// 本来は宛先をサーバ側で決めるべきで、その場合はこのルートにも
// 'auth' を付けて送信者を特定できるようにする。
