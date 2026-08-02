<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\NotificationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        // 入力の検証。
        //
        // 修正前は $request->title などを検証せずそのまま使っていた。
        // recipient_email に至っては、任意のアドレスへメールを送れるため
        // このエンドポイントがオープンリレーとして悪用できる状態だった。
        // 少なくとも形式の検証と長さの上限は必須。
        //
        // 宛先を利用者が自由に指定できてよいかは要件次第だが、
        // 通常は「ログイン中のユーザー宛」「管理者宛」のように
        // サーバ側で決めるべき値。
        $validated = $request->validate([
            'recipient_email' => ['required', 'email:rfc', 'max:255'],
            'title'           => ['required', 'string', 'max:255'],
            'body'            => ['required', 'string', 'max:5000'],
            'name'            => ['required', 'string', 'max:100'],
        ]);

        try {
            $mailData = [
                'title' => $validated['title'],
                'body'  => $validated['body'],
                'name'  => $validated['name'],
            ];

            Mail::to($validated['recipient_email'])
                ->send(new NotificationMail($mailData));

            return response()->json([
                'message' => 'メールを送信しました。'
            ], 200);

        } catch (Throwable $e) {
            // 例外メッセージをレスポンスに含めない。
            //
            // 修正前は 'error' => $e->getMessage() を返していた。
            // SMTP の例外メッセージにはホスト名・ポート・認証方式・
            // 認証失敗の理由などが含まれるため、
            // 攻撃者にメール基盤の構成を教えることになる。
            //
            // 詳細はログに残し、レスポンスには追跡用の ID だけ返す。
            $errorId = bin2hex(random_bytes(8));

            Log::error('メール送信に失敗しました', [
                'error_id' => $errorId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message'  => 'メール送信に失敗しました。',
                'error_id' => $errorId,
            ], 500);
        }
    }
}
