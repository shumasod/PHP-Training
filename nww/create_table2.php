use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File; // ファイル存在確認用

class ImportSqlData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SQLファイルのパスを取得
        $filePath = storage_path('app/import.sql');

        // ファイルの存在確認
        if (!File::exists($filePath)) {
            throw new \Exception("SQLファイルが見つかりません: " . $filePath);
        }

        // ファイルの内容を取得
        $sql = File::get($filePath);

        // トランザクションを使用してSQLを実行
        DB::transaction(function () use ($sql) {
            DB::unprepared($sql); // SQLの実行
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 何をロールバックするか定義する
        // 例: インポートしたデータを削除するSQLを追加
        // ここで対応するダウン用のSQLを定義するか、手動で処理するか判断
    }
}
