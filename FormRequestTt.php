use Illuminate\Validation\Rule;

// ...

public function rules()
{
    return [
        // 既存のルール

        // emailのルール
        'email' => [
            'nullable',
            'array',
            // emailが配列であることを確認し、各要素がメールアドレスであることを確認
            'each:email',
            // emailアドレスが重複しないことを確認
            Rule::unique('users', 'email')->ignore(auth()->id()), // ログインユーザーを無視する場合
        ],
    ];
}
