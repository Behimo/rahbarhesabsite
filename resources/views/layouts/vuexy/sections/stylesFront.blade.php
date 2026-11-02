<!-- BEGIN: Theme CSS-->
{{-- Front/admin fonts are self-hosted; Google Fonts CDN removed --}}

@vite(['resources/assets/vendor/fonts/tabler-icons.scss'])
<!-- Core CSS -->
@vite(['resources/assets/vendor/scss/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/vendor/scss/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/css/demo.css',
'resources/assets/vendor/libs/node-waves/node-waves.scss',
'resources/assets/vendor/scss/pages/front-page.scss'])

<!-- Vendor Styles -->
@yield('vendor-style')


<!-- Page Styles -->
@yield('page-style')
