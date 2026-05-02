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

  <div class="d-flex flex-column flex-lg-row min-vh-100" style="position: relative; z-index: 2;">

    <!-- LEFT TEXT -->
    <div class="d-flex col-12 col-lg-7 align-items-center justify-content-center align-items-lg-start px-4 px-lg-5 mt-5 pt-5 mt-lg-0 pt-lg-0 info-section-wrapper">
      <div class="lp-info-card">
        <div class="icon-wrapper d-none d-sm-flex">
          <i class="ri-graduation-cap-fill"></i>
        </div>
        <div class="info-content text-center text-sm-start">
          <h3 class="gradient-text" style="font-size: clamp(1.3rem, 3vw, 1.6rem);">Portal Kelulusan Digital</h3>
          <p style="font-size: clamp(0.95rem, 2vw, 1.15rem);">
            Cek status kelulusan dan unduh dokumen
            <span class="highlight">Surat Keterangan Lulus (SKL)</span> serta
            <span class="highlight">Transkrip Nilai</span> secara mandiri melalui portal ini.
          </p>
        </div>
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

    /* INFO CARD */
    .lp-info-card {
      max-width: 600px;
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-radius: 24px;
      padding: 2.2rem;
      border: 1px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 20px 40px rgba(30, 64, 175, 0.08),
                  inset 0 0 0 1px rgba(255, 255, 255, 0.5);
      display: flex;
      gap: 1.5rem;
      align-items: flex-start;
      transform: translateY(0);
      transition: all 0.4s ease;
      animation: floatUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      opacity: 0;
    }

    .lp-info-card:hover {
      transform: translateY(-5px) scale(1.01);
      box-shadow: 0 25px 50px rgba(30, 64, 175, 0.12);
      background: rgba(255, 255, 255, 0.85);
    }

    .lp-info-card .icon-wrapper {
      width: 64px;
      height: 64px;
      flex-shrink: 0;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
      animation: pulseGlow 2s infinite alternate;
    }

    .lp-info-card .icon-wrapper i {
      font-size: 2.2rem;
      color: #fff;
    }

    .lp-info-card .info-content h3 {
      font-size: 1.6rem;
      font-weight: 800;
      margin-bottom: 0.6rem;
      background: linear-gradient(135deg, #0f172a, #1e4ed8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -0.5px;
    }

    .lp-info-card .info-content p {
      color: #475569;
      font-size: 1.15rem;
      line-height: 1.7;
      margin: 0;
      font-weight: 500;
    }

    .lp-info-card .info-content .highlight {
      color: #1e4ed8;
      font-weight: 700;
      background: rgba(59, 130, 246, 0.1);
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
      display: inline-block;
      margin-bottom: 2px;
      transition: background 0.3s ease;
    }

    .lp-info-card:hover .highlight {
      background: rgba(59, 130, 246, 0.15);
    }

    @keyframes floatUp {
      0% {
        opacity: 0;
        transform: translateY(40px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulseGlow {
      0% {
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
      }
      100% {
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.5);
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
    .info-section-wrapper {
      padding-top: 3rem;
      margin-bottom: -1rem;
    }

    @media (min-width: 992px) {
      .info-section-wrapper {
        padding-top: 11rem !important;
        padding-left: 3.5rem !important;
        margin-bottom: 0;
      }
    }

    @media (max-width: 575px) {
      .lp-info-card {
        padding: 1.5rem;
        border-radius: 20px;
      }
      .lp-brand {
        top: 16px;
        left: 20px;
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