<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        Gate::define('test',function(User $user){
            if($user->id===1){
                return true;
            }
            return false;
        });

        // 問い合わせの閲覧・編集・削除を管理者だけに限定する。
        //
        // ContactFormController の show / edit / update / destroy は
        // docblock 上「管理者用」とされていたが、ルートに付いていた
        // ミドルウェアは ['auth'] だけだった。
        // つまりログインさえしていれば、URL の ID を差し替えるだけで
        // 他人の問い合わせ (氏名・メールアドレス・本文) を
        // 閲覧・編集・削除できた。
        //
        // contact_forms には所有者の列が無く、store() も user_id を
        // 記録していないため「自分の分だけ見せる」という絞り方はできない。
        // 元の意図どおり管理者限定にする。
        //
        // 判定には database/factories/UserFactory.php の admin() 状態が
        // 立てる is_admin 列を使う。
        // (learn_8/RoleMiddleware.php は role === 'admin' で判定しており
        //  リポジトリ内に 2 つの流儀があるが、ファクトリが実際に用意するのは
        //  is_admin なのでそちらに合わせる。)
        Gate::define('manage-contacts', function (User $user): bool {
            return (bool) ($user->is_admin ?? false);
        });
    }
}
