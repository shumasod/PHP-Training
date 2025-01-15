<?php

namespace App\Http\Controllers;

use App\Mail\NotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function send(Request $request)
    {
        try {
            $mailData = [
                'title' => $request->title,
                'body' => $request->body,
                'name' => $request->name
            ];

            Mail::to($request->recipient_email)
                ->send(new NotificationMail($mailData));

            return response()->json([
                'message' => 'メールを送信しました。'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'メール送信に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
