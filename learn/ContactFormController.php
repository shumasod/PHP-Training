<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;
use App\Models\Contact;

class ContactFormController extends Controller
{
    /**
     * お問い合わせフォームを表示
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('contacts.index');
    }

    /**
     * お問い合わせフォームの作成画面を表示
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * お問い合わせデータを保存
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // バリデーションルール
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'お名前は必須です。',
            'name.max' => 'お名前は255文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'subject.required' => '件名は必須です。',
            'subject.max' => '件名は255文字以内で入力してください。',
            'message.required' => 'メッセージは必須です。',
            'message.max' => 'メッセージは2000文字以内で入力してください。',
        ]);

        // バリデーションエラーの場合
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // データベースに保存
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'pending', // 未対応
            ]);

            // 管理者にメール送信
            Mail::to(config('mail.admin_email'))->send(new ContactMail($contact));

            // ユーザーに自動返信メール送信
            Mail::to($request->email)->send(new ContactMail($contact, true));

            return redirect()->route('contacts.complete')
                ->with('success', 'お問い合わせを受け付けました。ありがとうございます。');

        } catch (\Exception $e) {
            \Log::error('Contact form submission error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'お問い合わせの送信に失敗しました。しばらく時間をおいて再度お試しください。')
                ->withInput();
        }
    }

    /**
     * 送信完了画面を表示
     *
     * @return \Illuminate\Http\Response
     */
    public function complete()
    {
        return view('contacts.complete');
    }

    /**
     * 特定のお問い合わせを表示（管理者用）
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contact = Contact::findOrFail($id);
        return view('contacts.show', compact('contact'));
    }

    /**
     * お問い合わせの編集画面を表示（管理者用）
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $contact = Contact::findOrFail($id);
        return view('contacts.edit', compact('contact'));
    }

    /**
     * お問い合わせ情報を更新（管理者用）
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        // 管理者用のバリデーションルール
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'status.required' => 'ステータスは必須です。',
            'status.in' => '正しいステータスを選択してください。',
            'admin_notes.max' => '管理者メモは1000文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $contact->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ]);

            return redirect()->route('contacts.show', $id)
                ->with('success', 'お問い合わせ情報を更新しました。');

        } catch (\Exception $e) {
            \Log::error('Contact update error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', '更新に失敗しました。')
                ->withInput();
        }
    }

    /**
     * お問い合わせを削除（管理者用）
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();

            return redirect()->route('contacts.index')
                ->with('success', 'お問い合わせを削除しました。');

        } catch (\Exception $e) {
            \Log::error('Contact deletion error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', '削除に失敗しました。');
        }
    }

    /**
     * お問い合わせ一覧を表示（管理者用）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function admin(Request $request)
    {
        $query = Contact::query();

        // ステータスでフィルタリング
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 検索
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')
                         ->paginate(20);

        return view('contacts.admin', compact('contacts'));
    }
}