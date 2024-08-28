use Illuminate\Support\Facades\DB;

class YourController extends Controller
{
    public function yourControllerMethod()
    {
        try {
            // トランザクションを開始
            DB::beginTransaction();

            // データベースクエリや変更を実行
            DB::table('your_table')->update(['column' => 'value']);

            // もし何かしらのエラーが発生した場合、例外をスローしてトランザクションをロールバック
            if (/* エラーが発生した場合の条件 */) {
                throw new \Exception("エラーが発生しました。トランザクションをロールバックします。");
            }

            // すべてが成功した場合、トランザクションをコミット
            DB::commit();

            // 成功時の処理などを追加
            return response()->json(['success' => true, 'message' => '成功しました。']);

        } catch (\Exception $e) {
            // エラーが発生した場合、トランザクションをロールバック
            DB::rollBack();

            // エラー時の処理などを追加
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
