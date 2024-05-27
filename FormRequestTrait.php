namespace App\Http\Requests;

trait FormRequestTrait
{
    /**
     * nullableでemailバリデーションルールを作成する
     *
     * @return array
     */
    public function nullableEmailRule()
    {
        return [
            'nullable',
            'email',
        ];
    }
}
