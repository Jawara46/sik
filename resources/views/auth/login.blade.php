@extends('layouts.auth')

@section('content')
  @php
    $bgUrl = $uiSettingsUrl['background_image'] ?? asset('assets/img/bg-lp.jpg');
    $logoUrl = $uiSettingsUrl['app_logo'] ?? asset('assets/img/logo.png');
  @endphp

  <!-- Background -->
  <div class="lp-bg" style="background-image: url('{{ $bgUrl }}');"></div>

  <!-- Brand -->
  <a href="{{ url('/') }}" class="lp-brand">
    <img src="{{ $logoUrl }}" height="32">
    <span class="lp-brand-text">{{ $schoolName ?? 'SIK-T' }}</span>
  </a>

  <div class="d-flex min-vh-100" style="position: relative; z-index: 2;">

    <!-- LEFT TEXT -->
    <div class="d-none d-lg-flex col-lg-7 align-items-start px-5" style="padding-left: 3.5rem !important; padding-top: 9rem !important;">
      <div class="lp-info-box lp-animate">
        <p class="mb-0">
          Cek status kelulusan dan unduh dokumen
          <strong>Surat Keterangan Lulus (SKL)</strong> serta
          <strong>Transkrip Nilai</strong> Anda secara mandiri melalui portal ini.
        </p>
      </div>
    </div>

    <!-- LOGIN -->
    <div class="d-flex col-12 col-lg-5 align-items-center justify-content-center p-4 p-sm-5">
      <div class="auth-card w-100" style="max-width: 420px;">

        <div class="d-lg-none text-center mb-4">
          <img src="{{ $logoUrl }}" height="48">
        </div>

        <h4 class="mb-1">Selamat Datang di SIK! 👋</h4>
        <p class="mb-4">Silakan login menggunakan <strong>NISN</strong> dan <strong>Password</strong></p>

        @if ($errors->any())
          <div class="alert alert-danger mb-4">
            <strong>Login Gagal!</strong><br>
            <small>{{ $errors->first() }}</small>
          </div>
        @endif

        <form id="studentLoginForm" action="{{ url('/login') }}" method="POST">
          @csrf

          <div class="mb-4">
            <label class="form-label fw-semibold">NISN</label>
            <input type="text" class="form-control" name="nisn" placeholder="Masukkan NISN Anda" required>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <input type="password" id="password" class="form-control" name="password"
                placeholder="Masukkan password Anda" required>
              <button type="button" class="input-group-text" id="togglePassword">
                <i class="ri ri-eye-line" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" name="remember">
            <label class="form-check-label">Ingat Saya</label>
          </div>

          <button class="btn btn-primary w-100 py-2">
            <i class="ri ri-login-box-line me-2"></i>Login
          </button>
        </form>

        <div class="divider my-4">
          <div class="divider-text">Portal Siswa</div>
        </div>

        <div class="d-block d-lg-none mt-4 text-center">
          <small class="text-muted">&copy; {{ date('Y') }} {{ config('sik.developer', 'Yazid Digital') }}<br>SIK-T v{{ config('sik.version', '1.0') }}</small>
        </div>
      </div>
    </div>
  </div>
@endsection


@push('styles')
  <style>
    /* BACKGROUND */
    .lp-bg {
      position: fixed;
      inset: 0;
      background-size: cover;
      background-position: center;
      z-index: 1;
    }

    .lp-bg::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(to right,
          rgba(15, 23, 42, 0.35),
          rgba(15, 23, 42, 0.1));
    }

    /* BRAND */
    .lp-brand {
      position: absolute;
      top: 24px;
      left: 40px;
      z-index: 3;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .lp-brand-text {
      color: white;
      font-weight: 600;
    }

    /* INFO BOX */
    .lp-info-box {
      max-width: 560px;
      background: rgba(15, 40, 100, 0.88);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 2.2rem 2.5rem;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-left: 4px solid rgba(100, 160, 255, 0.7);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25),
                  0 0 60px rgba(59, 130, 246, 0.08);
    }

    .lp-info-box p {
      color: #fff;
      font-size: 1.25rem;
      font-weight: 400;
      line-height: 1.85;
      letter-spacing: 0.3px;
      margin: 0;
    }

    .lp-info-box p strong {
      color: #93c5fd;
      font-weight: 700;
    }

    /* Slide-up fade-in animation */
    .lp-animate {
      animation: lpSlideUp 0.8s ease-out both;
    }

    @keyframes lpSlideUp {
      0% {
        opacity: 0;
        transform: translateY(30px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* CARD */
    .auth-card {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    /* INPUT */
    .form-control {
      border-radius: 12px;
    }

    .input-group-text {
      border-radius: 0 12px 12px 0;
    }

    /* BUTTON */
    .btn-primary {
      background: linear-gradient(135deg, #2F6BFF, #1E4ED8);
      border: none;
      border-radius: 12px;
      box-shadow: 0 10px 20px rgba(47, 107, 255, 0.3);
    }

    /* RESPONSIVE */
    @media (max-width: 991px) {
      .lp-brand {
        display: none !important;
      }
    }
  </style>
@endpush


@push('scripts')
  <script>
    document.getElementById('togglePassword').onclick = function () {
      const input = document.getElementById('password');
      const icon = document.getElementById('eyeIcon');

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('ri-eye-line', 'ri-eye-off-line');
      } else {
        input.type = 'password';
        icon.classList.replace('ri-eye-off-line', 'ri-eye-line');
      }
    };
  </script>
@endpush