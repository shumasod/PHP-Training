<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;

/**
 * テストで管理者ユーザーを扱うためのヘルパー。
 *
 * 修正前は custometest.php という 1 ファイルに、このトレイトと
 * それを使う AdminTest クラスが同居していた。以下の問題があった。
 *
 *   - ファイル名 (custometest.php) とクラス名 (AdminTest) が一致せず、
 *     PSR-4 オートロードで解決できない
 *   - 1 ファイルに 2 つの型が入っている
 *   - User と TestCase の use が無く、名前空間 Tests\Traits の下にある
 *     Tests\Traits\User / Tests\Traits\TestCase という存在しないクラスを
 *     参照していた
 *
 * トレイトと使用例を分け、それぞれ規約どおりのパスへ置いた。
 */
trait UserTestHelpers
{
    protected function createAdminUser(): User
    {
        return User::factory()->admin()->create();
    }

    protected function loginAsAdmin(): User
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        return $admin;
    }
}
