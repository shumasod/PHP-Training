# SQL Syntax Corrector

SQL の構文エラーを検出・自動修正し、整形して返す Laravel 向けのサービス一式です。

以前は `sey.php` と `SqlSyntaxCorrectorService .php` に、複数ファイル分のコードが
1 ファイルへ連結された状態で置かれていました（`sey.php` は同じ内容が 2 回重複し、
2 つ目の `<?php` タグにより Parse error になっていました）。
本ディレクトリはそれを Laravel の実際の配置に沿って分割したものです。

## ファイル構成

| パス | 役割 |
| --- | --- |
| `app/Services/SqlSyntaxCorrectorService.php` | 構文チェック・自動修正・整形の本体 |
| `app/Http/Controllers/SqlCorrectorController.php` | `POST /api/sql-corrector/{correct,format}` のエンドポイント |
| `app/Console/Commands/SqlCorrectorCommand.php` | `php artisan sql:correct` |
| `app/Providers/SqlCorrectorServiceProvider.php` | サービスコンテナへの登録 |
| `routes/api.php` | API ルート定義 |
| `resources/views/sql-corrector.blade.php` | 動作確認用のフロントエンド |
| `tests/SqlCorrectorExample.php` | 検証用のサンプル SQL 集 |

## セットアップ手順

1. サービスプロバイダーを登録する（`config/app.php`）

   ```php
   'providers' => [
       // ...
       App\Providers\SqlCorrectorServiceProvider::class,
   ],
   ```

2. API ルートを追加する（`routes/api.php`）

   ```php
   Route::prefix('sql-corrector')->group(function () {
       Route::post('/correct', [App\Http\Controllers\SqlCorrectorController::class, 'correctSql']);
       Route::post('/format', [App\Http\Controllers\SqlCorrectorController::class, 'formatSql']);
   });
   ```

3. Web ルートを追加する（`routes/web.php`）

   ```php
   Route::get('/sql-corrector', function () {
       return view('sql-corrector');
   });
   ```

4. Artisan コマンドを登録する（`app/Console/Kernel.php`）

   ```php
   protected $commands = [
       App\Console\Commands\SqlCorrectorCommand::class,
   ];
   ```

5. 各ファイルを Laravel プロジェクトの対応するパスへ配置する。

## 主な機能

### 構文チェック

- `SELECT` にカラム指定がない
- `FROM` 句の欠落
- 不要なカンマ
- 文字列リテラルの引用符漏れ
- `INSERT` の `VALUES` 句が不完全
- `UPDATE` の `SET` 句がない
- `JOIN` の結合条件がない
- `GROUP BY` にカラムがない

### SQL 整形

- キーワードの大文字化
- 適切な改行とインデント
- 読みやすい形式での出力

## 拡張の余地

1. より高度な構文解析
2. データベース固有の文法チェック
3. パフォーマンス改善の提案
4. セキュリティリスクの検出
5. リアルタイムバリデーション
