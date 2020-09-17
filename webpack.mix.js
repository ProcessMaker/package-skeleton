const mix = require('laravel-mix');

/*
Your Processmaker Plugin webpack utilizes laravel mix in order to easily compile
your static resources.  Refer to the Laravel Mix documentation at
https://laravel.com/docs/5.6/mix for information on how to configure
mix to compile additional resources.

The compiled resources will be put in
your plugin's public folder which can then be published. For information
on how to publish your plugin's resources, review
https://laravel.com/docs/5.6/packages#public-assets for information.  Your plugin
service provider located at src/PluginServiceProvider.php already has the public
folder configured for publishing by default.
 */

mix.setPublicPath('public')
    .js('resources/js/package.js', 'js')
    .sass('resources/sass/package.scss', 'css')
    .version()
    .then(() => {
        try {
            require('./webpack.callback')();
        } catch (e) {
            //No callback found
        }
    });
