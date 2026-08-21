# Factory の使い方

`UserFactory` の呼び出し例です。

以前は `usefactory.php` の中で、クラス定義のすぐ下にファイルスコープの
実行文として書かれていました。

```php
class UserFactory extends Factory { /* ... */ }

// テストでの使用
$user = User::factory()->create();          // ← require しただけで実行される
$admin = User::factory()->admin()->create();
$users = User::factory()->count(3)->create();
```

**ファイルスコープなので、`require` しただけで 5 件のレコードが作られます。**
factory は「定義」であって「実行」ではないため、使用例はここへ移しました。

## 呼び出し例

```php
// 1 件作る
$user = User::factory()->create();

// 管理者を 1 件作る
$admin = User::factory()->admin()->create();

// 3 件まとめて作る
$users = User::factory()->count(3)->create();

// メール未確認の状態で作る
$pending = User::factory()->unverified()->create();

// 保存せずインスタンスだけ欲しいとき
$user = User::factory()->make();

// 特定の値を上書きする
$user = User::factory()->create(['email' => 'test@example.com']);
```

## パスワードについて

`definition()` が返すパスワードは `password` の固定値です。
テストでログインさせたいときはこれを使います。

```php
$this->post('/login', [
    'email' => $user->email,
    'password' => 'password',
]);
```

ハッシュ値は `static::$password` に 1 回だけ計算して使い回します。
bcrypt は意図的に遅いので、100 件作るときに毎回ハッシュすると
その分だけテストが遅くなるためです。
