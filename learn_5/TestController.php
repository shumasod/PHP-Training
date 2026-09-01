<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent とクエリビルダの書き方を並べて確認するためのコントローラ。
 */
class TestController extends Controller
{
    public function index(): View
    {
        // 修正前はこのメソッドの先頭に dd('test'); が置かれていた。
        //
        // dd() は出力して exit するため、後続の行は一度も実行されない。
        // つまり下のクエリも return view(...) もすべて到達不能で、
        // このコントローラは「test という文字列を出して終わる」だけだった。
        //
        // メソッドの末尾にも dd($values, ...) があり、こちらも
        // return view(...) へ到達させないようにしていた。
        //
        // dd() は本番でも動く。モデルの全属性やクエリ結果がそのまま
        // ブラウザへ出るため、消し忘れるとデータの中身が漏れる。
        // 両方とも削除した。

        // Eloquent(エロクアント)
        //
        // all() は全件をメモリに載せる。件数が増える前提なら
        // paginate() を使う。
        $values = Test::all();

        $count = Test::count();

        // findOrFail(1) は id=1 が無いと ModelNotFoundException になり
        // 404 が返る。ここでは「見つからなければ 404」で構わない想定。
        $first = Test::findOrFail(1);

        $whereBBB = Test::where('text', '=', 'bbb')->get();

        // クエリビルダ
        // Eloquent と違ってモデルを経由しないので、
        // アクセサやキャストは適用されない。
        $queryBuilder = DB::table('tests')
            ->where('text', '=', 'bbb')
            ->select('id', 'text')
            ->first();

        return view('tests.test', compact(
            'values',
            'count',
            'first',
            'whereBBB',
            'queryBuilder',
        ));
    }
}
