@extends('layouts.app')

@section('title', __('app.account.profile') . ' - SIK-T')

@section('content')
@php
  $adminAvatar = $admin?->avatar_url ?? asset('assets/img/avatars/1.png');
  $activeTab = in_array($activeTab ?? 'profile', ['profile', 'security'], true) ? $activeTab : 'profile';
@endphp
<div class="row g-6">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-4">
        <img
          src="{{ $adminAvatar }}"
          alt="{{ $admin?->name ?: 'Administrator' }}"
          class="rounded-circle"
          id="adminProfileHeroAvatar"
          width="72"
          height="72">
        <div>
          <h4 class="mb-1">{{ __('app.profile.title') }}</h4>
          <p class="mb-1 text-muted">{{ __('app.profile.subtitle') }}</p>
          <span class="badge bg-label-primary rounded-pill">{{ __('app.account.super_admin') }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <ul class="nav nav-pills flex-wrap gap-2" role="tablist">
      <li class="nav-item" role="presentation">
        <a
          class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}"
          href="{{ route('admin.profile.index', ['tab' => 'profile']) }}"
          role="tab"
          aria-selected="{{ $activeTab === 'profile' ? 'true' : 'false' }}">
          {{ __('app.profile.profile_card') }}
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a
          class="nav-link {{ $activeTab === 'security' ? 'active' : '' }}"
          href="{{ route('admin.profile.index', ['tab' => 'security']) }}"
          role="tab"
          aria-selected="{{ $activeTab === 'security' ? 'true' : 'false' }}">
          {{ __('app.profile.security_card') }}
        </a>
      </li>
    </ul>
  </div>

  <div class="col-12 {{ $activeTab === 'profile' ? '' : 'd-none' }}" id="profile-tab-panel">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">{{ __('app.profile.profile_card') }}</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.profile.update') }}" method="POST" class="row g-5" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="col-12">
            <label for="avatar" class="form-label">{{ __('app.profile.avatar') }}</label>
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-4">
              <img
                src="{{ $adminAvatar }}"
                alt="{{ $admin?->name ?: 'Administrator' }}"
                id="adminProfileAvatarPreview"
                data-default-src="{{ $adminAvatar }}"
                class="rounded-circle border"
                width="84"
                height="84"
                style="object-fit: cover;">
              <div class="w-100">
                <input
                  type="file"
                  id="avatar"
                  name="avatar"
                  class="form-control @error('avatar') is-invalid @enderror"
                  accept=".jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block mt-2">{{ __('app.profile.avatar_help') }}</small>
                @error('avatar')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="d-flex flex-wrap gap-2 mt-3">
                  @if (!empty($admin?->avatar))
                    <button
                      type="submit"
                      class="btn btn-outline-danger"
                      form="deleteAdminAvatarForm"
                      onclick="return confirm('{{ __('app.profile.delete_avatar_confirm') }}');">
                      {{ __('app.profile.delete_avatar') }}
                    </button>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label for="name" class="form-label">{{ __('app.profile.name') }}</label>
            <input
              type="text"
              id="name"
              name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name', $admin?->name) }}"
              required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="email" class="form-label">{{ __('app.profile.email') }}</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control @error('email') is-invalid @enderror"
              value="{{ old('email', $admin?->email) }}"
              required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="nomor_wa" class="form-label">{{ __('app.profile.phone') }}</label>
            <input
              type="text"
              id="nomor_wa"
              name="nomor_wa"
              class="form-control @error('nomor_wa') is-invalid @enderror"
              value="{{ old('nomor_wa', $admin?->nomor_wa) }}"
              placeholder="62812xxxxxxx"
              required>
            <small class="text-muted d-block mt-2">{{ __('app.profile.phone_help') }}</small>
            @error('nomor_wa')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="verifikasi_password" class="form-label">{{ __('app.profile.profile_verification_password') }}</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="verifikasi_password"
                name="verifikasi_password"
                class="form-control @error('verifikasi_password') is-invalid @enderror"
                required>
              <button
                type="button"
                class="input-group-text cursor-pointer"
                data-password-toggle
                data-target="#verifikasi_password"
                data-label-show="{{ __('app.password_toggle.show') }}"
                data-label-hide="{{ __('app.password_toggle.hide') }}">
                <i class="icon-base ri ri-eye-line icon-20px"></i>
              </button>
            </div>
            <small class="text-muted d-block mt-2">{{ __('app.profile.profile_verification_password_help') }}</small>
            @error('verifikasi_password')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label for="alamat" class="form-label">{{ __('app.profile.address') }}</label>
            <textarea
              id="alamat"
              name="alamat"
              rows="4"
              class="form-control @error('alamat') is-invalid @enderror"
              placeholder="{{ __('app.profile.address_placeholder') }}">{{ old('alamat', $admin?->alamat) }}</textarea>
            @error('alamat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-primary">{{ __('app.profile.save_profile') }}</button>
          </div>
        </form>

        @if (!empty($admin?->avatar))
          <form id="deleteAdminAvatarForm" action="{{ route('admin.profile.avatar.destroy') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
          </form>
        @endif
      </div>
    </div>
  </div>

  <div class="col-12 {{ $activeTab === 'security' ? '' : 'd-none' }}" id="security-card">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">{{ __('app.profile.security_card') }}</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.profile.password.update') }}" method="POST" class="row g-5">
          @csrf
          @method('PUT')

          <div class="col-12">
            <label for="password_lama" class="form-label">{{ __('app.profile.old_password') }}</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="password_lama"
                name="password_lama"
                class="form-control @error('password_lama') is-invalid @enderror"
                required>
              <button
                type="button"
                class="input-group-text cursor-pointer"
                data-password-toggle
                data-target="#password_lama"
                data-label-show="{{ __('app.password_toggle.show') }}"
                data-label-hide="{{ __('app.password_toggle.hide') }}">
                <i class="icon-base ri ri-eye-line icon-20px"></i>
              </button>
            </div>
            @error('password_lama')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label for="password_baru" class="form-label">{{ __('app.profile.new_password') }}</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="password_baru"
                name="password_baru"
                class="form-control @error('password_baru') is-invalid @enderror"
                minlength="8"
                required>
              <button
                type="button"
                class="input-group-text cursor-pointer"
                data-password-toggle
                data-target="#password_baru"
                data-label-show="{{ __('app.password_toggle.show') }}"
                data-label-hide="{{ __('app.password_toggle.hide') }}">
                <i class="icon-base ri ri-eye-line icon-20px"></i>
              </button>
            </div>
            @error('password_baru')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label for="konfirmasi_password" class="form-label">{{ __('app.profile.confirm_password') }}</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="konfirmasi_password"
                name="konfirmasi_password"
                class="form-control @error('konfirmasi_password') is-invalid @enderror"
                required>
              <button
                type="button"
                class="input-group-text cursor-pointer"
                data-password-toggle
                data-target="#konfirmasi_password"
                data-label-show="{{ __('app.password_toggle.show') }}"
                data-label-hide="{{ __('app.password_toggle.hide') }}">
                <i class="icon-base ri ri-eye-line icon-20px"></i>
              </button>
            </div>
            @error('konfirmasi_password')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-primary">{{ __('app.profile.save_password') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('adminProfileAvatarPreview');
    const heroAvatar = document.getElementById('adminProfileHeroAvatar');

    if (!input || !preview || !heroAvatar) {
      return;
    }

    const defaultSrc = preview.dataset.defaultSrc || preview.getAttribute('src') || '';

    input.addEventListener('change', function (event) {
      const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

      if (!file) {
        preview.src = defaultSrc;
        heroAvatar.src = defaultSrc;
        return;
      }

      if (!file.type.startsWith('image/')) {
        preview.src = defaultSrc;
        heroAvatar.src = defaultSrc;
        return;
      }

      const objectUrl = URL.createObjectURL(file);
      preview.src = objectUrl;
      heroAvatar.src = objectUrl;

      preview.onload = function () {
        URL.revokeObjectURL(objectUrl);
      };
    });
  });
</script>
@endpush
