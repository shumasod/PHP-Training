<?php

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
