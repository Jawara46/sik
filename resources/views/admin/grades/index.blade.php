@extends('layouts.app')

@section('title', 'Manajemen Nilai - SIK-T')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<style>
  .grade-status-badge {
    min-width: 128px;
  }

  .grade-card-muted {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.08), rgba(40, 199, 111, 0.08));
    border: 1px dashed rgba(115, 103, 240, 0.2);
  }
</style>
@endpush

@section('content')
<div class="row g-6">
  <div class="col-12 col-xl-5">
    <div class="card h-100">
      <div class="card-header pb-3">
        <h5 class="card-title mb-1">Template Nilai</h5>
        <p class="text-muted mb-0">Pilih template sesuai kebutuhan input. Sistem import akan mendeteksi otomatis jenis
          file yang diunggah.</p>
      </div>
      <div class="card-body d-flex flex-column gap-4">
        <div class="grade-card-muted rounded-4 p-4">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Jumlah Siswa</span>
            <strong>{{ $availability['students'] }}</strong>
          </div>
          <div class="d-flex justify-content-between mb-0">
            <span class="text-muted">Jumlah Mata Pelajaran</span>
            <strong>{{ $availability['subjects'] }}</strong>
          </div>
        </div>

        <div class="d-grid gap-3">
          <button type="button" 
            class="btn btn-primary {{ $availability['can_download'] ? '' : 'disabled' }}" 
            @if ($availability['can_download']) data-bs-toggle="modal" data-bs-target="#downloadTemplateModal" @else aria-disabled="true" @endif>
            <i class="ri-download-2-line me-2"></i> Unduh Template Nilai
          </button>
        </div>

        <div class="small text-muted mt-auto">
          Template <strong>Nilai Akhir</strong> berisi 1 kolom nilai per mapel.<br>
          Template <strong>Leger</strong> berisi 6 worksheet terpisah: <strong>SMT1</strong> sampai
          <strong>SMT6</strong>.<br>
          Kolom <strong>NISN</strong>, <strong>Nama Siswa</strong>, dan <strong>Kode Jurusan</strong> dikunci agar hanya area nilai yang bisa diedit.
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-7">
    <div class="card h-100">
      <div class="card-header pb-3">
        <h5 class="card-title mb-1">Upload Nilai</h5>
        <p class="text-muted mb-0">Unggah file Excel hasil isian guru/admin. Sistem akan memvalidasi rentang nilai 0-100
          dan NISN siswa.</p>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.grades.import.store') }}" method="POST" enctype="multipart/form-data"
          class="row g-4">
          @csrf
          <div class="col-12">
            <label for="grade_file" class="form-label">File Excel Nilai</label>
            <input type="file" id="grade_file" name="grade_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            <small class="text-muted d-block mt-2">Format akan dibedakan otomatis antara nilai akhir dan leger
              multi-sheet semester.</small>
          </div>
          <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
            <button type="submit" class="btn btn-success">Upload Nilai</button>
            @if (session('import_result'))
            <span class="badge bg-label-info rounded-pill text-uppercase">
              Terdeteksi: {{ session('import_result.template_type') === 'leger' ? 'Leger 1-6' : 'Nilai Akhir' }}
            </span>
            @endif
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h5 class="card-title mb-1">Daftar Nilai Siswa</h5>
          <p class="text-muted mb-0">Pantau kelengkapan pengisian leger semester dan buka halaman edit manual per siswa.
          </p>
        </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table border-top" id="gradesStatusTable">
          <thead>
            <tr>
              <th style="width: 50px;">No.</th>
              <th>NISN</th>
              <th>Nama Siswa</th>
              <th>Jurusan</th>
              <th>Mapel Aktif</th>
              <th>Progress Leger</th>
              <th>Nilai Akhir</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($studentStatuses as $item)
            <tr>
              <td>{{ $loop->iteration }}.</td>
              <td>{{ $item['student']->nisn }}</td>
              <td>
                <div class="fw-semibold">{{ $item['student']->name }}</div>
                <small class="text-muted">{{ $item['student']->major?->name ?? 'Umum' }}</small>
              </td>
              <td>{{ $item['major_label'] }}</td>
              <td>{{ $item['applicable_subjects'] }}</td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <span class="fw-semibold">{{ $item['filled_semesters'] }} / {{ $item['expected_semesters'] }}</span>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-{{ $item['status_class'] }}" role="progressbar"
                      style="width: {{ $item['completion_percentage'] }}%;"
                      aria-valuenow="{{ $item['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
              </td>
              <td>{{ $item['final_subjects_count'] }} mapel</td>
              <td>
                <span class="badge bg-label-{{ $item['status_class'] }} grade-status-badge">
                  {{ $item['status_label'] }} ({{ $item['completion_percentage'] }}%)
                </span>
              </td>
              <td>
                <a href="{{ route('admin.grades.students.edit', $item['student']) }}"
                  class="btn btn-sm btn-outline-primary">
                  <i class="ri-pencil-line me-1"></i>Detail/Edit
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if (session('import_log'))
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h6 class="card-title mb-1">Log Baris Dilewati</h6>
      </div>
      <div class="card-body">
        <ul class="mb-0 ps-4">
          @foreach ((array) session('import_log') as $item)
          <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
  @endif
</div>

<!-- Modal Download Template -->
<div class="modal fade" id="downloadTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="downloadTemplateModalLabel">Unduh Template Nilai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.grades.template.download') }}" method="GET">
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label d-block text-body">Jenis Template</label>
            <div class="form-check form-check-inline mt-2">
              <input class="form-check-input" type="radio" name="type" id="type_leger" value="leger" checked>
              <label class="form-check-label" for="type_leger">Leger Lengkap (Smt 1-6)</label>
            </div>
            <div class="form-check form-check-inline mt-2">
              <input class="form-check-input" type="radio" name="type" id="type_final" value="final">
              <label class="form-check-label" for="type_final">Nilai Akhir Saja</label>
            </div>
          </div>
          
          @if ($school->tipe_sekolah === 'SMK')
          <div class="mb-3">
            <label for="major_id" class="form-label">Filter Jurusan (Opsional)</label>
            <select class="form-select" id="major_id" name="major_id">
              <option value="">-- Semua Jurusan --</option>
              @foreach ($majors as $major)
                <option value="{{ $major->id }}">{{ $major->name }} ({{ $major->code }})</option>
              @endforeach
            </select>
            <div class="form-text">
              Jika jurusan dipilih, template hanya akan memuat siswa dari jurusan tersebut, beserta mata pelajaran Umum dan mata pelajaran terkait jurusannya saja.
            </div>
          </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" onclick="setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('downloadTemplateModal')).hide(); }, 500)">Unduh File Excel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /Modal -->
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('gradesStatusTable');
    if (!table || typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
      return;
    }

    window.jQuery(table).DataTable({
      pageLength: 25,
      order: [[2, 'asc']], // Order by Name (previously index 1, now 2)
      columnDefs: [
        { orderable: false, targets: [0, 7] } // No. and Aksi are not orderable
      ],
      language: {
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ siswa',
        infoEmpty: 'Belum ada data nilai',
        zeroRecords: 'Data siswa tidak ditemukan',
        paginate: {
          previous: 'Prev',
          next: 'Next'
        }
      }
    });
  });
</script>
@endpush
