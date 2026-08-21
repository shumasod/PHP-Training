<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function create() {
        return view('post.create');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'title' => 'required|max:20',
            'body' => 'required|max:400',
            ]);

        // CHAPTER7で追加
        $validated['user_id'] = auth()->id();

        $post = Post::create($validated);
        $request->session()->flash('message', '保存しました');
        return back();
    }

    // CHAPTER7で追加
    public function index() {
        // postsテーブルのすべてのデータを取得
        //
        // with('user') を付けて投稿者をまとめて読み込む。
        //
        // ビュー (post/index.blade.php) は 1 件ごとに $post->user->name を
        // 参照している。with が無いと、投稿 1 件につき users への
        // SELECT が 1 回ずつ走る（N+1 問題）。
        // 100 件表示すれば 1 + 100 = 101 クエリになる。
        //
        // Post::all() は全件をメモリに載せるので、
        // 件数が増える前提なら paginate() に変えること。
        $posts = Post::with('user')->latest()->get();

        // postsテーブルのログインユーザーのデータを取得
        // $posts=Post::where('user_id', auth()->id())->get();

        // postsテーブルのログインユーザー以外のデータを取得
        // $posts=Post::where('user_id', '!=', auth()->id())->get();

        // postsテーブルの2022/12/2以降のデータを取得
        // $posts=Post::whereDate('created_at', '>=', '2022-12-02')->get();

        // postsテーブルのuser_idが1で2022/12/2以降のデータを取得
        // $posts=Post::where('user_id', 1)->whereDate('created_at', '>=', '2022-12-02')->get();

        // postsテーブルのすべてのデータを新しい順に取得
        // $posts=Post::orderBy('created_at', 'desc')->get();

        return view('post.index', compact('posts'));
    }
}
