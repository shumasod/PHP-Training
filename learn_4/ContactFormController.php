<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactForm;
use App\Services\CheckFormService;
use App\Http\Requests\StoreContactRequest;
use App\Http\Services\HogeService;
use Illuminate\Support\Facades\Gate;

class ContactFormController extends Controller
{
    public function __construct(
        // public HogeService $hogeService
    )
    {

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // 残っている問題 (この PR では直していない)
        //
        // 一覧は全員の問い合わせの id / name / title / created_at を返す。
        // show などと同じく、本来は管理者だけが見るべき情報である。
        //
        // ただし store() が完了後にここへリダイレクトしているため、
        // 一般利用者を弾くと問い合わせ送信が 403 で終わってしまう。
        // 直すには「送信完了画面を別に用意する」という画面遷移の変更が
        // 必要で、認可の修正とは別の判断になるので分けてある。
        // $contacts = Contactform::select('id', 'name', 'title', 'created_at')
        // ->get();

        // ペジネーション対応
        // $contacts = Contactform::select('id', 'name', 'title', 'created_at')
        // ->paginate(20);

        // $this->hogeService->fnc1();

        // 検索対応
        $search = $request->search;
        $query = ContactForm::search($search);
        
        $contacts = $query->select('id', 'name', 'title', 'created_at')
        ->paginate(20);


        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContactRequest $request)
    {
        // dd($request, $request->name);

        ContactForm::create([
            'name' => $request->name,
            'title' => $request->title,
            'email' => $request->email,
            'url' => $request->url,
            'gender' => $request->gender,
            'age' => $request->age,
            'contact' => $request->contact,
        ]);

        return to_route('contacts.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // 管理者だけが他人の問い合わせを見られる。
        //
        // 修正前はこのチェックが無く、ルートの middleware も ['auth']
        // だけだったため、ログインしていれば URL の ID を差し替えるだけで
        // 他人の問い合わせ内容を読めた。
        Gate::authorize('manage-contacts');

        $contact = ContactForm::find($id);

        $gender = CheckFormService::checkGender($contact);
        
        $age = CheckFormService::checkAge($contact);

        return view('contacts.show', 
        compact('contact', 'gender', 'age'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Gate::authorize('manage-contacts');

        $contact = ContactForm::find($id);

        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('manage-contacts');

        $contact = ContactForm::find($id);
        $contact->name = $request->name;
        $contact->title = $request->title;
        $contact->email = $request->email;
        $contact->url = $request->url;
        $contact->gender = $request->gender;
        $contact->age = $request->age;
        $contact->contact = $request->contact;
        $contact->save();

        return to_route('contacts.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Gate::authorize('manage-contacts');

        $contact = ContactForm::find($id);
        $contact->delete();

        return to_route('contacts.index');
        
    }
}
