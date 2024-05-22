use App\Http\Requests\FormRequestTrait;

class StoreRequest extends FormRequest
{
    use FormRequestTrait;

    public function rules()
    {
        return [
            'email' => $this->nullableEmailRule(),
        ];
    }
}
