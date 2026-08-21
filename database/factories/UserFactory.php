<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * ハッシュ済みパスワードのキャッシュ。
     *
     * bcrypt は意図的に遅い。100 件作る factory で毎回ハッシュすると
     * その分だけテストが遅くなるので、1 回だけ計算して使い回す。
     * Laravel の標準 UserFactory も同じ形を採っている。
     */
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            // 修正前は bcrypt('password') を直接呼んでいた。
            // Hash ファサードを使うと config/hashing.php の設定
            // (driver / cost) に従うので、アプリ本体と同じ条件になる。
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * 管理者用の状態
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin' => true,
        ]);
    }

    /**
     * メール未確認の状態
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
