const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .styles([
            'resources/templates/coreui/css/font-awesome.min.css',
            'resources/templates/coreui/css/simple-line-icons.min.css',
            'resources/templates/coreui/css/style.css'
            ],'public/css/dashboard.css')
    .scripts([
            'resources/templates/coreui/js/pace.min.js',
            'resources/templates/coreui/js/Chart.min.js',
            'resources/templates/coreui/js/template.js',
            'resources/templates/coreui/js/sweetalert2.all.min.js'
            ],'public/js/dashboard.js')
    .vue()
    .sourceMaps();

 mix.browserSync('http://j2b.local/');