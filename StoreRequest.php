use App\Http\Requests\FormRequestTrait; // FormRequestTrait.php のパスに応じて変更してください

class StoreRequest extends FormRequest
{
    use FormRequestTrait;

    public function rules()
    {
        return [
            'email' => $this->uniqueEmailRule(), // FormRequestTrait.php 内のメソッドを呼び出す
        ];
    }
}
