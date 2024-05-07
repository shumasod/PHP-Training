namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::all();
        return view('dashboard', compact('customers'));
    }

    public function search(Request $request)
    {
        $search = $request->input('search'); // 文法エラーの修正（セミコロン追加）

        $customers = Customer::where('name', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->get();

        return view('dashboard', compact('customers'));
    }
}
