@extends('layouts.app')

@section('title', 'Status Kelulusan - SIK-T')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="card-header">
        <span class="badge bg-label-primary mb-3">Layanan Kelulusan</span>
        <h4 class="card-title mb-1">Status Kelulusan</h4>
        <p class="mb-0 text-muted">Kelola status `Lulus / Tidak Lulus / Pending` dan buka atau kunci akses unduh dokumen siswa.</p>
      </div>
      <div class="card-body">
        @if (session('status'))
          <div class="alert alert-success mb-4">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger mb-4">{{ session('error') }}</div>
        @endif

        <div class="row g-4 mb-4">
          <div class="col-sm-6 col-lg-2">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Total Siswa</small>
              <h5 class="mb-0">{{ $summary['total_students'] }}</h5>
            </div>
          </div>
          <div class="col-sm-6 col-lg-2">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Lulus</small>
              <h5 class="mb-0 text-success">{{ $summary['lulus'] }}</h5>
            </div>
          </div>
          <div class="col-sm-6 col-lg-2">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Tidak Lulus</small>
              <h5 class="mb-0 text-danger">{{ $summary['tidak_lulus'] }}</h5>
            </div>
          </div>
          <div class="col-sm-6 col-lg-2">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Pending</small>
              <h5 class="mb-0 text-warning">{{ $summary['pending'] }}</h5>
            </div>
          </div>
          <div class="col-sm-6 col-lg-2">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Akses Aktif</small>
              <h5 class="mb-0 text-primary">{{ $summary['access_open'] }}</h5>
            </div>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.graduation.status.index') }}" class="row g-3 align-items-end mb-4">
          <div class="col-md-5">
            <label class="form-label">Cari Siswa</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nama siswa atau NISN">
          </div>
          <div class="col-md-auto">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
          </div>
        </form>

        <form id="bulk-graduation-form" method="POST" action="{{ route('admin.graduation.status.bulk.update') }}">
          @csrf
          <div class="border rounded p-3 mb-4">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Bulk Status Kelulusan</label>
                <select name="status" class="form-select">
                  <option value="Lulus">Lulus</option>
                  <option value="Tidak Lulus">Tidak Lulus</option>
                  <option value="Pending">Pending</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Bulk Akses Unduh</label>
                <select name="is_locked" class="form-select">
                  <option value="0">Buka Akses</option>
                  <option value="1">Kunci Akses</option>
                </select>
              </div>
              <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">Bulk Ubah Status</button>
                <button type="submit" class="btn btn-outline-warning" formaction="{{ route('admin.graduation.status.bulk-access') }}">
                  Bulk Ubah Akses
                </button>
              </div>
            </div>
          </div>

        </form>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th width="44">
                  <input type="checkbox" class="form-check-input" id="select-all-students">
                </th>
                <th>Siswa</th>
                <th>NISN</th>
                <th>Jurusan</th>
                <th>Status</th>
                <th>Akses</th>
                <th class="text-end">Aksi Cepat</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                <tr>
                  <td>
                    <input type="checkbox" class="form-check-input student-checkbox" name="student_ids[]" value="{{ $student->id }}" form="bulk-graduation-form">
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $student->name }}</div>
                  </td>
                  <td>{{ $student->nisn }}</td>
                  <td>{{ $student->major?->code ?? '-' }}</td>
                  <td>
                    <span class="badge {{ $student->status === 'Lulus' ? 'bg-label-success' : ($student->status === 'Tidak Lulus' ? 'bg-label-danger' : 'bg-label-warning') }}">
                      {{ $student->status ?? 'Pending' }}
                    </span>
                  </td>
                  <td>
                    <span class="badge {{ (!$student->access_locked && $student->status_administrasi) ? 'bg-label-success' : 'bg-label-secondary' }}">
                      {{ (!$student->access_locked && $student->status_administrasi) ? 'Terbuka' : 'Terkunci' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                      <form method="POST" action="{{ route('admin.graduation.status.student.update', $student) }}">
                        @csrf
                        <div class="input-group input-group-sm">
                          <select name="status" class="form-select">
                            <option value="Lulus" @selected($student->status === 'Lulus')>Lulus</option>
                            <option value="Tidak Lulus" @selected($student->status === 'Tidak Lulus')>Tidak Lulus</option>
                            <option value="Pending" @selected($student->status === 'Pending')>Pending</option>
                          </select>
                          <button type="submit" class="btn btn-outline-primary">Simpan</button>
                        </div>
                      </form>

                      <form method="POST" action="{{ route('admin.graduation.status.student.access', $student) }}">
                        @csrf
                        <input type="hidden" name="is_locked" value="{{ $student->access_locked ? 0 : 1 }}">
                        <button type="submit" class="btn btn-sm {{ $student->access_locked ? 'btn-success' : 'btn-outline-danger' }}">
                          {{ $student->access_locked ? 'Buka Akses' : 'Kunci Akses' }}
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Belum ada data siswa yang dapat dikelola.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all-students');
    const checkboxes = document.querySelectorAll('.student-checkbox');

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
          checkbox.checked = selectAll.checked;
        });
      });
    }
  });
</script>
@endpush
