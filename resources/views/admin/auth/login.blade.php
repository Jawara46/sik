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

    <form method="POST" action="{{ route('admin.login') }}" id="adminLoginForm">
      @csrf
      <input type="hidden" name="login_mode" id="login_mode" value="password">

      <div class="mb-4" id="emailGroup">
        <label for="email" class="form-label fw-semibold">Email Admin</label>
        <input
          type="email"
          class="form-control"
          id="email"
          name="email"
          placeholder="Masukkan email admin Anda"
          value="{{ old('email') }}"
          autofocus
        />
      </div>

      <!-- Password Input -->
      <div id="passwordGroup" class="mb-4">
        <label for="password" class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <input
            type="password"
            id="password"
            class="form-control"
            name="password"
            placeholder="Masukkan password Anda"
          />
          <button
            type="button"
            class="input-group-text"
            id="toggleAdminPassword">
            <i class="ri ri-eye-line" id="adminEyeIcon"></i>
          </button>
        </div>
      </div>

      <!-- PIN Input (Hidden by default) -->
      <div id="pinGroup" class="mb-4 d-none">
        <label for="pin" class="form-label fw-semibold">PIN Keamanan (6 Digit)</label>
        <div class="input-group">
          <input
            type="password"
            id="pin"
            class="form-control text-center"
            name="pin"
            maxlength="6"
            placeholder="••••••"
            style="letter-spacing: 0.5rem; font-size: 1.2rem; font-weight: bold;"
          />
          <button
            type="button"
            class="input-group-text"
            id="toggleAdminPin">
            <i class="ri ri-eye-line" id="adminPinEyeIcon"></i>
          </button>
        </div>
        <small class="text-muted mt-2 d-block">Masukkan 6 digit kode PIN yang telah Anda atur.</small>
      </div>

      <!-- Mode Selector (Moved Down) -->
      <div class="d-flex gap-2 mb-4">
        <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 active" id="btnModePassword">
          <i class="ri ri-lock-password-line me-1"></i>Password
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" id="btnModePin">
          <i class="ri ri-key-line me-1"></i>PIN
        </button>
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
    const btnToggle = document.getElementById('toggleAdminPassword');
    const inputPassword = document.getElementById('password');
    const iconEye = document.getElementById('adminEyeIcon');
    
    // Toggle Password Visibility
    if (btnToggle && inputPassword) {
      btnToggle.onclick = function(e) {
        e.preventDefault();
        if (inputPassword.type === 'password') {
          inputPassword.type = 'text';
          iconEye.classList.replace('ri-eye-line', 'ri-eye-off-line');
        } else {
          inputPassword.type = 'password';
          iconEye.classList.replace('ri-eye-off-line', 'ri-eye-line');
        }
      };
    }

    // Toggle PIN Visibility
    const btnTogglePin = document.getElementById('toggleAdminPin');
    const inputPin = document.getElementById('pin');
    const iconPinEye = document.getElementById('adminPinEyeIcon');

    if (btnTogglePin && inputPin) {
      btnTogglePin.onclick = function(e) {
        e.preventDefault();
        if (inputPin.type === 'password') {
          inputPin.type = 'text';
          iconPinEye.classList.replace('ri-eye-line', 'ri-eye-off-line');
        } else {
          inputPin.type = 'password';
          iconPinEye.classList.replace('ri-eye-off-line', 'ri-eye-line');
        }
      };
    }

    // Switch Login Mode (Password vs PIN)
    const btnModePassword = document.getElementById('btnModePassword');
    const btnModePin = document.getElementById('btnModePin');
    const loginModeInput = document.getElementById('login_mode');
    const emailGroup = document.getElementById('emailGroup');
    const passwordGroup = document.getElementById('passwordGroup');
    const pinGroup = document.getElementById('pinGroup');
    const emailInput = document.getElementById('email');

    function showToast(message) {
      const toast = document.createElement('div');
      toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-4 animate__animated animate__fadeInDown';
      toast.style.zIndex = '9999';
      toast.innerHTML = `
        <div class="alert alert-primary shadow-lg d-flex align-items-center gap-2" style="border-radius: 50px; padding: 10px 25px;">
          <i class="ri ri-information-line"></i>
          <span>${message}</span>
        </div>
      `;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.classList.replace('animate__fadeInDown', 'animate__fadeOutUp');
        setTimeout(() => toast.remove(), 1000);
      }, 2500);
    }

    if (btnModePassword && btnModePin) {
      // Default state
      emailInput.setAttribute('required', 'required');

      btnModePassword.onclick = function() {
        loginModeInput.value = 'password';
        emailGroup.classList.remove('d-none');
        passwordGroup.classList.remove('d-none');
        pinGroup.classList.add('d-none');
        btnModePassword.classList.add('active');
        btnModePin.classList.remove('active');
        emailInput.setAttribute('required', 'required');
        inputPassword.setAttribute('required', 'required');
        inputPin.removeAttribute('required');
        emailInput.focus();
      };

      btnModePin.onclick = function() {
        loginModeInput.value = 'pin';
        emailGroup.classList.add('d-none');
        passwordGroup.classList.add('d-none');
        pinGroup.classList.remove('d-none');
        btnModePin.classList.add('active');
        btnModePassword.classList.remove('active');
        emailInput.removeAttribute('required');
        inputPassword.removeAttribute('required');
        inputPin.setAttribute('required', 'required');
        inputPin.focus();
        showToast('Mode PIN Aktif. Masukkan 6 digit kode keamanan.');
      };
    }

    // PIN Numeric Only
    if (inputPin) {
      inputPin.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
    }
  });
</script>
@endsection
