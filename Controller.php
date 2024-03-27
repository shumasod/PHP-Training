  // DB::beginTransaction();基本使用文法


use Illuminate\Support\Facades\DB;

// ...

try {
    // トランザクションを開始
    DB::beginTransaction();

    // データベースクエリや変更を実行
    DB::table('table_name')->update(['column' => 'value']);

    // もし何かしらのエラーが発生した場合、例外をスローしてトランザクションをロールバック
    if (/* エラーが発生した場合の条件 */) {
        throw new \Exception("エラーが発生しました。トランザクションをロールバックします。");
    }

    // すべてが成功した場合、トランザクションをコミット
    DB::commit();
} catch (\Exception $e) {
    // エラーが発生した場合、トランザクションをロールバック
    DB::rollBack();

    // 例外を処理するかログに記録するなど、適切な対応を行う
    // 例外の詳細は $e->getMessage() で取得できます
    // 例: Log::error($e->getMessage());
}
