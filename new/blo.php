<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>あなたのブログダッシュボード</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- ファビコンの追加 -->
    <link rel="icon" href="<?php echo asset('images/macho-icon.ico'); ?>" type="image/x-icon">
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-500 p-4 text-white">
        <div class="container mx-auto">
            <h1 class="text-2xl font-semibold">あなたのブログ</h1>
        </div>
    </header>

    <main class="container mx-auto mt-8">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-4xl font-semibold mb-6 text-center">ダッシュボードへようこそ</h1>

            <p class="text-gray-700 text-center mb-6">ここには、ダッシュボードの説明が表示されます。</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-500 hover:bg-blue-600 text-white py-4 px-6 rounded-lg text-center">
                    <h2 class="text-lg font-semibold mb-2">ボタン1</h2>
                    <p class="text-sm">ここには、ボタン1の説明が表示されます。</p>
                </div>

                <div class="bg-green-500 hover:bg-green-600 text-white py-4 px-6 rounded-lg text-center">
                    <h2 class="text-lg font-semibold mb-2">ボタン2</h2>
                    <p class="text-sm">ここには、ボタン2の説明が表示されます。</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-200 p-4 mt-8">
        <div class="container mx-auto text-center text-gray-600">
            &copy; 2024 あなたのブログ. All rights reserved.
        </div>
    </footer>
</body>
</html>
