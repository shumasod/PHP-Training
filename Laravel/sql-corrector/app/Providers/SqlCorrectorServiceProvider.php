<?php

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
