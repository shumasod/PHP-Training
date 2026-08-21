# PHP-Training

PHP の学習と実践のための練習用リポジトリです。
素の PHP、Laravel、CodeIgniter のコードが章ごと・題材ごとに置かれています。

> **注意**
> ここにあるコードは学習用のサンプルです。そのまま本番環境へ持ち込むことは
> 想定していません。動かす場合は必ず内容を確認してください。

## ディレクトリ構成

各ディレクトリは**独立したスナップショット**です。
`learn_5/Kernel.php` と `learn_8/Kernel.php` のように同じクラス名が
複数の章に登場しますが、同時に読み込まれることは想定していません。

| パス | 内容 |
| --- | --- |
| `learn/`, `learn_1/` | 素の PHP。ファイル入出力、セッション、PDO、クラスの基本 |
| `learn_2/` | お問い合わせフォーム（入力 → 確認 → 完了）、RSS リーダー |
| `learn_3/` 〜 `learn_8/` | Laravel を章ごとに進めたスナップショット |
| `Laravel/lodge-app/` | Laravel の CRUD を 3 段階で組み立てた例（step1 〜 step3） |
| `Laravel/sql-corrector/` | SQL の構文チェック・整形サービス |
| `codeigniter-typeerror/` | CodeIgniter で起きた TypeError の before / after |
| `Game/` | ハチ公ゲーム（CLI）、パチンコゲーム（HTML/JS） |
| `Mailble/` | Laravel のメール送信・キュー |
| `nww/`, `new/` | Laravel のダッシュボード、静的ページ |
| `debug/` | デバッグ用の関数サンプル |
| `tools/` | リポジトリ健全性チェック（後述） |
| ルート直下 | 単体で完結する題材（バリデーション、セッション管理、PDF 生成など） |

## 動かし方

多くのファイルは環境変数から DB 接続情報を読みます。

```sh
cp .env.example .env
```

`.env` を編集して接続情報を設定してください。`.env` は `.gitignore`
の対象なのでコミットされません。**認証情報をソースへ直接書かないでください。**

| 変数 | 用途 |
| --- | --- |
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` | データベース接続 |
| `MAIL_*` | メール送信（`Mailble/.env.example` を参照） |

## 検査

構文エラーや、よくある壊れ方をまとめて検査できます。

```sh
php tools/check-repo.php
```

検査項目:

1. 構文エラー（`php -l`）
2. `<?php` 開始タグの欠落 — 無いと Web 経由でソースが平文で返る
3. 拡張子の無い PHP ファイル — PHP として実行されず lint も素通りする
4. クラス名の重複 — 同時に読み込むと `Cannot redeclare class` で落ちる

同じ検査を GitHub Actions（`.github/workflows/php-lint.yml`）でも
PHP 8.3 / 8.4 の両方で実行しています。

## コーディング規約

- 改行コードは LF（`.gitattributes` / `.editorconfig` で強制）
- インデントは PHP が 4 スペース、JS / CSS / JSON / YAML が 2 スペース
- ファイル名はクラス名と一致させる（PSR-4）
- 画面へ出す値は必ず `htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` を通す
- SQL の値はプレースホルダで渡す。識別子はバインドできないので許可リストで絞る

## セキュリティ

セキュリティに関する実装詳細については、[SECURITY.md](SECURITY.md) をご覧ください。
脆弱性を見つけた場合の報告先も同ファイルに記載しています。

## 使用中のオープンソースライブラリ

このプロジェクトは以下のオープンソースライブラリを使用しています：

### PHP依存関係（Composer）
- **nesbot/carbon** (MIT License) - 日付処理ライブラリ
- **Symfony コンポーネント** (MIT License) - 各種Symfonyパッケージ

### CSS/JavaScriptライブラリ（CDN）
- **Bootstrap** (MIT License) - CSSフレームワーク
- **Tailwind CSS** (MIT License) - ユーティリティファーストCSSフレームワーク
- **jQuery** (MIT License) - JavaScriptライブラリ
- **Tailblocks** (MIT License) - UIコンポーネントライブラリ

詳細な著作権表示については、[NOTICE.md](NOTICE.md) をご覧ください。

## ライセンス

このプロジェクトは MIT License の下で公開されています。詳細は [LICENSE](LICENSE) ファイルをご覧ください。

### 第三者コンポーネント

このプロジェクトには、それぞれ独自のライセンスを持つ第三者のコードとライブラリが含まれています。各コンポーネントのライセンス情報については、以下をご確認ください：

- Composer依存関係: `learn_1/composer.lock` を参照
- Node.js依存関係: `new/package.json` を参照

## 法的事項

### 著作権

- このリポジトリのカスタムコード: Copyright (c) 2026 PHP-Training Contributors
- 外部ライブラリ: それぞれの著作権者に帰属

### AI学習・データクローリング

このリポジトリは教育・学習目的で公開されています。商用利用や大規模なデータ収集を行う場合は、MITライセンスの条件を遵守してください。

## コントリビューション

コードを引用または参考にした場合は、必ず出典を明記してください。

