@extends('layouts.app')

@section('title', $title . ' - SIK-T')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="card-header">
        <span class="badge bg-label-primary mb-3">{{ $module }}</span>
        <h4 class="card-title mb-1">{{ $title }}</h4>
        <p class="mb-0 text-muted">{{ $description }}</p>
      </div>
      <div class="card-body">
        @if (!empty($linkedService))
          <div class="alert alert-info mb-4" role="alert">
            Halaman ini terhubung ke service: <strong>{{ $linkedService }}</strong>.
          </div>
        @endif

        @if (!empty($context['schoolType']))
          <p class="mb-2">
            <strong>Tipe Sekolah Aktif:</strong> {{ $context['schoolType'] }}
          </p>
        @endif

        @if (isset($context['total_students']))
          <div class="row g-4">
            <div class="col-sm-6 col-lg-3">
              <div class="border rounded p-3">
                <small class="text-muted d-block">Total Siswa</small>
                <h5 class="mb-0">{{ $context['total_students'] }}</h5>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="border rounded p-3">
                <small class="text-muted d-block">Lulus</small>
                <h5 class="mb-0 text-success">{{ $context['lulus'] }}</h5>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="border rounded p-3">
                <small class="text-muted d-block">Tidak Lulus</small>
                <h5 class="mb-0 text-danger">{{ $context['tidak_lulus'] }}</h5>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="border rounded p-3">
                <small class="text-muted d-block">Pending</small>
                <h5 class="mb-0 text-warning">{{ $context['pending'] }}</h5>
              </div>
            </div>
          </div>
        @else
          <div class="alert alert-secondary mb-0" role="alert">
            Modul ini siap untuk tahap implementasi berikutnya.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
