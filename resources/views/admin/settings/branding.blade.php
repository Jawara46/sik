@extends('layouts.app')

@section('title', 'Pengaturan Branding - SIK-T')

@section('content')
@php
  $resolveMediaUrl = static function (?string $path, string $fallback): string {
      if (!is_string($path) || $path === '') {
          return asset($fallback);
      }
      if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:'])) {
          return $path;
      }
      $normalized = ltrim($path, '/');
      if (\Illuminate\Support\Str::startsWith($normalized, ['assets/', 'storage/'])) {
          return asset($normalized);
      }
      return asset('storage/' . $normalized);
  };
@endphp
<div class="row g-6">
  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-1">Pengaturan Branding</h5>
        <p class="text-muted mb-0">Kelola aset visual utama tanpa harus membuka formulir profil sekolah penuh.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="row g-5">
          @csrf

          @foreach ([
            'logo' => ['label' => 'Logo Sekolah', 'fallback' => 'assets/img/logo.png'],
            'kop_surat' => ['label' => 'Kop Surat Sekolah', 'fallback' => 'assets/img/illustrations/auth-login-illustration-light.png'],
            'ttd_kepsek' => ['label' => 'TTD Kepala Sekolah', 'fallback' => 'assets/img/illustrations/auth-login-illustration-light.png'],
            'stempel_sekolah' => ['label' => 'Stempel Sekolah', 'fallback' => 'assets/img/illustrations/auth-login-illustration-light.png'],
            'bg_countdown' => ['label' => 'Background Countdown/Login', 'fallback' => 'assets/img/illustrations/auth-login-illustration-light.png'],
          ] as $field => $config)
            <div class="col-md-6 col-xl-4">
              <label class="form-label">{{ $config['label'] }}</label>
              <div class="border rounded p-3 h-100">
                <img src="{{ $resolveMediaUrl($school->{$field}, $config['fallback']) }}" alt="{{ $config['label'] }}" class="img-fluid rounded border mb-3" style="height: 140px; width: 100%; object-fit: contain; background: #fff;">
                <input type="file" name="{{ $field }}" class="form-control">
              </div>
            </div>
          @endforeach

          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="use_digital_stamp" name="use_digital_stamp" value="1" @checked(old('use_digital_stamp', $school->use_digital_stamp))>
              <label class="form-check-label" for="use_digital_stamp">Gunakan stempel digital pada dokumen PDF</label>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-between flex-wrap gap-3">
            <a href="{{ route('admin.school.profile.index') }}" class="btn btn-outline-secondary">Buka Profil Sekolah Lengkap</a>
            <button type="submit" class="btn btn-primary">Simpan Branding</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
