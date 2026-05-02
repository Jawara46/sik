@extends('layouts.app')

@section('title', 'Mata Pelajaran - SIK-T')

@section('content')
<div class="row">
  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h5 class="card-title mb-1">Mata Pelajaran</h5>
          <p class="mb-0 text-muted">Master mapel untuk pemetaan kurikulum dan template nilai.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <form method="GET" action="{{ route('admin.school.subjects.index') }}" class="d-flex gap-2">
            <input
              type="text"
              name="q"
              class="form-control"
              placeholder="Cari mapel"
              value="{{ $keyword }}"
            >
            <button type="submit" class="btn btn-outline-primary">Cari</button>
          </form>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            Tambah Mata Pelajaran
          </button>
          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importSubjectModal">
            Import
          </button>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Nama Mata Pelajaran</th>
                <th>Kategori</th>
                @if ($isSmk)
                  <th>Mapping Jurusan</th>
                @endif
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($subjects as $subject)
                <tr>
                  <td class="fw-medium">{{ $subject->name }}</td>
                  <td><span class="badge bg-label-primary">{{ $subject->category }}</span></td>
                  @if ($isSmk)
                    <td>
                      @if ($subject->majors->isEmpty())
                        <span class="text-muted">Umum (semua jurusan)</span>
                      @else
                        {{ $subject->majors->pluck('code')->implode(', ') }}
                      @endif
                    </td>
                  @endif
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#editSubjectModal-{{ $subject->id }}"
                    >
                      Edit
                    </button>
                    <form
                      action="{{ route('admin.school.subjects.destroy', $subject) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Hapus mata pelajaran ini?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $isSmk ? 4 : 3 }}" class="text-center py-5 text-muted">Belum ada data mata pelajaran.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: {{ $subjects->total() }} mapel</small>
        {{ $subjects->links() }}
      </div>
    </div>
  </div>
</div>

@if (session('subject_import_log'))
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h6 class="card-title mb-1">Log Import Mata Pelajaran</h6>
        </div>
        <div class="card-body">
          <ul class="mb-0">
            @foreach ((array) session('subject_import_log') as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.school.subjects.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="subject_name_create">Nama Mata Pelajaran</label>
            <input id="subject_name_create" type="text" name="name" class="form-control" required>
          </div>
          <div>
            <label class="form-label" for="subject_category_create">Kategori</label>
            <select id="subject_category_create" name="category" class="form-select" required>
              @foreach ($categories as $category)
                <option value="{{ $category }}">{{ $category }}</option>
              @endforeach
            </select>
          </div>
          @if ($isSmk)
            <div class="mt-3">
              <label class="form-label" for="subject_major_create">Mapping Jurusan (opsional)</label>
              <select id="subject_major_create" name="major_ids[]" class="form-select" multiple>
                @foreach ($majors as $major)
                  <option value="{{ $major->id }}">{{ $major->name }} ({{ $major->code }})</option>
                @endforeach
              </select>
              <small class="text-muted">Kosongkan jika mapel berlaku untuk semua jurusan.</small>
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="importSubjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.school.subjects.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Import Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <a href="{{ route('admin.school.subjects.template.download') }}" class="btn btn-outline-primary btn-sm mb-3">
            Download Template Excel
          </a>
          @if ($isSmk)
            <p class="small text-muted mb-3">Isi kolom <strong>Kode Jurusan</strong> dengan kode seperti `AKL` atau `AKL,BDP`.</p>
          @endif
          <div>
            <label class="form-label" for="subject_file">Upload File Excel</label>
            <input id="subject_file" type="file" name="subject_file" class="form-control" accept=".xlsx,.xls,.csv" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-success">Upload & Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

@foreach ($subjects as $subject)
  <div class="modal fade" id="editSubjectModal-{{ $subject->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.school.subjects.update', $subject) }}">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Mata Pelajaran</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama Mata Pelajaran</label>
              <input type="text" name="name" class="form-control" value="{{ $subject->name }}" required>
            </div>
            <div>
              <label class="form-label">Kategori</label>
              <select name="category" class="form-select" required>
                @foreach ($categories as $category)
                  <option value="{{ $category }}" @selected($subject->category === $category)>{{ $category }}</option>
                @endforeach
              </select>
            </div>
            @if ($isSmk)
              <div class="mt-3">
                <label class="form-label">Mapping Jurusan (opsional)</label>
                <select name="major_ids[]" class="form-select" multiple>
                  @foreach ($majors as $major)
                    <option value="{{ $major->id }}" @selected($subject->majors->contains('id', $major->id))>
                      {{ $major->name }} ({{ $major->code }})
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Kosongkan jika mapel berlaku untuk semua jurusan.</small>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const openModal = @json($openModal);
    if (openModal === 'import') {
      const element = document.getElementById('importSubjectModal');
      if (element && window.bootstrap) {
        const modal = new bootstrap.Modal(element);
        modal.show();
      }
    }
  });
</script>
@endpush
