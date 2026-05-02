<meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />


    <meta name="description" content="" />

    <!-- Favicon -->
    @php
        $faviconUrl = $uiSettingsUrl['app_logo'] ?? asset('assets/img/logo.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset("assets/vendor/fonts/iconify-icons.css") }}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css -->

    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/node-waves/node-waves.css") }}" />

    <script src="{{ asset("assets/vendor/libs/@algolia/autocomplete-js.js") }}"></script>

    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/pickr/pickr-themes.css") }}" />

    <link rel="stylesheet" href="{{ asset("assets/vendor/css/core.css") }}" />
    <link rel="stylesheet" href="{{ asset("assets/css/demo.css") }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css") }}" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/apex-charts/apex-charts.css") }}" />
    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/swiper/swiper.css") }}" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset("assets/vendor/css/pages/cards-statistics.css") }}" />

    <!-- Helpers -->
    <script src="{{ asset("assets/vendor/js/helpers.js") }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js. -->
    <script src="{{ asset("assets/vendor/js/template-customizer.js") }}"></script>

    <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

    <script src="{{ asset("assets/js/config.js") }}"></script>
