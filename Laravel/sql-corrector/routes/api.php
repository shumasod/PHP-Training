<?php

declare(strict_types=1);

use App\Http\Controllers\SqlCorrectorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SQL Syntax Corrector API Routes
|--------------------------------------------------------------------------
|
| 元は sey.php 内のコメントとして書かれていた定義を実ファイル化したもの。
| routes/api.php にそのまま追記して使う。
|
*/

Route::prefix('sql-corrector')->group(function (): void {
    Route::post('/correct', [SqlCorrectorController::class, 'correctSql']);
    Route::post('/format', [SqlCorrectorController::class, 'formatSql']);
});
