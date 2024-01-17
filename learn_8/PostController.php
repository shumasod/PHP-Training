<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
// CHAPTER8でGate追加
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function create() {
        return view('post.create');
    }

    public function store(Request $request) {
        // CHAPTER8でGate追加
        Gate::authorize('test');

        $validated = $request->validate([
            'title' => 'required|max:20',
            'body' => 'required|max:400',
            ]);

        $validated['user_id'] = auth()->id();
        $post = Post::create($validated);
        $request->session()->flash('message', '保存しました');
        return back();
    }

    public function index() {
        $posts=Post::all();
        return view('post.index', compact('posts'));
    }
}
