# TypeError: Argument #2 ($id) must be of type int, null given

CodeIgniter で起きた TypeError の、原因と対応を段階的に追った教材です。

以前はこの 3 ファイルがリポジトリのルートに散らばっていました。

```
UserController.php          ← class UserController と class OptionService_model
UserController-hotfix.php   ← class UserController        （同名！）
OptionService_model.php     ← class OptionService_model   （同名！）
```

**同じクラス名が 2 回ずつ定義されている**ため、2 つを同時に読み込むと落ちます。

```
$ php collide.php
UserController.php: OK (UserController と OptionService_model を定義)
PHP Fatal error: Cannot redeclare class UserController
  (previously declared in UserController.php:10) in UserController-hotfix.php on line 10
```

before / after に分けることで、同時に読み込まれない配置にしました。
`before/UserController.php` に 2 クラスが同居していた点も、1 ファイル 1 クラスへ分割しています。

## 発生した問題

```
TypeError: OptionService_model::getUserDetail(): Argument #2 ($id)
must be of type int, null given
```

`before/UserController.php`:

```php
// セッションからユーザーIDを取得（ここで null が返される可能性）
$userId = $_SESSION['user_id'] ?? null;

// 型宣言があるメソッドに null を直接渡してしまう
$options = $this->OptionService_model->getUserDetail('param1', $userId);
```

`before/OptionService_model.php`:

```php
// 厳密な型宣言により、null が渡されると TypeError 発生
public function getUserDetail($param1, int $id)
```

セッションが切れた利用者のリクエストで必ず落ちます。

## 対応の段階

| ディレクトリ | 内容 |
| --- | --- |
| `before/` | 問題が起きていた元のコード |
| `after/` | 防御的プログラミングを入れた修正版 |

`after/` では次のようにしています。

- `UserController` — ユーザー ID の取得を複数の経路から試み、型と範囲を検証してから渡す。例外は握りつぶさず、追跡用の ID を付けて JSON で返す
- `OptionService_model` — 引数を `?int` にし、null と範囲外を明示的に例外で弾く。ユーザーの存在確認も行う

## 関連するクラス

汎用化した検証・セッション管理はリポジトリのルートにあります。

- `InputValidator.php` — ユーザー ID / メール / パスワードの検証
- `SessionManager.php` — セッションの初期化・検証・破棄
- `Logger.php` — CodeIgniter / Laravel / 素の PHP のどこでも動くログ出力

## 学べること

1. `?? null` と厳密な型宣言を組み合わせると、null が型境界まで素通りする
2. 型宣言は「呼び出し側が正しい値を渡す」ことを保証しない。境界で検証する
3. 修正は「即応（防御的に弾く）」→「堅牢化（型を正す）」→「予防（共通の検証クラス）」の順で進められる
