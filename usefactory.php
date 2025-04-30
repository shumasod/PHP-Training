// Factory定義（database/factories/UserFactory.php）
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
    
    // 管理者用の状態
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => true,
            ];
        });
    }
}

// テストでの使用
$user = User::factory()->create();
$admin = User::factory()->admin()->create();
$users = User::factory()->count(3)->create();
