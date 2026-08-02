<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 未ログインの場合の扱い。
        //
        // 修正前は auth()->user()->role を直接参照していたため、
        // 未ログインでこのミドルウェアを通ると
        //   Call to a member function ... on null
        // で 500 になっていた。
        //
        // 500 は「サーバ側の不具合」を意味するステータスなので、
        // 認可の失敗をこれで返すと監視のノイズになるうえ、
        // 攻撃者に内部エラーの発生を伝えることにもなる。
        // ログインページへ誘導する。
        if ($user === null) {
            return redirect()->route('login');
        }

        // 役割の比較。
        //
        // 修正前は == による緩い比較だった。
        // role が数値やそれに準ずる値だった場合、
        // PHP 8 未満では 0 == 'admin' が true になる。
        // PHP 8 で挙動は変わったが、意図を明示するため === を使う。
        if ($user->role === 'admin') {
            return $next($request);
        }

        // 権限不足。
        //
        // 修正前は dashboard へリダイレクトしていた。
        // これはユーザーには親切だが、
        //   - 拒否がアクセスログ上 302 として残るため検知しづらい
        //   - API クライアントからは成功と区別しにくい
        // ので 403 を返す。
        abort(Response::HTTP_FORBIDDEN);
    }
}
