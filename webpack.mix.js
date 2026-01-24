const mix = require('laravel-mix');
const webpack = require('webpack'); // Agregar esta línea

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
    
    // Dashboard Assets (Admin/Client/Superadmin)
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
    
    // Web Assets (Public Landing Pages)
    .styles([
            'resources/css/web/landing.css'
            ], 'public/css/web.css')
    .scripts([
            'resources/js/web/components/stats-counter.js',
            'resources/js/web/lazy-loading.js',
            'resources/js/web/landing.js',
            'resources/js/web/contact-form.js'
            ], 'public/js/web.js')
    
    // Development test script (only in development)
    .when(!mix.inProduction(), () => {
        mix.scripts([
            'resources/js/web/test-lazy-loading.js'
        ], 'public/js/web-test.js');
    })
    
    .vue()
    .sourceMaps()
    
    // Enable versioning for cache busting in production
    .version()
    
    // Configure options for better cache busting
    .options({
        processCssUrls: false, // Disable processing of CSS urls for faster builds
        hmrOptions: {
            host: 'localhost',
            port: 8080
        }
    });

mix.webpackConfig({
        plugins: [
            new webpack.DefinePlugin({
                __VUE_OPTIONS_API__: true,
                __VUE_PROD_DEVTOOLS__: false
            })
        ]
    });


// Configuración de BrowserSync para desarrollo
if (!mix.inProduction()) {
    mix.browserSync({
        proxy: 'j2b.test',
        open: 'external',
        browser: 'brave-browser',
        notify: false,
        reloadOnRestart: true
    });
}