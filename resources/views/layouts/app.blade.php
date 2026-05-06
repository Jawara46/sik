<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/') }}/" data-template="vertical-menu-template" data-style="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title', 'SIK - YAZID DIGITAL')</title>
    
    <!-- Meta CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('components.materialize.head')
    @stack('styles')
    <style>
        #back-to-top {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1000;
            display: none;
            width: 45px;
            height: 45px;
            text-align: center;
            line-height: 45px;
            background: #7367f0;
            color: #fff;
            cursor: pointer;
            border-radius: 50%;
            border: none;
            box-shadow: 0 4px 12px rgba(115, 103, 240, 0.4);
            transition: all 0.3s ease;
        }
        #back-to-top:hover {
            transform: translateY(-5px);
            background: #5e50ee;
            box-shadow: 0 6px 18px rgba(115, 103, 240, 0.5);
        }
        #back-to-top i {
            font-size: 24px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu Sidebar -->
            @include('components.materialize.sidebar')
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar / Header -->
                @include('components.materialize.navbar')
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content Area -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <!-- / Content Area -->

                    <!-- Footer -->
                    @include('components.materialize.footer')
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
        
        <!-- Back to Top FAB -->
        <button id="back-to-top" title="Scroll ke Atas">
            <i class="ri ri-arrow-up-line"></i>
        </button>
    </div>
    <!-- / Layout wrapper -->

    @include('components.materialize.scripts')
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backToTop = document.getElementById('back-to-top');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            });

            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
