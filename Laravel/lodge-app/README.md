# Lodge App（Laravel CRUD 練習）

Laravel で CRUD を段階的に組み立てていく練習用のサンプルです。

以前は `free3.php` / `free2.php` / `free.php` の 3 ファイルに、それぞれ複数ファイル分の
コードが 1 ファイルへ連結された状態で置かれていました。ファイル中に `<?php` が何度も
現れるため、いずれも `php -l` が Parse error を返す状態でした。

本ディレクトリはそれを Laravel の実際の配置に沿って分割したものです。
元ファイルは同じアプリを段階的に育てた 3 世代だったため、`step1` → `step3` として保存しています。

## 各ステップの内容

### `step1/`（旧 `free3.php`）

最小構成。`Lodge` モデルと一覧・詳細の表示のみ。

- `app/Http/Controllers/LodgeController.php` — `index` / `show`
- `app/Models/Lodge.php`
- `database/migrations/xxxx_xx_xx_create_lodges_table.php`
- `routes/web.php`
- `resources/views/lodges/{index,show}.blade.php`

### `step2/`（旧 `free2.php`）

`Member` / `Event` のリレーションと登録フォームを追加。

- `app/Http/Controllers/LodgeController.php` — `create` / `store` を追加
- `app/Models/{Lodge,Member,Event}.php`
- `database/migrations/` — members / events テーブル
- `resources/views/lodges/create.blade.php` を追加

### `step3/`（旧 `free.php`）

FormRequest によるバリデーション、Policy による認可、`Symbol` の追加まで含む完成形。

- `app/Http/Controllers/` — Lodge / Member / Event / Symbol
- `app/Http/Requests/` — `Store{Lodge,Member,Event}Request`
- `app/Policies/` — `{Lodge,Member,Event}Policy`
- `app/Models/` — Lodge / Member / Event / Symbol
- `database/migrations/` — 4 テーブル
- `resources/views/layouts/app.blade.php` ほか

## 注意

分割にあたってコードの中身は変更していません。学習用サンプルであり、
そのまま本番へ持ち込むことは想定していません。
