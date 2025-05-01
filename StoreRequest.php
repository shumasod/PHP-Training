<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\FormRequestTrait;

/**
 * ユーザー作成リクエストクラス
 */
class StoreRequest extends FormRequest
{
    use FormRequestTrait;
    
    /**
     * リクエストの認可判定
     *
     * @return bool
     */
    public function authorize()
    {
        // 認可ロジックを実装（例：ユーザー作成権限がある場合のみtrue）
        return auth()->user()->can('create', User::class);
        // または単純に全ユーザーに許可する場合
        // return true;
    }
    
    /**
     * バリデーションルールを定義
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => $this->uniqueEmailRule(), // FormRequestTraitのメソッドを呼び出す
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:user,admin,editor'],
        ];
    }
    
    /**
     * バリデーションメッセージをカスタマイズ
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required' => 'メールアドレスは必須項目です。',
            'email.email' => '有効なメールアドレス形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'name.required' => '名前は必須項目です。',
            'password.required' => 'パスワードは必須項目です。',
            'password.min' => 'パスワードは8文字以上で設定してください。',
            'password.confirmed' => 'パスワードと確認用パスワードが一致しません。',
            'role.required' => '役割は必須項目です。',
            'role.in' => '選択された役割は無効です。',
        ];
    }
    
    /**
     * バリデーション属性名をカスタマイズ
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'メールアドレス',
            'name' => '名前',
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード（確認用）',
            'role' => '役割',
        ];
    }
    
    /**
     * バリデーション前の処理
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // 入力データの前処理を行う（例：メールアドレスを小文字に変換）
        $this->merge([
            'email' => strtolower($this->input('email')),
        ]);
    }
}
