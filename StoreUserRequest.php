<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ユーザー作成リクエストクラス
 *
 * 修正前は StoreRequest.php / StoreRequest1.php / StoreRequest2.php の
 * 3 ファイルがすべて `class StoreRequest` を定義しており、同時に
 * 読み込むと Fatal error になった。役割の分かる名前へ変更している。
 *
 * FormRequestTrait は同じ名前空間にあるので use は不要
 * （修正前の `use App\Http\Requests\FormRequestTrait;` は
 *   自分自身の名前空間を指す冗長な import だった）。
 */
class StoreUserRequest extends FormRequest
{
    use FormRequestTrait;
    
    /**
     * リクエストの認可判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // 認可ロジックを実装（例：ユーザー作成権限がある場合のみtrue）
        //
        // 修正前は auth()->user()->can(...) と書いていたため、
        // 未ログインだと auth()->user() が null になり
        //   Call to a member function can() on null
        // で 500 になっていた。$this->user() は null 安全に扱う。
        //
        // また User がインポートされておらず、User::class は
        // App\Http\Requests\User という存在しないクラスを指していた。
        return $this->user()?->can('create', User::class) ?? false;
    }
    
    /**
     * バリデーションルールを定義
     *
     * @return array
     */
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
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
    /**
     * @return array<string, string>
     */
    public function messages(): array
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
    /**
     * @return array<string, string>
     */
    public function attributes(): array
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
    protected function prepareForValidation(): void
    {
        // 入力データの前処理を行う（例：メールアドレスを小文字に変換）
        //
        // email が送られてこないと strtolower(null) になり、
        // PHP 8.1 以降 Deprecated になる。存在確認を入れる。
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower((string) $this->input('email')),
            ]);
        }
    }
}
