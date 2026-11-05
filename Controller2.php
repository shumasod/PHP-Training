<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DB::transaction() の使い方の見本。
 *
 * 修正前は 2 箇所に問題があった。
 *
 * 1. WHERE の無い UPDATE
 *
 *        DB::table('your_table')->update(['column' => 'value']);
 *
 *    Laravel のクエリビルダは where を付けなければ条件なしの
 *    UPDATE を組み立てるので、テーブルの全行が書き換わる。
 *    見本としてこの形を載せると、読んだ人がそのまま真似る。
 *    対象を絞る where を必ず書く形にした。
 *
 * 2. 例外メッセージをレスポンスに返していた
 *
 *        return response()->json(['success' => false,
 *            'message' => $e->getMessage()], 500);
 *
 *    例外メッセージには SQL 本文、テーブル名、カラム名が入りうる。
 *    詳細はログに残し、画面には固定の文言だけを返す。
 */
class YourController extends Controller
{
    public function yourControllerMethod()
    {
        // 自前で beginTransaction / commit / rollBack を書くのではなく
        // DB::transaction() にクロージャを渡す形にした。
        //
        // 修正前の形だと、クロージャの外で例外が起きた場合や
        // catch し忘れた場合にトランザクションが開いたまま残る。
        // DB::transaction() なら、例外が出れば必ずロールバックし、
        // 最後まで通ればコミットする。
        try {
            DB::transaction(function (): void {
                // 対象を where で絞る。
                // where を付けないと全行が更新される。
                DB::table('your_table')
                    ->where('id', 1)
                    ->update(['column' => 'value']);

                // 実際の条件に置き換えて使う。
                // ここで例外を投げればロールバックされる。
                $hasError = false;
                if ($hasError) {
                    throw new \RuntimeException('エラーが発生しました。');
                }
            });

            return response()->json(['success' => true, 'message' => '成功しました。']);

        } catch (\Throwable $e) {
            // 例外メッセージをレスポンスに含めない。
            //
            // 詳細はログに残し、利用者には固定の文言と
            // 追跡用の ID だけを返す。
            $errorId = bin2hex(random_bytes(8));

            Log::error('トランザクション処理に失敗しました', [
                'error_id' => $errorId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success'  => false,
                'message'  => '処理に失敗しました。',
                'error_id' => $errorId,
            ], 500);
        }
    }
}
