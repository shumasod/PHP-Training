<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\UserTestHelpers;

/**
 * UserTestHelpers の使用例。
 *
 * 修正前は custometest.php の中で、トレイトの定義のすぐ下に
 * コメント「// テストクラスでの使用」付きで書かれていた。
 */
class AdminTest extends TestCase
{
    // テストごとにデータベースを巻き戻す。
    // これが無いと、前のテストが作ったユーザーが次のテストに残り、
    // 実行順によって結果が変わる。
    use RefreshDatabase;
    use UserTestHelpers;

    public function test_admin_can_view_dashboard(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    /**
     * 「管理者は見られる」だけを確認しても、認可が機能しているとは言えない。
     * 一般ユーザーが弾かれることも併せて確認する。
     */
    public function test_guest_cannot_view_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }
}
