<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ブログ</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="icon" href="<?php echo asset('images/macho-icon.ico'); ?>" type="image/x-icon">
</head>
<body class="bg-gray-100 font-sans">
  <header class="bg-blue-500 p-4 text-white">
    <div class="container mx-auto">
      <h1 class="text-2xl font-semibold">ブログ</h1>
      <p class="text-sm mt-2">キャッチフレーズアッシュ</p>
    </div>
  </header>

  <main class="container mx-auto mt-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <section class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-4">最新記事</h2>
        <ul>
          <li class="mb-4">
            <a href="#">記事タイトル1</a>
            <p class="text-gray-700 text-sm">記事の抜粋</p>
          </li>
          <li class="mb-4">
            <a href="#">記事タイトル2</a>
            <p class="text-gray-700 text-sm">記事の抜粋</p>
          </li>
          <li>
            <a href="#">記事タイトル3</a>
            <p class="text-gray-700 text-sm">記事の抜粋</p>
          </li>
        </ul>
      </section>
      <section class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-4">カテゴリー</h2>
        <ul>
          <li class="mb-2"><a href="#">カテゴリー1</a></li>
          <li class="mb-2"><a href="#">カテゴリー2</a></li>
          <li class="mb-2"><a href="#">カテゴリー3</a></li>
          <li><a href="#">カテゴリー4</a></li>
        </ul>
        <h2 class="text-2xl font-semibold mb-4 mt-8">サイドバー</h2>
        </section>
    </div>
  </main>

  <footer class="bg-gray-200 p-4 mt-8">
    <div class="container mx-auto text-center text-gray-600">
      &copy; 2024 あなたのブログ. All rights reserved.
    </div>
  </footer>
</body>
</html>
