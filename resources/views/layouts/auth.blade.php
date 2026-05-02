<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/') }}/" data-template="vertical-menu-template" data-style="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - SIK-T</title>
    
    <!-- Meta CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('components.materialize.head')
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
    
    <!-- Custom CSS: Landing Page Theme -->
    <style>
        /* ── Full-screen background ── */
        .lp-bg {
            position: fixed;
            inset: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 0;
        }

        .lp-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
              linear-gradient(to right, rgba(220, 230, 248, 0.15) 0%, rgba(220, 230, 248, 0.05) 60%, rgba(255, 255, 255, 0.65) 100%),
              linear-gradient(to top, rgba(26, 54, 126, 0.3) 0%, transparent 30%);
            z-index: 1;
        }

        /* ── Auth Card: Glassmorphism ── */
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.15);
            border: none;
            position: relative;
            z-index: 2;
        }
        .auth-card h4       { color: #1a366e !important; }
        .auth-card p        { color: #4a5e8a !important; }
        .auth-card label    { color: #3b5280 !important; }
        .auth-card .form-check-label { color: #3b5280 !important; }
        .auth-card .divider-text     { color: #8094b8 !important; }
        .auth-card code { color: #1a366e; background: #e8eef8; padding: 2px 8px; border-radius: 6px; font-size: .82rem; }
        .auth-card small.text-muted  { color: #6b82ad !important; }

        /* Inputs */
        .auth-card .form-control {
            background: #f0f4fa;
            color: #1a366e;
            border-color: #d1dbed;
            border-radius: 12px;
            font-size: 0.95rem;
            padding: 0.65rem 1rem;
        }
        .auth-card .form-control:focus {
            background: #fff;
            color: #1a366e;
            border-color: #3b5bb0;
            box-shadow: 0 0 0 3px rgba(59, 91, 176, 0.15);
        }
        .auth-card .form-control::placeholder { color: #8ca0c4; }

        /* Fix browser autofill */
        .auth-card .form-control:-webkit-autofill,
        .auth-card .form-control:-webkit-autofill:hover,
        .auth-card .form-control:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #f0f4fa inset !important;
            -webkit-text-fill-color: #1a366e !important;
            border-color: #d1dbed;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Toggle password button */
        .auth-card .input-group-text {
            background: #f0f4fa;
            border-color: #d1dbed;
            border-left: none;
            color: #8ca0c4;
            cursor: pointer;
            border-radius: 0 12px 12px 0;
        }
        .auth-card .input-group-text:hover { color: #3b5bb0; background: #e4ecf7; }
        .auth-card .input-group .form-control { border-radius: 12px 0 0 12px; border-right: none; }

        /* Floating label fix */
        .auth-card .form-floating label { color: #8ca0c4 !important; }
        .auth-card .form-floating .form-control:focus ~ label,
        .auth-card .form-floating .form-control:not(:placeholder-shown) ~ label {
            color: #3b5bb0 !important;
        }

        /* ── Primary Button (blue matching bg) ── */
        .auth-card .btn-primary {
            background: linear-gradient(135deg, #1a366e 0%, #3b5bb0 100%) !important;
            border: none !important;
            border-radius: 12px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 20px rgba(26, 54, 110, 0.35);
            transition: all 0.3s ease;
        }
        .auth-card .btn-primary:hover {
            background: linear-gradient(135deg, #15295a 0%, #2f4a96 100%) !important;
            box-shadow: 0 6px 28px rgba(26, 54, 110, 0.5);
            transform: translateY(-1px);
        }

        /* ── Alert ── */
        .auth-card .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 12px;
        }

        /* ── Left hero panel ── */
        .lp-hero-panel {
            position: relative;
            z-index: 2;
            color: #fff;
        }

        .lp-hero-panel h1 {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1.15;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.4);
        }

        .lp-hero-panel .lp-subtitle {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
        }

        .lp-feature-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.82rem;
            color: #fff;
            font-weight: 500;
        }

        /* ── Brand (top-left) ── */
        .lp-brand {
            position: fixed;
            top: 1.5rem;
            left: 2rem;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .lp-brand-text {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        /* ── Glass panel (countdown) ── */
        .glass-panel {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.25);
        }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .lp-hero-panel { display: none !important; }
            .auth-card { border-radius: 16px; padding: 32px 24px; }
        }

        /* ── Subtle float animation ── */
        @keyframes floatUp {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .lp-float { animation: floatUp 4s ease-in-out infinite; }
    </style>
</head>
<body>
    <!-- Content -->
    @yield('content')
    <!-- / Content -->

    <!-- Auth Footer Overlay -->
    <div style="position: fixed; bottom: 0; width: 100%; z-index: 10;">
        @include('components.materialize.footer')
    </div>

    @include('components.materialize.scripts')
    
    <!-- Page JS -->
    <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>

    <!-- Password Toggle: robust implementation -->
    <script>
    (function () {
        function initPasswordToggle() {
            document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
                if (btn._pwToggleInit) return;
                btn._pwToggleInit = true;

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var targetSel = btn.getAttribute('data-target') || btn.getAttribute('data-bs-target');
                    var input = targetSel ? document.querySelector(targetSel) : null;

                    if (!input) {
                        var group = btn.closest('.input-group');
                        if (group) input = group.querySelector('input[type="password"], input[type="text"]');
                    }
                    if (!input) return;

                    var icon = btn.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        btn.setAttribute('title', 'Sembunyikan password');
                        if (icon) { icon.classList.remove('ri-eye-line'); icon.classList.add('ri-eye-off-line'); }
                    } else {
                        input.type = 'password';
                        btn.setAttribute('title', 'Tampilkan password');
                        if (icon) { icon.classList.remove('ri-eye-off-line'); icon.classList.add('ri-eye-line'); }
                    }
                    input.focus();
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPasswordToggle);
        } else {
            initPasswordToggle();
        }
    })();
    </script>
    @stack('scripts')
</body>
</html>
