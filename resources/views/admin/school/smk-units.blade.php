@extends('layouts.app')

@section('title', 'Master Unit Kompetensi - SIK-T')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
@endpush

@section('content')
<div class="row g-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h5 class="card-title mb-1">Master Unit Kompetensi</h5>
          <p class="text-muted mb-0">Kelola daftar unit kompetensi (SKKNI) yang diujikan pada tiap jurusan SMK.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($majors->isNotEmpty())
            <a href="{{ route('admin.school.smk-units.template.download') }}" class="btn btn-outline-primary">
              <i class="ri-download-2-line me-1"></i> Unduh Excel
            </a>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalImport">
              <i class="ri-upload-2-line me-1"></i> Upload Excel
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUnit">
              <i class="ri-add-line me-1"></i> Tambah Unit
            </button>
            @endif
        </div>
      </div>

      <div class="card-body border-bottom pt-0">
          <form method="GET" action="{{ route('admin.school.smk-units.index') }}" class="row g-3 align-items-end">
              <div class="col-12 col-md-5">
                  <label for="major_id_filter" class="form-label">Tampilkan Unit dari Jurusan:</label>
                  <select name="major_id" id="major_id_filter" class="form-select" onchange="this.form.submit()">
                      @if($majors->isEmpty())
                          <option value="">Belum ada jurusan yang terdaftar</option>
                      @endif
                      @foreach($majors as $major)
                          <option value="{{ $major->id }}" {{ (int) $selectedMajorId === $major->id ? 'selected' : '' }}>
                              {{ $major->name }} ({{ $major->code }})
                          </option>
                      @endforeach
                  </select>
              </div>
          </form>
          
          @if (session('status'))
              <div class="alert alert-success alert-dismissible mt-3 mb-0" role="alert">
                  {{ session('status') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          @endif
          @if ($errors->any())
              <div class="alert alert-danger alert-dismissible mt-3 mb-0" role="alert">
                  {{ $errors->first() }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          @endif
      </div>

      @if($majors->isNotEmpty())
      <div class="card-datatable table-responsive">
        <table class="table" id="unitsTable">
          <thead>
            <tr>
              <th>No</th>
              <th>Kode Unit</th>
              <th>Judul Unit</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($units as $index => $unit)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="fw-semibold">{{ $unit->kode_unit }}</td>
              <td>{{ $unit->judul_unit }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill btn-edit-unit" 
                          data-id="{{ $unit->id }}"
                          data-kode="{{ $unit->kode_unit }}"
                          data-judul="{{ $unit->judul_unit }}"
                          title="Edit">
                    <i class="ri-edit-box-line ri-20px"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-delete-unit" 
                          data-id="{{ $unit->id }}" 
                          title="Hapus">
                    <i class="ri-delete-bin-7-line ri-20px"></i>
                  </button>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="card-body text-center py-5">
          <i class="ri-building-2-line ri-4x text-muted mb-3 d-block"></i>
          <h5>Tidak Ada Jurusan</h5>
          <p class="text-muted">Tambahkan jurusan terlebih dahulu di menu Profil Sekolah > Jurusan.</p>
      </div>
      @endif
    </div>
  </div>
</div>

@if($majors->isNotEmpty())
<!-- Modal Add Unit -->
<div class="modal fade" id="modalAddUnit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddUnitLabel">Tambah Unit Kompetensi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.school.smk-units.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="add_major_id">Pilih Jurusan</label>
            <select id="add_major_id" name="major_id" class="form-select" required>
                @foreach($majors as $major)
                    <option value="{{ $major->id }}" {{ (int) $selectedMajorId === $major->id ? 'selected' : '' }}>
                        {{ $major->name }} ({{ $major->code }})
                    </option>
                @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="kode_unit">Kode Unit (SKKNI)</label>
            <input type="text" class="form-control" id="kode_unit" name="kode_unit" placeholder="Misal: J.620100.009.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="judul_unit">Judul Unit</label>
            <input type="text" class="form-control" id="judul_unit" name="judul_unit" placeholder="Misal: Menggunakan Spesifikasi Program" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Unit -->
<div class="modal fade" id="modalEditUnit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditUnitLabel">Edit Unit Kompetensi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditUnit" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
            <div class="alert alert-info py-2"><i class="ri-information-line me-2"></i> Perubahan pada Master SKKNI ini akan meresap ke semua hasil print sertifikat SMK.</div>
          <div class="mb-3 mt-3">
            <label class="form-label" for="edit_kode_unit">Kode Unit</label>
            <input type="text" class="form-control" id="edit_kode_unit" name="kode_unit" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="edit_judul_unit">Judul Unit</label>
            <input type="text" class="form-control" id="edit_judul_unit" name="judul_unit" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Form -->
<form id="formDeleteUnit" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal Import Unit -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportLabel">Upload Excel Master Unit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.school.smk-units.import.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="alert alert-warning py-2">
             <i class="ri-error-warning-line me-2"></i> Pastikan Anda mengunggah file yang sesuai (*Template Unduhan*). Karena sistem menggunakan nama Worksheet untuk mencocokkan ID Jurusan.
          </div>
          <div class="mb-3 mt-3">
            <label for="excel_file" class="form-label">File Excel</label>
            <input type="file" id="excel_file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Mulai Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('unitsTable');
    if (table && typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.DataTable !== 'undefined') {
        window.jQuery(table).DataTable({
          pageLength: 25,
          order: [[1, 'asc']],
          language: {
            search: 'Cari Unit:',
            lengthMenu: '_MENU_',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            infoEmpty: 'Belum ada data',
            zeroRecords: 'Data tidak ditemukan'
          }
        });
    }

    // Edit Delegation
    $(document).on('click', '.btn-edit-unit', function() {
        const btn = $(this);
        const id = btn.data('id');
        
        document.getElementById('edit_kode_unit').value = btn.data('kode');
        document.getElementById('edit_judul_unit').value = btn.data('judul');
        
        const form = document.getElementById('formEditUnit');
        form.action = `{{ url('admin/school/smk-units') }}/${id}`;
        
        new bootstrap.Modal(document.getElementById('modalEditUnit')).show();
    });

    // Delete Delegation
    $(document).on('click', '.btn-delete-unit', function() {
        if(confirm('Yakin ingin menghapus Unit ini? Data nilai siswa yang merujuk pada unit ini akan ikut terhapus otomatis.')) {
            const id = $(this).data('id');
            const form = document.getElementById('formDeleteUnit');
            form.action = `{{ url('admin/school/smk-units') }}/${id}`;
            form.submit();
        }
    });

  });
</script>
@endpush
