# セキュリティ

このドキュメントは、PHP-Training リポジトリのセキュリティ方針と、
これまでに実施した改善についてまとめたものです。

> **このリポジトリは学習用のサンプル集です。**
> 実運用のシステムではないため、ここにあるコードをそのまま
> 本番環境へ持ち込むことは想定していません。

## 脆弱性の報告

このリポジトリのコードに問題を見つけた場合は、
GitHub の Issue で報告してください。

https://github.com/shumasod/PHP-Training/issues

学習用のリポジトリであり、実際に稼働しているサービスはないため、
非公開での連絡経路は用意していません。

## 実施したセキュリティ対策

### 1. データベース認証情報の保護

**問題**: データベースのパスワードがソースコード内に平文で保存されていました。

**修正内容**:
- `learn_1/db_connection.php`: 環境変数からDB認証情報を取得
- `learn_1/function.php`: 環境変数からDB認証情報を取得
- `database.php`: 環境変数からDB認証情報を取得
- `db.php`: 環境変数からDB認証情報を取得（`DB_DRIVER` で接続先も切り替え）
- `siglton.php`: 環境変数からDB認証情報を取得

**使用方法**:
```bash
# .env.example を .env にコピー
cp .env.example .env

# .env ファイルを編集して実際の認証情報を入力
vim .env
```

### 2. SQLインジェクション対策

**問題**: `table.php`でテーブル名やカラム名が検証なしでSQL文に埋め込まれていました。

**修正内容**:
- テーブル名、カラム名、ORDER BY句に対する入力検証を追加
- 正規表現を使用して、英数字とアンダースコアのみを許可
- 不正な入力に対してはInvalidArgumentExceptionをスロー

### 3. XSS（クロスサイトスクリプティング）対策

**問題**: ユーザー入力やセッション値が適切にエスケープされずに出力されていました。

**修正内容**:
- `learn/sessiontest_1.php`: セッション値の出力時にhtmlspecialchars()を使用
- `learn_2/input.php`: 変数名のタイポを修正（$_SE → $_SESSION）
- `learn_2/input3.php`: POST された値をエスケープせず出力していた箇所を修正
- `learn_2/rss.php`: 外部 RSS フィード由来の値のエスケープと、
  `href` に入れる URL のスキーム制限
- `database.php`: `javascript:` スキームを `href` に入れられる問題を修正
- `learn_1/test1.php`: フォームから書き込まれた CSV の未エスケープ出力を修正

### 4. セッション管理の強化

**問題**:
- セッション固定攻撃に対する脆弱性
- セキュアなセッション設定の欠如

**修正内容**:
- `learn_2/input.php`: CSRF検証後にsession_regenerate_id(true)を追加
- `learn/sessiontest_1.php`: セキュアなセッション設定を追加
  - `session.cookie_httponly`: JavaScriptからのアクセスを防止
  - `session.cookie_secure`: HTTPS接続のみでクッキーを送信
  - `session.use_strict_mode`: 未初期化のセッションIDを拒否
  - `session.cookie_samesite`: CSRF攻撃を防止

### 5. デバッグ情報の漏洩対策

**問題**: 本番環境でvar_dump()などのデバッグ情報が表示される可能性がありました。

**修正内容**:
- `learn_2/input.php`: 確認画面で `var_dump($_SESSION)` を出していた箇所を削除。
  セッションには CSRF トークンが含まれるため、画面に出すと
  そのユーザーとして操作を仕掛けられる。
- `learn_2/input2.php`: POST のたびに `var_dump($_POST)` を出していた箇所を削除。
  `var_dump()` は HTML エスケープをしないため反射型 XSS になっていた。
- `learn_5/TestController.php`: `dd()` の削除。
- `debug/` 配下: `?test=1` で誰でもデバッグ出力を引き出せた問題を修正。

### 6. .gitignore の追加

**修正内容**:
- `.env`ファイルをGit管理から除外
- その他の機密情報や一時ファイルを除外

## セキュリティのベストプラクティス

### 環境変数の設定

本番環境では、以下の環境変数を設定してください：

```bash
export DB_DRIVER=mysql   # mysql / pgsql / sqlite
export DB_HOST=localhost
export DB_PORT=3306
export DB_NAME=your_database
export DB_USER=your_username
export DB_PASSWORD=your_secure_password
```

### HTTPS の使用

本番環境では必ずHTTPSを使用してください。セッションクッキーは`session.cookie_secure`が有効になっているため、HTTPSが必要です。

### 定期的なセキュリティ監査

定期的にコードをレビューし、以下の項目を確認してください：

1. すべてのユーザー入力が適切に検証・サニタイズされているか
2. すべての出力が適切にエスケープされているか
3. SQLクエリがプリペアドステートメントを使用しているか
4. 機密情報がログに出力されていないか
5. セッション管理が適切に実装されているか

## 参考になるファイル

以下は、対策の書き方を確認するのに向いています。

- `secure_query.php`: プリペアドステートメントとパラメータの型指定
- `SessionManager.php`: セッションの初期化・検証・破棄
- `Sample.php`: CSRF トークンの発行と検証、出力エスケープ
- `Logger.php`: 実行環境に応じたログ出力の切り替え

ただし、**どのファイルも「完成形」ではありません。**
このドキュメントの初版では上記を「既に適切なセキュリティ対策が
実装されています」と紹介していましたが、その後のレビューで
以下が見つかっています。

| ファイル | 見つかった問題 |
| --- | --- |
| `Sample.php` | 投稿データを公開ディレクトリに保存（IP を含む）、同時投稿でのデータ消失、`FILTER_SANITIZE_STRING` の使用 |
| `SessionManager.php` | セッション固定化、権限チェックの緩い比較、CodeIgniter 外での Fatal error |
| `database.php` | 識別子に `real_escape_string()` を使用、例外メッセージの画面出力 |

修正済みですが、「このファイルは安全だ」と決めつけず、
使う前に必ず中身を読んでください。

## 今後の改善提案

1. **パスワードのハッシュ化**: ユーザーパスワードは必ず`password_hash()`を使用
2. **入力検証の強化**: すべてのユーザー入力に対して厳密な検証を実装
3. **エラーハンドリング**: 本番環境ではエラーメッセージを詳細に表示しない
4. **レート制限**: ログイン試行回数などに制限を設ける
5. **セキュリティヘッダー**: Content-Security-Policy、X-Frame-Optionsなどを設定

## 参考リンク

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP セキュリティガイド](https://www.php.net/manual/ja/security.php)
- [セキュアコーディング](https://www.ipa.go.jp/security/vuln/websecurity.html)
