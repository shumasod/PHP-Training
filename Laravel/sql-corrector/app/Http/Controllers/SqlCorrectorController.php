<?php

namespace App\Http\Controllers;

use App\Services\SqlSyntaxCorrectorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            return response()->json([
                'success' => false,
                'error' => 'SQL解析中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'error' => 'SQL整形中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
