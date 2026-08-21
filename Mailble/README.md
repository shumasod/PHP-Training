# Mailble — Laravel のメール送信サンプル

Laravel の Mailable / Notification / キューを使ったメール送信の練習用です。

以前は手順が「Mailableクラスの作成」「キューテーブルの作成」という
拡張子の無いファイルに 1 行ずつ書かれていました。中身はシェルコマンドなので、
実行できるファイルではなく手順書としてここにまとめています。

## ファイル構成

| パス | 役割 |
| --- | --- |
| `MailController.php` | 送信エンドポイント |
| `Notification.php` | 通知クラス |
| `notification.blade.php` | メール本文のテンプレート |
| `web.php` | ルート定義 |
| `.env.example` | メール送信設定のサンプル |

## セットアップ

### 1. 環境変数を設定する

```sh
cp Mailble/.env.example .env
```

`MAIL_USERNAME` と `MAIL_PASSWORD` を埋めてください。
Gmail の場合は通常のログインパスワードではなく、アプリパスワードを発行して設定します。

### 2. Mailable クラスを作成する

```sh
php artisan make:mail NotificationMail
```

### 3. キューテーブルを作成する

メール送信をキュー経由にする場合に必要です。

```sh
php artisan queue:table
php artisan migrate
```

`.env` の `QUEUE_CONNECTION=database` と対応しています。

### 4. キューワーカーを起動する

```sh
php artisan queue:work
```
