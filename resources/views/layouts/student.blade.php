<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/') }}/">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Portal Siswa - SIK-T')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.materialize.head')
    @stack('styles')
</head>
<body style="background:#f6f8fc;">
    <div class="container-xxl py-4 py-md-5">
        @yield('content')
    </div>
    @include('components.materialize.scripts')
    @stack('scripts')
</body>
</html>
