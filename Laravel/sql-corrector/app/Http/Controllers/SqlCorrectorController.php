<?php

namespace App\Http\Controllers;

use App\Services\SqlSyntaxCorrectorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SqlCorrectorController extends Controller
{
    private SqlSyntaxCorrectorService $sqlCorrector;

    public function __construct(SqlSyntaxCorrectorService $sqlCorrector)
    {
        $this->sqlCorrector = $sqlCorrector;
    }

    /**
     * SQLを解析して修正提案を返す
     */
    public function correctSql(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string|max:10000'
        ]);

        try {
            $result = $this->sqlCorrector->correctSql($request->input('sql'));
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            // 例外メッセージをレスポンスに含めない。
            //
            // 修正前は $e->getMessage() をそのまま返していた。
            // 例外メッセージにはファイルパスや内部のクラス名、
            // ライブラリのバージョンに依存する文言が入りうる。
            // このエンドポイントは外部から任意の SQL 文字列を
            // 投げられるので、意図的に例外を起こさせて
            // 内部構成を少しずつ探る、という使い方ができてしまう。
            //
            // 詳細はログに残し、レスポンスには追跡用の ID だけ返す。
            // (Mailble/MailController.php と同じ方針)
            return $this->failure($e, 'SQL解析中にエラーが発生しました。', 'correctSql');
        }
    }

    /**
     * SQLを整形する
     */
    public function formatSql(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string|max:10000'
        ]);

        try {
            $formattedSql = $this->sqlCorrector->formatSql($request->input('sql'));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original_sql' => $request->input('sql'),
                    'formatted_sql' => $formattedSql
                ]
            ]);
        } catch (\Exception $e) {
            // 理由は correctSql() のコメントを参照。
            return $this->failure($e, 'SQL整形中にエラーが発生しました。', 'formatSql');
        }
    }

    /**
     * 500 を返す際の共通処理。
     *
     * 例外の詳細はログにだけ出し、レスポンスには追跡用の ID を返す。
     * 利用者からの問い合わせと、ログ上の該当行を突き合わせられるように
     * するための ID で、推測しても意味を持たない値にしている。
     */
    private function failure(\Exception $e, string $message, string $action): JsonResponse
    {
        $errorId = bin2hex(random_bytes(8));

        Log::error('SQL corrector failed', [
            'error_id' => $errorId,
            'action'   => $action,
            'error'    => $e->getMessage(),
            'trace'    => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success'  => false,
            'error'    => $message,
            'error_id' => $errorId,
        ], 500);
    }
}
