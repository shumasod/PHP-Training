<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\RedirectResponse;

/**
 * FormRequest の使い方。
 *
 * 引数の型に FormRequest のサブクラスを書いておくと、
 * コントローラのメソッドに入る「前」に Laravel が
 * authorize() と rules() を実行する。
 * 失敗した場合はここへ到達しない（403 / 422 が返る）。
 *
 * 元のファイル名は FormRequset.php と綴りが誤っており、
 * 中身も空のスタブだった。
 */
class StoreUserController extends Controller
{
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // validated() は rules() で定義した項目「だけ」を返す。
        //
        // ここで $request->all() を使うと、ルールに無いフィールドまで
        // そのまま流れてしまう（マスアサインメント）。
        // 例えば role をフォームに紛れ込ませて admin に昇格させる、
        // といったことが可能になる。
        $validated = $request->validated();

        // User::create($validated); などの保存処理

        return redirect()
            ->route('users.index')
            ->with('success', 'ユーザーを登録しました。');
    }
}
