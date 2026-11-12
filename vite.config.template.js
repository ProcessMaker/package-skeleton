/* eslint-disable import/no-extraneous-dependencies */
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js';
import { resolve } from 'node:path';
import convertToMixManifest from './resources/vite2webpack';

const libraryName = 'package-skeleton';

export const buildScreenBuilderConfig = ({ input, manifestFileName }) => defineConfig({
  base: `/vendor/processmaker/packages/${libraryName}`,
  resolve: {
    alias: [
      {
        find: '@',
        replacement: resolve(process.cwd(), 'resources/js'),
      },
      {
        find: 'vue',
        replacement: resolve(process.cwd(), 'node_modules/vue/dist/vue.esm.js'),
      },
    ],
    extensions: ['.mjs', '.js', '.vue', '.json'],
  },
  build: {
    sourcemap: false,
    manifest: manifestFileName,
    target: 'esnext',
    outDir: 'public',
    emptyOutDir: false,
    rollupOptions: {
      input,
      external: ['vue'],
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) return 'css/[name][extname]';
          return 'assets/[name][extname]';
        },
        globals: {
          vue: 'Vue',
        },
        format: 'iife',
        name: 'PackageSkeletonScreenBuilder',
      },
    },
  },
  plugins: [
    cssInjectedByJsPlugin({ relativeCSSInjection: true }),
    vue(),
    convertToMixManifest({
      outDir: resolve(process.cwd(), 'public'),
      baseDir: `vendor/processmaker/packages/${libraryName}`,
      addManifest: manifestFileName,
    }),
  ],
});
