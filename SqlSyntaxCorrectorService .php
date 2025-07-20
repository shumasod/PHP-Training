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

// サービスプロバイダー (app/Providers/SqlCorrectorServiceProvider.php)
namespace App\Providers;

use App\Services\SqlSyntaxCorrectorService;
use Illuminate\Support\ServiceProvider;

class SqlCorrectorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SqlSyntaxCorrectorService::class, function ($app) {
            return new SqlSyntaxCorrectorService();
        });
    }

    public function boot()
    {
        // 必要に応じて設定ファイルの発行など
    }
}

// フロントエンド用のBladeテンプレート (resources/views/sql-corrector.blade.php)
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Syntax Corrector</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .input-section, .output-section {
            margin-bottom: 30px;
        }
        .sql-textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            resize: vertical;
        }
        .sql-textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .button-group {
            margin: 15px 0;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .error-list {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .error-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff5f5;
            border-left: 4px solid #dc3545;
            border-radius: 0 4px 4px 0;
        }
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            color: #155724;
        }
        .corrected-sql {
            background-color: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            white-space: pre-wrap;
            margin-top: 15px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .examples {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .example-sql {
            background-color: white;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .example-sql:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SQL Syntax Corrector</h1>
            <p>SQLのシンタックスエラーを自動的に検出・修正します</p>
        </div>

        <div class="input-section">
            <h3>SQLクエリを入力してください：</h3>
            <textarea id="sqlInput" class="sql-textarea" 
                placeholder="SELECT * FROM users WHERE id = 1&#10;&#10;例：&#10;SELECT FROM users&#10;INSERT INTO users VALUES&#10;UPDATE users WHERE id = 1"></textarea>
            
            <div class="button-group">
                <button class="btn btn-primary" onclick="correctSql()">エラーチェック・修正</button>
                <button class="btn btn-secondary" onclick="formatSql()">SQL整形</button>
                <button class="btn btn-secondary" onclick="clearAll()">クリア</button>
            </div>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>処理中...</p>
        </div>

        <div class="output-section" id="output" style="display: none;">
            <h3>解析結果：</h3>
            <div id="results"></div>
        </div>

        <div class="examples">
            <h4>テスト用SQL例（クリックで入力）：</h4>
            <div class="example-sql" onclick="setExample('SELECT FROM users')">SELECT FROM users</div>
            <div class="example-sql" onclick="setExample('SELECT * users WHERE id = 1')">SELECT * users WHERE id = 1</div>
            <div class="example-sql" onclick="setExample('SELECT name, FROM users')">SELECT name, FROM users</div>
            <div class="example-sql" onclick="setExample(`SELECT * FROM users WHERE name = John`)">SELECT * FROM users WHERE name = John</div>
            <div class="example-sql" onclick="setExample('INSERT INTO users VALUES')">INSERT INTO users VALUES</div>
            <div class="example-sql" onclick="setExample('UPDATE users WHERE id = 1')">UPDATE users WHERE id = 1</div>
        </div>
    </div>

    <script>
        // CSRFトークンの設定
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function correctSql() {
            const sql = document.getElementById('sqlInput').value.trim();
            
            if (!sql) {
                alert('SQLクエリを入力してください');
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch('/api/sql-corrector/correct', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sql: sql })
                });

                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.data);
                } else {
                    alert('エラー: ' + (data.error || '不明なエラーが発生しました'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        async function formatSql() {
            const sql = document.getElementById('sqlInput').value.trim();
            
            if (!sql) {
                alert('SQLクエリを入力してください');
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch('/api/sql-corrector/format', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sql: sql })
                });

                const data = await response.json();
                
                if (data.success) {
                    displayFormatResult(data.data);
                } else {
                    alert('エラー: ' + (data.error || '不明なエラーが発生しました'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            let html = '';

            if (data.has_errors) {
                html += '<div class="error-list">';
                html += '<h4>検出されたエラー:</h4>';
                data.corrections.forEach(correction => {
                    html += `<div class="error-item">
                        <strong>${correction.description}</strong>
                        ${correction.suggestion ? `<br><small>提案: ${correction.suggestion}</small>` : ''}
                    </div>`;
                });
                html += '</div>';

                html += '<h4>修正されたSQL:</h4>';
                html += `<div class="corrected-sql">${escapeHtml(data.corrected_sql)}</div>`;
            } else {
                html += '<div class="success-message">';
                html += '<h4>✅ エラーは検出されませんでした！</h4>';
                html += '<p>入力されたSQLは正常な構文です。</p>';
                html += '</div>';
            }

            resultsDiv.innerHTML = html;
            document.getElementById('output').style.display = 'block';
        }

        function displayFormatResult(data) {
            const resultsDiv = document.getElementById('results');
            let html = '<h4>整形されたSQL:</h4>';
            html += `<div class="corrected-sql">${escapeHtml(data.formatted_sql)}</div>`;
            
            resultsDiv.innerHTML = html;
            document.getElementById('output').style.display = 'block';
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.getElementById('output').style.display = show ? 'none' : document.getElementById('output').style.display;
        }

        function clearAll() {
            document.getElementById('sqlInput').value = '';
            document.getElementById('output').style.display = 'none';
            document.getElementById('results').innerHTML = '';
        }

        function setExample(sql) {
            document.getElementById('sqlInput').value = sql;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

<?php
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
