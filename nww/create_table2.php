use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ImportSqlData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // テーブル作成のマイグレーションの後にSQLファイルをインポートする
        $filePath = storage_path('app/import.sql');
        DB::unprepared(file_get_contents($filePath));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ロールバック時の処理を記述する場合はここに追加します
    }
}
