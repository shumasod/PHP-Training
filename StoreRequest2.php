use App\Http\Requests\FormRequestTrait; // FormRequestTrait.php のパスに応じて変更してください

class StoreRequest extends FormRequest
{
    use FormRequestTrait;

    public function rules()
    {
        // 画面Aの場合
        if ($this->route()->getName() == 'screen_a_route_name') {
            return $this->rulesForScreenA();
        }
        // 画面Bの場合
        elseif ($this->route()->getName() == 'screen_b_route_name') {
            return $this->rulesForScreenB();
        }
        // 他の画面の場合、デフォルトルールを返す
        else {
            return [];
        }
    }
}
