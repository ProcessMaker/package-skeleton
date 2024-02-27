/* eslint-disable import/no-extraneous-dependencies */
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';
import convertToMixManifest from './resources/vite2webpack';

const isStorybook = process.env.STORYBOOK;

export default defineConfig({
  resolve: {
    alias: [
      {
        find: '@',
        replacement: resolve(__dirname, 'resources/js'),
      },
      {
        find: 'vue',
        replacement: resolve(__dirname, 'node_modules/vue/dist/vue.esm.js'),
      },
    ],
  },
  build: {
    sourcemap: true,
    outDir: 'public', // Set the public directory for output
    rollupOptions: {
      input: ['resources/js/package.js', 'resources/css/package.css'],
      output: [
        {
          entryFileNames: 'js/[name].js', // Output structure for JS
          chunkFileNames: 'js/[name].js',
          assetFileNames: (assetInfo) => {
            if (assetInfo.name.endsWith('.css')) return 'css/[name][extname]';
            return 'assets/[name][extname]';
          },
        },
      ],
    },
  },
  plugins: [
    !isStorybook ? laravel({
      input: [
        'resources/css/package.css',
        'resources/js/package.js',
      ],
    }) : undefined,
    vue({
      version: 2,
      jsx: false,
    }),
    convertToMixManifest({
      outDir: resolve(__dirname, 'public'),
      baseDir: 'vendor/processmaker/packages/package-skeleton',
    }),
  ],
  server: {
    hmr: {
      host: 'localhost',
    },
  },
});
