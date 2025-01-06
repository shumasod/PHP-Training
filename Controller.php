use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseOperationException extends \Exception {}

try {
    $maxAttempts = 3;
    $attempt = 1;

    do {
        try {
            DB::beginTransaction();
            
            $result = DB::table('table_name')->update(['column' => 'new_value']);
            
            if (!$result) {
                throw new DatabaseOperationException("更新が失敗しました");
            }
            
            DB::commit();
            return $result;
            
        } catch (\PDOException $e) {
            DB::rollBack();
            
            if (str_contains($e->getMessage(), 'deadlock')) {
                if ($attempt === $maxAttempts) {
                    Log::error("デッドロックの解決に失敗しました", [
                        'attempt' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    throw new DatabaseOperationException("デッドロックが解決できませんでした");
                }
                Log::warning("デッドロックを検出。リトライします。", ['attempt' => $attempt]);
                sleep(1 * $attempt); // 指数バックオフ
                $attempt++;
                continue;
            }
            throw $e;
        }
    } while ($attempt <= $maxAttempts);
    
} catch (\Exception $e) {
    DB::rollBack();
    Log::error("トランザクション処理でエラーが発生しました", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    throw $e;
}
