// vite.config.js
Laravel9.10用

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
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
});
