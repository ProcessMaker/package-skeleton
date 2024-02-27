const mix = require('laravel-mix');

mix.vue({ version: 2 });

mix.setPublicPath('public').webpackConfig({
  externals: {
    SharedComponents: 'SharedComponents',
    vue: 'Vue',
    '@processmaker/vue-form-elements': 'VueFormElements',
  },
})
  .js('resources/js/package.js', 'js')
  .vue()
  .css('resources/css/package.css', 'css')
  .version();
