<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SqlSyntaxCorrectorService
{
    /**
     * 一般的なSQLエラーパターンと修正方法
     */
    private array $errorPatterns = [
        // SELECT文のエラー
        [
            'pattern' => '/SELECT\s*FROM/i',
            'correction' => 'SELECT * FROM',
            'description' => 'SELECT文にカラム指定が不足しています'
        ],
        [
            'pattern' => '/SELECT\s+\*\s+WHERE/i',
            'correction' => 'SELECT * FROM table_name WHERE',
            'description' => 'FROM句が不足しています'
        ],
        
        // カンマの修正
        [
            'pattern' => '/,\s*FROM/i',
            'correction' => ' FROM',
            'description' => 'FROM句の前に不要なカンマがあります'
        ],
        [
            'pattern' => '/,\s*WHERE/i',
            'correction' => ' WHERE',
            'description' => 'WHERE句の前に不要なカンマがあります'
        ],
        [
            'pattern' => '/,\s*ORDER\s+BY/i',
            'correction' => ' ORDER BY',
            'description' => 'ORDER BY句の前に不要なカンマがあります'
        ],
        
        // 引用符の修正
        [
            'pattern' => '/=\s*([^\'"][^,\s;]+)(?=\s|$|,|;)/i',
            'correction' => "= '$1'",
            'description' => '文字列値に引用符が不足しています'
        ],
        
        // JOIN文の修正
        [
            'pattern' => '/JOIN\s+(\w+)\s+WHERE/i',
            'correction' => 'JOIN $1 ON table1.id = $1.id WHERE',
            'description' => 'JOIN句にON条件が不足しています'
        ],
        
        // GROUP BY後のカラム名修正
        [
            'pattern' => '/GROUP\s+BY\s*$/i',
            'correction' => 'GROUP BY column_name',
            'description' => 'GROUP BY句にカラム名が不足しています'
        ],
        
        // ORDER BY後のカラム名修正
        [
            'pattern' => '/ORDER\s+BY\s*$/i',
            'correction' => 'ORDER BY column_name',
            'description' => 'ORDER BY句にカラム名が不足しています'
        ],
        
        // INSERT文の修正
        [
            'pattern' => '/INSERT\s+INTO\s+(\w+)\s+VALUES\s*$/i',
            'correction' => 'INSERT INTO $1 (column1, column2) VALUES (value1, value2)',
            'description' => 'INSERT文にカラム名と値が不足しています'
        ],
        
        // UPDATE文の修正
        [
            'pattern' => '/UPDATE\s+(\w+)\s+WHERE/i',
            'correction' => 'UPDATE $1 SET column = value WHERE',
            'description' => 'UPDATE文にSET句が不足しています'
        ],
        
        // セミコロンの追加
        [
            'pattern' => '/^(?!.*;$)(.+)$/m',
            'correction' => '$1;',
            'description' => '文末にセミコロンが不足しています'
        ]
    ];

    /**
     * 予約語のスペルチェック
     */
    private array $sqlKeywords = [
        'SELECT', 'FROM', 'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'OUTER',
        'ON', 'GROUP', 'BY', 'ORDER', 'HAVING', 'INSERT', 'INTO', 'VALUES',
        'UPDATE', 'SET', 'DELETE', 'CREATE', 'TABLE', 'ALTER', 'DROP',
        'INDEX', 'PRIMARY', 'KEY', 'FOREIGN', 'REFERENCES', 'NOT', 'NULL',
        'DEFAULT', 'AUTO_INCREMENT', 'UNIQUE', 'DISTINCT', 'LIMIT', 'OFFSET'
    ];

    /**
     * SQLを解析してエラーを検出・修正
     */
    public function correctSql(string $sql): array
    {
        $originalSql = $sql;
        $corrections = [];
        $correctedSql = $sql;

        // 基本的な構文チェック
        $corrections = array_merge($corrections, $this->checkBasicSyntax($sql));
        
        // パターンマッチングによる修正
        foreach ($this->errorPatterns as $pattern) {
            if (preg_match($pattern['pattern'], $correctedSql)) {
                $newSql = preg_replace($pattern['pattern'], $pattern['correction'], $correctedSql);
                if ($newSql !== $correctedSql) {
                    $corrections[] = [
                        'type' => 'pattern_correction',
                        'description' => $pattern['description'],
                        'before' => $correctedSql,
                        'after' => $newSql
                    ];
                    $correctedSql = $newSql;
                }
            }
        }

        // 予約語のスペルチェック
        $corrections = array_merge($corrections, $this->checkKeywordSpelling($correctedSql));
        
        // 括弧のバランスチェック
        $corrections = array_merge($corrections, $this->checkParenthesesBalance($correctedSql));

        return [
            'original_sql' => $originalSql,
            'corrected_sql' => $correctedSql,
            'corrections' => $corrections,
            'has_errors' => !empty($corrections)
        ];
    }

    /**
     * 基本的な構文チェック
     */
    private function checkBasicSyntax(string $sql): array
    {
        $errors = [];

        // 空のSQL
        if (trim($sql) === '') {
            $errors[] = [
                'type' => 'empty_sql',
                'description' => 'SQLが空です',
                'suggestion' => 'SQLクエリを入力してください'
            ];
            return $errors;
        }

        // SELECT文の基本構造チェック
        if (preg_match('/^\s*SELECT/i', $sql)) {
            if (!preg_match('/FROM/i', $sql)) {
                $errors[] = [
                    'type' => 'missing_from',
                    'description' => 'SELECT文にFROM句がありません',
                    'suggestion' => 'FROM テーブル名 を追加してください'
                ];
            }
        }

        // INSERT文の基本構造チェック
        if (preg_match('/^\s*INSERT/i', $sql)) {
            if (!preg_match('/INTO/i', $sql)) {
                $errors[] = [
                    'type' => 'missing_into',
                    'description' => 'INSERT文にINTO句がありません',
                    'suggestion' => 'INTO テーブル名 を追加してください'
                ];
            }
            if (!preg_match('/VALUES/i', $sql)) {
                $errors[] = [
                    'type' => 'missing_values',
                    'description' => 'INSERT文にVALUES句がありません',
                    'suggestion' => 'VALUES (値1, 値2, ...) を追加してください'
                ];
            }
        }

        // UPDATE文の基本構造チェック
        if (preg_match('/^\s*UPDATE/i', $sql)) {
            if (!preg_match('/SET/i', $sql)) {
                $errors[] = [
                    'type' => 'missing_set',
                    'description' => 'UPDATE文にSET句がありません',
                    'suggestion' => 'SET カラム名 = 値 を追加してください'
                ];
            }
        }

        return $errors;
    }

    /**
     * 予約語のスペルチェック
     */
    private function checkKeywordSpelling(string $sql): array
    {
        $errors = [];
        $words = preg_split('/\s+/', $sql);

        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^\w]/', '', strtoupper($word));
            if (strlen($cleanWord) > 2) {
                $suggestion = $this->findSimilarKeyword($cleanWord);
                if ($suggestion && $suggestion !== $cleanWord) {
                    $errors[] = [
                        'type' => 'keyword_spelling',
                        'description' => "'{$word}' は '{$suggestion}' の間違いの可能性があります",
                        'suggestion' => "'{$word}' を '{$suggestion}' に変更してください"
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * 類似のキーワードを検索
     */
    private function findSimilarKeyword(string $word): ?string
    {
        foreach ($this->sqlKeywords as $keyword) {
            if (levenshtein($word, $keyword) <= 2 && strlen($word) >= 3) {
                return $keyword;
            }
        }
        return null;
    }

    /**
     * 括弧のバランスチェック
     */
    private function checkParenthesesBalance(string $sql): array
    {
        $errors = [];
        $openCount = substr_count($sql, '(');
        $closeCount = substr_count($sql, ')');

        if ($openCount !== $closeCount) {
            $errors[] = [
                'type' => 'parentheses_mismatch',
                'description' => '括弧の開始と終了が一致しません',
                'suggestion' => $openCount > $closeCount 
                    ? '閉じ括弧 ) を追加してください' 
                    : '開き括弧 ( を追加してください'
            ];
        }

        return $errors;
    }

    /**
     * SQLを整形する
     */
    public function formatSql(string $sql): string
    {
        // 基本的なSQL整形
        $formatted = $sql;
        
        // キーワードを大文字に
        $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'ON', 'GROUP BY', 'ORDER BY', 
                    'HAVING', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE'];
        
        foreach ($keywords as $keyword) {
            $formatted = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $keyword, $formatted);
        }

        // 改行とインデントの追加
        $formatted = preg_replace('/\bFROM\b/i', "\nFROM", $formatted);
        $formatted = preg_replace('/\bWHERE\b/i', "\nWHERE", $formatted);
        $formatted = preg_replace('/\bJOIN\b/i', "\nJOIN", $formatted);
        $formatted = preg_replace('/\bGROUP BY\b/i', "\nGROUP BY", $formatted);
        $formatted = preg_replace('/\bORDER BY\b/i', "\nORDER BY", $formatted);

        return trim($formatted);
    }
}

// コントローラーファイル
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

// Artisanコマンドファイル
namespace App\Console\Commands;

use App\Services\SqlSyntaxCorrectorService;
use Illuminate\Console\Command;

class SqlCorrectorCommand extends Command
{
    protected $signature = 'sql:correct {sql : 修正するSQL文}';
    protected $description = 'SQLのシンタックスエラーを修正します';

    private SqlSyntaxCorrectorService $sqlCorrector;

    public function __construct(SqlSyntaxCorrectorService $sqlCorrector)
    {
        parent::__construct();
        $this->sqlCorrector = $sqlCorrector;
    }

    public function handle()
    {
        $sql = $this->argument('sql');
        
        $this->info('SQLを解析中...');
        $result = $this->sqlCorrector->correctSql($sql);

        $this->line('');
        $this->info('=== 元のSQL ===');
        $this->line($result['original_sql']);

        if ($result['has_errors']) {
            $this->line('');
            $this->warn('=== 検出されたエラー ===');
            foreach ($result['corrections'] as $correction) {
                $this->error('・' . $correction['description']);
                if (isset($correction['suggestion'])) {
                    $this->line('  提案: ' . $correction['suggestion']);
                }
            }

            $this->line('');
            $this->info('=== 修正されたSQL ===');
            $this->line($result['corrected_sql']);
        } else {
            $this->line('');
            $this->success('エラーは検出されませんでした！');
        }

        return 0;
    }
}

// ルート定義 (routes/api.php に追加)
/*
Route::prefix('sql-corrector')->group(function () {
    Route::post('/correct', [SqlCorrectorController::class, 'correctSql']);
    Route::post('/format', [SqlCorrectorController::class, 'formatSql']);
});
*/

// 使用例とテストケース
class SqlCorrectorExample
{
    public static function examples(): array
    {
        return [
            // エラーのあるSQL例
            'SELECT FROM users',                           // カラム指定なし
            'SELECT * users WHERE id = 1',                // FROM句なし
            'SELECT name, FROM users',                     // 不要なカンマ
            'SELECT * FROM users WHERE name = John',       // 引用符なし
            'INSERT INTO users VALUES',                    // VALUES句不完全
            'UPDATE users WHERE id = 1',                   // SET句なし
            'SELECT * FROM users JOIN orders WHERE',       // JOIN条件なし
            'SELECT * FROM users GROUP BY',                // GROUP BY カラムなし
        ];
    }
}
