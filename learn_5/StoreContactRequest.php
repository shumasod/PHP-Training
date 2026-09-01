<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255'], //テーブル毎に1件ならunique:contact_forms

            // url ルールは filter_var(FILTER_VALIDATE_URL) を使うだけで、
            // スキームを見ていない。PHP 8.4 で確認した挙動:
            //
            //     javascript:alert(1)                    弾く
            //     javascript://example.com/%0Aalert(1)   通る  ← XSS として成立
            //     file:///etc/passwd                     通る
            //     ftp://example.com/x                    通る
            //
            // 画面にリンクとして出す値なので http/https に限定する。
            // また nullable は先に書く（他のルールより先に評価させる）。
            'url' => ['nullable', 'url:http,https', 'max:255'],

            'gender' => ['required', 'boolean'],

            // 修正前は ['required'] だけで、型も範囲も見ていなかった。
            // ContactFormFactory が 1〜6 を入れているので年齢そのものではなく
            // 区分コード。想定する値だけを許可する。
            'age' => ['required', 'integer', 'between:1,6'],

            'contact' => ['required', 'string', 'max:200'],
            'caution' => ['required', 'accepted']
        ];

    }
}
