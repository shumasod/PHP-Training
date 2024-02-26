
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
        $search = $request->input('search')

        $customers = Customer::where('name', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->get();

        return view('dashboard', compact('customers'));
    }
}


##TestControllers

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class TestController extends Controller
{
<<<<<<< HEAD
public function test() {
=======
    public function test();{ 
>>>>>>> 2d7621099f058b089451b2e28d314f3b5c2daab8
        $users = user::all();
        return view('test',compact('users'));
        }
    

    
        <?php

        namespace App\Http\Controllers;
        
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\DB;
        use App\Models\User;
        use App\Models\Test; // 追加
        
        class TestController extends Controller
        {
            public function test() {
                // クエリビルダ
                $queryBuilder = DB::table('tests')->where('text', '=', 'bbb')
                ->select('id', 'text')
                ->first();
        
                dd($queryBuilder);
        
                return view('tests.test');
            }
        }
        