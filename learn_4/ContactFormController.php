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

        // find() ではなく findOrFail() を使う。
        //
        // find() は該当レコードが無いと null を返す。修正前は戻り値を
        // 確かめずに後続へ渡していたため、存在しない ID を指定されると
        //
        //   - show():    CheckFormService::checkGender(null) に null が渡る
        //   - edit():    ビューに null が渡り、$contact->name で致命的エラー
        //   - update():  $contact->name = ... で
        //                "Attempt to assign property on null"
        //   - destroy(): $contact->delete() で
        //                "Call to a member function delete() on null"
        //
        // となる。いずれも 500 になり、認可の失敗や「存在しない」を
        // サーバ側の不具合として返してしまう。
        // findOrFail() なら 404 になる。
        $contact = ContactForm::findOrFail($id);

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

        // findOrFail にしている理由は show() のコメントを参照。
        $contact = ContactForm::findOrFail($id);

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

        // 更新時も入力を検証する。
        //
        // 修正前は素の Request から $request->name などを直接代入しており、
        // 検証が一切無かった。store() は StoreContactRequest を通るので、
        // 「作成はチェックされるが更新は何でも通る」状態だった。
        //
        // とくに url は、StoreContactRequest がわざわざ
        //     'url' => ['nullable', 'url:http,https', 'max:255']
        // とスキームを http/https に限定しているのに、
        // update() 経由なら javascript: スキームをそのまま保存できた。
        // 保存された値は画面にリンクとして出るため、
        // 作成時の対策を素通りして保存型 XSS を仕込める経路になっていた。
        //
        // StoreContactRequest をそのまま使わない理由:
        // あちらには 'caution' => ['required', 'accepted'] が含まれる。
        // これは送信フォームの同意チェックボックスで、
        // 管理者の編集画面には存在しない項目なので、更新では外す。
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'gender' => ['required', 'boolean'],
            'age' => ['required', 'integer', 'between:1,6'],
            'contact' => ['required', 'string', 'max:200'],
        ]);

        // findOrFail にしている理由は show() のコメントを参照。
        $contact = ContactForm::findOrFail($id);

        $contact->name = $validated['name'];
        $contact->title = $validated['title'];
        $contact->email = $validated['email'];
        $contact->url = $validated['url'] ?? null;
        $contact->gender = $validated['gender'];
        $contact->age = $validated['age'];
        $contact->contact = $validated['contact'];
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

        // findOrFail にしている理由は show() のコメントを参照。
        $contact = ContactForm::findOrFail($id);
        $contact->delete();

        return to_route('contacts.index');
        
    }
}
