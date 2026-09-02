<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactForm>
 */
class ContactFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            // name() の引数は性別 ('male' / 'female') であって文字数ではない。
            // 修正前の name(20) の 20 は黙って無視されるため、
            // StoreContactRequest の max:20 を超える名前が生成されうる。
            // realText() のような長さ指定と混同したものと思われる。
            // 生成後に切り詰めて上限を守る。
            'name' => mb_substr($this->faker->name(), 0, 20),

            'title' => $this->faker->realText(50),

            // email() は gmail.com / yahoo.com など実在するドメインを使う。
            // このモデルは問い合わせフォームのデータで、
            // テストやシーディングの流れでメール送信まで走ると
            // 実在しうるアドレスへ届いてしまう。
            // safeEmail() は RFC 2606 の予約ドメイン (example.org など) を
            // 使うので、誤送信の心配がない。
            'email' => $this->faker->unique()->safeEmail(),

            // url() も同様に実在ドメインを返すことがある。
            'url' => 'https://example.com/' . $this->faker->slug(),

            'gender' => $this->faker->boolean(),
            'age' => $this->faker->numberBetween(1, 6),
            'contact' => $this->faker->realText(200),
        ];
    }
}
