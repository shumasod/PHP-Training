namespace Tests\Traits;

trait UserTestHelpers
{
    protected function createAdminUser()
    {
        return User::factory()->admin()->create();
    }
    
    protected function loginAsAdmin()
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        return $admin;
    }
}

// テストクラスでの使用
class AdminTest extends TestCase
{
    use UserTestHelpers;
    
    public function testAdminDashboard()
    {
        $admin = $this->loginAsAdmin();
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
    }
}
