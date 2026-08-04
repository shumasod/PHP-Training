<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // 注意: これは Gate の書き方を確認するためのサンプル。
        // 「ID が 1 のユーザーだけ許可」というのは実運用では使えない。
        // 権限は users.role や専用の権限テーブルで表現し、
        // 主キーの値に意味を持たせないこと。
        Gate::define('test', function (User $user): bool {
            return $user->id === 1;
        });
    }
}
