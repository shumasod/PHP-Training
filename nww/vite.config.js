// vite.config.js
// Laravel 9.10 用
//
// 修正前は 2 行目が "Laravel9.10用" という裸のメモで、
// コメントになっていなかった。JavaScript としては識別子の羅列と
// 解釈されるため、この設定ファイルは読み込めない。
//
//     ✘ [ERROR] Syntax error "\u{7528}"
//         2 │ Laravel9.10用

import path from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      // Laravelの開発サーバーのURLに合わせて設定してください
      '/api': 'http://localhost:8000',
    },
  },
  build: {
    // Laravelのpublicディレクトリにビルドされたファイルを出力する
    outDir: '../public/assets',
    assetsDir: '.',
    manifest: true,
  },
  resolve: {
    alias: {
      // LaravelのresourcesディレクトリにあるVueコンポーネントへのエイリアスを設定する
      // path は上で import している。修正前は import が無く、
      // 2 行目を直したとしても ReferenceError: path is not defined になった。
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
});
