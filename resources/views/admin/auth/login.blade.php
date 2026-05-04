@extends('layouts.auth')

@section('content')
@php
    $bgUrl = $uiSettingsUrl['background_image'] ?? asset('assets/img/bg-lp.jpg');
    $logoUrl = $uiSettingsUrl['app_logo'] ?? asset('assets/img/logo.png');
@endphp

<!-- Background -->
<div class="lp-bg" style="background-image: url('{{ $bgUrl }}');"></div>

<!-- Brand Logo -->
<a href="{{ url('/') }}" class="lp-brand">
  <img src="{{ $logoUrl }}" alt="SIK-T" height="32" onerror="this.src='{{ asset('assets/img/favicon/favicon.ico') }}'">
  <span class="lp-brand-text">SIK-T</span>
</a>

<div class="d-flex min-vh-100 align-items-center justify-content-center" style="position: relative; z-index: 2;">
  <div class="auth-card" style="max-width: 440px; width: 100%; margin: 2rem;">

    <!-- Header -->
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
           style="width: 64px; height: 64px; background: linear-gradient(135deg, #1a366e, #3b5bb0); box-shadow: 0 4px 20px rgba(26,54,110,0.3);">
        <i class="ri ri-shield-keyhole-line text-white" style="font-size: 1.8rem;"></i>
      </div>
      <h4 class="mb-1">Login Admin</h4>
      <p class="mb-0">Masuk menggunakan akun admin untuk mengelola seluruh modul SIK-T.</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 p-3" role="alert" style="border-radius: 12px;">
        <i class="ri ri-error-warning-fill mt-1" style="font-size: 1.1rem;"></i>
        <div>
          <strong>Login Gagal!</strong><br>
          <small>{{ $errors->first() }}</small>
        </div>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
      @csrf

      <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Email Admin</label>
        <input
          type="email"
          class="form-control"
          id="email"
          name="email"
          placeholder="Masukkan email admin Anda"
          value="{{ old('email') }}"
          required
          autofocus
        />
      </div>

      <div class="mb-4">
        <label for="password" class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <input
            type="password"
            id="password"
            class="form-control"
            name="password"
            placeholder="Masukkan password Anda"
            required
          />
          <button
            type="button"
            class="input-group-text"
            id="toggleAdminPassword">
            <i class="ri ri-eye-line" id="adminEyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="remember" name="remember" />
          <label class="form-check-label" for="remember">Ingat saya</label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="ri ri-dashboard-line me-2"></i>Masuk Dashboard
      </button>
    </form>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('toggleAdminPassword');
    const input = document.getElementById('password');
    const icon = document.getElementById('adminEyeIcon');
    
    if (btn && input) {
      btn.onclick = function(e) {
        e.preventDefault();
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('ri-eye-line', 'ri-eye-off-line');
        } else {
          input.type = 'password';
          icon.classList.replace('ri-eye-off-line', 'ri-eye-line');
        }
      };
    }
  });
</script>
@endsection
