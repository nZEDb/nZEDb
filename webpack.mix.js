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

mix
    .copyDirectory('node_modules/tinymce/themes/', 'www/themes/shared/js/themes/')
    .copyDirectory('node_modules/tinymce/skins/', 'www/themes/shared/js/skins/')
    .copyDirectory('node_modules/tinymce/plugins/', 'www/themes/shared/js/plugins/')
    .copy('node_modules/icheck/skins/flat/green.png', 'www/themes/shared/css/green.png')
    .copy('node_modules/icheck/skins/flat/green@2x.png', 'www/themes/shared/css/green@2x.png')
    .styles(
        [
            'node_modules/bootstrap/dist/css/bootstrap.min.css',
            'node_modules/bootstrap/dist/css/bootstrap-reboot.css',
            'node_modules/icheck/skins/flat/green.css',
            'node_modules/datatables/media/css/jquery.dataTables.min.css',
            'node_modules/animate.css/animate.min.css',
            'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.standalone.min.css',
            'node_modules/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css',
            'node_modules/@fortawesome/fontawesome-free/css/svg-with-js.min.css',
            'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.min.css',
            'node_modules/pnotify/dist/PNotifyBrightTheme.css',
            'node_modules/flexboxgrid2/flexboxgrid2.min.css',
            'node_modules/multiselect/css/multi-select.css',

            'www/themes/shared/css/custom.css'
    ], 'www/themes/shared/css/all-css.css')
    .scripts(
        [
            'node_modules/jquery/dist/jquery.min.js',
            'node_modules/jquery-migrate/dist/jquery-migrate.min.js',
            'node_modules/bootstrap/dist/js/bootstrap.bundle.js',
            'node_modules/@fortawesome/fontawesome-free/js/all.min.js',
            'node_modules/@fortawesome/fontawesome-free/js/v4-shims.min.js',
            'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.min.js',
            'node_modules/datatables/media/js/jquery.dataTables.min.js',
            'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
            'node_modules/autosize/dist/autosize.min.js',
            'node_modules/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js',
            'node_modules/bootstrap-progressbar/bootstrap-progressbar.min.js',
            'node_modules/jquery.nicescroll/dist/jquery.nicescroll.min.js',
            'node_modules/pnotify/dist/umd/PNotify.js',
            'node_modules/pnotify/dist/umd/PNotifyAnimate.js',
            'node_modules/pnotify/dist/umd/PNotifyButtons.js',
            'node_modules/pnotify/dist/umd/PNotifyCallbacks.js',
            'node_modules/pnotify/dist/umd/PNotifyConfirm.js',
            'node_modules/pnotify/dist/umd/PNotifyDesktop.js',
            'node_modules/pnotify/dist/umd/PNotifyNonBlock.js',
            'node_modules/pnotify/dist/umd/PNotifyMobile.js',
            'node_modules/pnotify/dist/umd/PNotifyHistory.js',
            'node_modules/pnotify/dist/umd/PNotifyReference.js',
            'node_modules/tinymce/tinymce.min.js',
            'node_modules/jquery-colorbox/jquery.colorbox-min.js',
            'node_modules/jquery-multifile/jquery.MultiFile.min.js',
            'node_modules/multiselect/js/jquery.multi-select.js',
            'node_modules/pace-js/pace.min.js',
            'node_modules/jquery-slimscroll/jquery.slimscroll.min.js',
            'node_modules/icheck/icheck.min.js',
            'node_modules/jquery-goup/dist/jquery.goup.min.js',
            'www/themes/shared/js/utils-admin.js',
            'www/themes/shared/js/custom.js',
            'www/themes/shared/js/functions.js'
        ]
    , 'www/themes/shared/js/all-js.js')
    .sourceMaps();

