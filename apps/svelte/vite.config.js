import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fusion from '@fusion/vue/vite';
import testExtractor from '@fusion/vue/testExtractor';

export default defineConfig({
  plugins: [
    testExtractor({
      output: 'playwright/tests/extracted'
    }),
    fusion(),
    laravel({
      input: 'resources/js/app.js',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    })
  ],
});
