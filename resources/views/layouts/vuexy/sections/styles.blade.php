<!-- BEGIN: Theme CSS-->
{{-- Admin fonts are self-hosted via resources/css/admin-font.css (Vazirmatn / @fontsource) --}}

@vite(['resources/assets/vendor/fonts/tabler-icons.scss',
'resources/assets/vendor/fonts/fontawesome.scss',
'resources/assets/vendor/fonts/flag-icons.scss',
'resources/css/admin-font.css'])
<!-- Core CSS -->
@vite(['resources/assets/vendor/scss'.$configData['rtlSupport'].'/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/vendor/scss'.$configData['rtlSupport'].'/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
'resources/assets/css/demo.css',
'resources/css/admin-menu.css',
'resources/css/admin-pages.css'])

@vite([
  'resources/assets/vendor/libs/node-waves/node-waves.scss',
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss'
])

<!-- Vendor Styles -->
@yield('vendor-style')


<!-- Page Styles -->
@yield('page-style')
