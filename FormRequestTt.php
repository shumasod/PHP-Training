<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * email を配列で受け取る場合の FormRequest の例。
 *
 * 元は rules() メソッドだけがクラス外・PHP 開始タグなしで置かれており、
 * PHP としては読み込めない状態だった。
 */
class FormRequestTt extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 既存のルール

            // emailのルール
            'email' => [
                'nullable',
                'array',
            ],
            // 各要素がメールアドレスであること、および重複しないことを確認
            // ※ 'each:email' というルールは Laravel に存在しない。
            //    配列の各要素へルールを当てるにはドット記法を使う。
            'email.*' => [
                'email',
                // ログインユーザー自身のレコードは重複判定から除外する
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
        ];
    }
}
