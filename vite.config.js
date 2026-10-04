import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    watch: {
      ignored: ['**/storage/**', '**/vendor/**', '**/bootstrap/cache/**', '**/public/build/**'],
    },
  },
  plugins: [
    laravel({
      input: [
              // Public site (Tailwind)
              'resources/css/app.css',
              'resources/css/frontend.css',
              'resources/js/app.js',

        'resources/css/admin-auth.scss',
        'resources/js/admin-auth.js',

        // Vuexy admin — core theme
        'resources/css/admin-font.css',
        'resources/css/rtl.css',
        'resources/assets/css/demo.css',
        'resources/assets/vendor/fonts/tabler-icons.scss',
        'resources/assets/vendor/fonts/fontawesome.scss',
        'resources/assets/vendor/fonts/flag-icons.scss',
        'resources/assets/vendor/scss/rtl/core.scss',
        'resources/assets/vendor/scss/rtl/theme-default.scss',
        'resources/assets/vendor/scss/rtl/core-dark.scss',
        'resources/assets/vendor/scss/rtl/theme-default-dark.scss',
        'resources/assets/vendor/scss/rtl/theme-bordered.scss',
        'resources/assets/vendor/scss/rtl/theme-bordered-dark.scss',
        'resources/assets/vendor/scss/rtl/theme-semi-dark.scss',
        'resources/assets/vendor/scss/rtl/theme-semi-dark-dark.scss',

        // Vuexy admin — libs
        'resources/assets/vendor/libs/node-waves/node-waves.scss',
        'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
        'resources/assets/vendor/libs/typeahead-js/typeahead.scss',

        // Vuexy admin — JS
        'resources/assets/vendor/libs/jquery/jquery.js',
        'resources/assets/vendor/libs/popper/popper.js',
        'resources/assets/vendor/js/bootstrap.js',
        'resources/assets/vendor/libs/node-waves/node-waves.js',
        'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
        'resources/assets/vendor/libs/hammer/hammer.js',
        'resources/assets/vendor/libs/typeahead-js/typeahead.js',
        'resources/assets/vendor/js/menu.js',
        'resources/assets/vendor/js/helpers.js',
        'resources/assets/vendor/js/template-customizer.js',
        'resources/assets/js/config.js',
        'resources/assets/js/main.js',
      ],
      refresh: true,
    }),
    tailwindcss(),
  ],
});
