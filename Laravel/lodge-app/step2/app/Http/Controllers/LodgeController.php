<?php

namespace App\Http\Controllers;

use App\Models\Lodge;
use Illuminate\Http\Request;

class LodgeController extends Controller
{
    /**
     * 書き込み系の操作にログインを要求する。
     *
     * 修正前は step2 全体に認証も認可も一つも無かった。
     *
     *     step1: auth=0 authorize=0   (index / show だけなので読み取り専用)
     *     step2: auth=0 authorize=0   ← create / store を足したのに素通し
     *     step3: auth=4 authorize=17
     *
     * step2 で POST /lodges を追加した時点で、
     * ログインしていない誰でも lodges にレコードを作れる状態になっていた。
     * 公開フォームとして意図したものではなく、
     * step3 では同じコントローラに auth が入っていることから、
     * 単に付け忘れである。
     *
     * 一覧と詳細は公開のまま、作成だけを認証必須にする
     * (step3 の LodgeController と同じ形)。
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index()
    {
        $lodges = Lodge::with('members')->get();
        return view('lodges.index', compact('lodges'));
    }

    public function show(Lodge $lodge)
    {
        $lodge->load('members', 'events');
        return view('lodges.show', compact('lodge'));
    }

    public function create()
    {
        return view('lodges.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'location' => 'required|max:255',
            'founded_year' => 'required|integer|min:1717',
        ]);

        Lodge::create($validated);

        return redirect()->route('lodges.index')->with('success', 'Lodge created successfully.');
    }
}
