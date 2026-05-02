@extends('layouts.app')

@section('title', 'Tahun Akademik - SIK-T')

@section('content')
<div class="row g-6">
  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-1">Periode Akademik Aktif</h5>
        <p class="text-muted mb-0">Atur tahun pelajaran, semester, dan jadwal rilis pengumuman dari satu halaman ringkas.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.school.academic-years.update') }}" class="row g-4">
          @csrf
          <div class="col-md-6">
            <label class="form-label">Tahun Pelajaran</label>
            <input type="text" name="tahun_pelajaran" class="form-control" value="{{ old('tahun_pelajaran', $school->tahun_pelajaran) }}" placeholder="2025/2026" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select" required>
              <option value="Ganjil" @selected(old('semester', $school->semester) === 'Ganjil')>Ganjil</option>
              <option value="Genap" @selected(old('semester', $school->semester) === 'Genap')>Genap</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Jadwal Rilis Pengumuman</label>
            <input type="datetime-local" name="announcement_date" class="form-control" value="{{ old('announcement_date', $announcementAt?->format('Y-m-d\TH:i')) }}">
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Simpan Periode Akademik</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="card-title mb-1">Ringkasan Aktif</h6>
      </div>
      <div class="card-body d-flex flex-column gap-4">
        <div class="border rounded p-3">
          <small class="text-muted d-block">Tahun Pelajaran</small>
          <strong>{{ $school->tahun_pelajaran ?: '-' }}</strong>
        </div>
        <div class="border rounded p-3">
          <small class="text-muted d-block">Semester</small>
          <strong>{{ $school->semester ?: '-' }}</strong>
        </div>
        <div class="border rounded p-3">
          <small class="text-muted d-block">Rilis Pengumuman</small>
          <strong>{{ $announcementAt?->translatedFormat('d F Y H:i') ?? '-' }}</strong>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
