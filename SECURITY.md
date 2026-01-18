# セキュリティ改善ドキュメント

このドキュメントは、PHP-Trainingリポジトリに実装されたセキュリティ改善について説明します。

## 実施したセキュリティ対策

### 1. データベース認証情報の保護

**問題**: データベースのパスワードがソースコード内に平文で保存されていました。

**修正内容**:
- `learn_1/db_connection.php`: 環境変数からDB認証情報を取得
- `database.php`: 環境変数からDB認証情報を取得
- `db.php`: 環境変数からDB認証情報を取得

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
- `learn_2/input.php`: デバッグコードをコメントアウト

### 6. .gitignore の追加

**修正内容**:
- `.env`ファイルをGit管理から除外
- その他の機密情報や一時ファイルを除外

## セキュリティのベストプラクティス

### 環境変数の設定

本番環境では、以下の環境変数を設定してください：

```bash
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

## 既に実装されているセキュリティ機能

以下のファイルでは、既に適切なセキュリティ対策が実装されています：

- `Sample.php`: CSRF保護、入力サニタイズ
- `SessionManager.php`: セキュアなセッション管理
- `sequre_query`: プリペアドステートメント
- `database.php`: プリペアドステートメント（一部）

これらのファイルをベストプラクティスの参考にしてください。

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
