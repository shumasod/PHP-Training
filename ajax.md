以下は、Laravel 10＋Vite環境を想定し、最新のベストプラクティスや補足項目を盛り込んだ改訂版READMEです。

```markdown
# Laravel における Ajax 実装の最新ベストプラクティス

本ドキュメントでは、Laravel 10＋Vite 環境における Ajax（XHR／Fetch／Axios）実装の最新ベストプラクティスをまとめています。  
セキュリティ、パフォーマンス、メンテナビリティを重視した構成例を掲載しています。

---

## 1. プロジェクト準備

### Laravel／Vite セットアップ

```bash
# Laravel インストール（例: バージョン指定あり）
composer create-project laravel/laravel:^10.0 my-app

# フロントエンドの依存インストール
cd my-app
npm install
```

`vite.config.js` はデフォルトで最適化済みです。Blade から Vite アセットを読み込む場合、`@vite` ヘルパーを使用します。

---

## 2. ルーティング設計

### ファイル分割

- **Web ルート** … `routes/web.php`  
- **API ルート** … `routes/api.php`

```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('welcome'))->name('home');
Route::middleware(['auth'])->get('/dashboard', fn() => view('dashboard'))->name('dashboard');
```

```php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DataController;

Route::prefix('data')->group(function () {
    // 公開
    Route::post('public', [DataController::class, 'getPublicData'])
         ->middleware('throttle:60,1');
    // 認証必須（Sanctum）
    Route::post('secure', [DataController::class, 'getSecureData'])
         ->middleware(['auth:sanctum', 'throttle:30,1']);
});
```

---

## 3. コントローラー実装

```bash
php artisan make:controller Api/DataController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DataController extends Controller
{
    public function getPublicData(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:100',
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => '公開APIレスポンス',
            'data'      => $validated['query'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function getSecureData(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status'    => 'success',
            'message'   => "{$user->name} さん、認証済みAPIレスポンスです",
            'user'      => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

- **バリデーション** はコントローラーの `$request->validate()` を活用。  
- エラー時は自動で 422 を返却。  

---

## 4. 認証・CSRF 保護

### Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

- SPA やモバイルクライアントからは `/sanctum/csrf-cookie` → 認証 API → 保護されたエンドポイント の順で利用。

---

## 5. フロントエンド実装

### Vite ブレード読み込み例

```blade
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Ajax Example</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app" class="container py-5">
        <h1>Laravel + Vite + Axios</h1>
        <!-- 公開API -->
        <div class="mb-4">
            <button id="btnPublic" class="btn btn-primary">公開データ取得</button>
            <pre id="outPublic" class="mt-2 bg-light p-2"></pre>
        </div>
        <!-- 認証API -->
        @auth
        <div class="mb-4">
            <button id="btnSecure" class="btn btn-success">セキュアデータ取得</button>
            <pre id="outSecure" class="mt-2 bg-light p-2"></pre>
        </div>
        @endauth
    </div>
    @vite('resources/js/app.js')
</body>
</html>
```

### `resources/js/app.js`

```js
import axios from 'axios';
import './bootstrap'; // Laravel Breeze 等で csrf-cookie 設定済みの場合

axios.defaults.headers.common['X-CSRF-TOKEN'] = document
  .querySelector('meta[name="csrf-token"]')
  .getAttribute('content');

document.getElementById('btnPublic')?.addEventListener('click', async () => {
  const out = document.getElementById('outPublic');
  out.textContent = '読み込み中...';
  try {
    const { data } = await axios.post('/api/data/public', { query: 'test' });
    out.textContent = JSON.stringify(data, null, 2);
  } catch (e) {
    out.textContent = e.response
      ? `Error ${e.response.status}: ${e.response.statusText}`
      : e.message;
  }
});

document.getElementById('btnSecure')?.addEventListener('click', async () => {
  const out = document.getElementById('outSecure');
  out.textContent = '読み込み中...';
  try {
    // 初回のみ CSRF クッキー取得
    await axios.get('/sanctum/csrf-cookie');
    const { data } = await axios.post('/api/data/secure');
    out.textContent = JSON.stringify(data, null, 2);
  } catch (e) {
    out.textContent = e.response?.status === 401
      ? '認証が必要です'
      : e.message;
  }
});
```

---

## 6. セキュリティ追加設定

1. **レート制限**：`throttle:60,1` などで DoS 緩和。  
2. **CORS**：必要に応じて `config/cors.php` を調整。  
3. **レスポンスヘッダー**：`X-Frame-Options` や `Referrer-Policy` を設定。  
4. **XSS**：必ず `textContent`／`createTextNode` で描画し、innerHTML を直接使わない。  

---

## 7. 動作確認

```bash
# アセットビルド & サーバー起動
npm run dev
php artisan serve
```

- ブラウザで `http://127.0.0.1:8000` を開き、各ボタンをクリックして動作を検証してください。

---

## まとめ

- Laravel 10＋Vite 環境での Ajax 実装サンプル  
- API と Web ルートの明確分離  
- Sanctum＋CSRF／CORS／レート制限によるセキュリティ強化  
- Axios を用いた非同期通信サンプル  
- XSS／バリデーション／エラーハンドリングの徹底  

必要に応じ、権限管理やジョブキュー、Broadcasting などと組み合わせて応用ください。```
