namespace App\Http\Requests;

trait FormRequestTrait
{
    /**
     * 画面A用のバリデーションルールを作成する
     *
     * @return array
     */
    public function rulesForScreenA()
    {
        return [
            'email' => [
                'nullable',
                'email',
            ],
            // 他の画面A用のルールを追加できます
        ];
    }

    /**
     * 画面B用のバリデーションルールを作成する
     *
     * @return array
     */
    public function rulesForScreenB()
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
            // 他の画面B用のルールを追加できます
        ];
    }
}
