<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mypage;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use App\Services\OptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * その他オプションのキャンセル処理。
 *
 * 元は以下の 3 ファイルに、クラス外の断片としてほぼ同じコードが
 * 貼り付けられていた:
 *   - Laravel/Mailservice.php
 *   - Laravel/Mailservice.php1
 *   - mailService.php
 *
 * いずれも <?php 開始タグがなく、Markdown のコードフェンス (```) と
 * 全角引用符 (‘ ’) が混入していて PHP としては読み込めなかった。
 * ここに 1 つへ統合し、実行できる形に直した。
 */
class OptionCancelController extends Controller
{
    public function __construct(
        private readonly OptionService $optionService,
        private readonly MailService $mailService,
    ) {
    }

    /**
     * @param array{option_id: int} $inputs
     */
    public function cancel(array $inputs): RedirectResponse
    {
        $user = auth()->user();

        $result = $this->optionService->cancel($user, $inputs['option_id']);

        if (!$result) {
            return $this->redirectError(
                message: 'その他オプションキャンセルに失敗しました',
                route: 'mypage.option.index',
            );
        }

        // メール送信
        try {
            $this->mailService->sendOptionCancelledMail(
                user: $user,
                apply_option_ids: [],
                cancel_option_ids: [$inputs['option_id']],
            );
        } catch (Throwable $e) {
            // メール送信の失敗でキャンセル処理自体を巻き戻さない（ユーザー体験を優先）。
            // ただし握りつぶさず、追跡できるようログには必ず残す。
            //
            // 例外メッセージとスタックトレースは内部情報なのでログのみに出し、
            // 画面へは返さない。
            Log::error('オプションキャンセルメール送信エラー', [
                'user_id'   => $user->id,
                'option_id' => $inputs['option_id'],
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }

        // 成功であればオプション申し込み画面に遷移する
        return $this->redirectSuccess(
            message: 'その他オプションキャンセルに成功しました。',
            route: 'mypage.option.index',
        );
    }
}
