@extends('layouts.app')

@section('title', 'Daftar Siswa - SIK-T')

@section('content')
@php
  $resolveMediaUrl = static function (?string $path): ?string {
      if (!is_string($path) || $path === '') {
          return null;
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
          <h5 class="card-title mb-1">Daftar Siswa</h5>
          <p class="mb-0 text-muted">Data siswa untuk proses SKL, transkrip, dan notifikasi WhatsApp.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <form method="GET" action="{{ route('admin.students.index') }}" class="d-flex gap-2">
            <input
              type="text"
              name="q"
              class="form-control"
              placeholder="Cari NISN / Nama"
              value="{{ $keyword }}"
            >
            <button type="submit" class="btn btn-outline-primary">Cari</button>
          </form>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            Tambah Siswa
          </button>
          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importStudentModal">
            Import
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>NISN</th>
                <th>Nama</th>
                <th>Pas Foto</th>
                @if ($isSmk)
                  <th>Jurusan</th>
                @endif
                <th>Tempat, Tanggal Lahir</th>
                <th>Nama Ortu</th>
                <th>Status WA</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                <tr>
                  <td class="fw-medium">{{ $student->nisn }}</td>
                  <td>{{ $student->name }}</td>
                  <td>
                    @if ($resolveMediaUrl($student->photo))
                      <img
                        src="{{ $resolveMediaUrl($student->photo) }}"
                        alt="Pas Foto {{ $student->name }}"
                        class="rounded border"
                        style="width: 48px; height: 64px; object-fit: cover;"
                      >
                    @else
                      <span class="badge bg-label-warning">Belum Ada</span>
                    @endif
                  </td>
                  @if ($isSmk)
                    <td>
                      @if ($student->major)
                        {{ $student->major->name }} ({{ $student->major->code }})
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                  @endif
                  <td>
                    @php
                      $tempat = $student->tempat_lahir ?: '-';
                      $tanggal = $student->tanggal_lahir ? $student->tanggal_lahir->format('d-m-Y') : '-';
                    @endphp
                    {{ $tempat }}, {{ $tanggal }}
                  </td>
                  <td>{{ $student->nama_orang_tua ?: '-' }}</td>
                  <td>
                    @if (!empty($student->nomor_wa))
                      <span class="badge bg-label-success">Terhubung</span>
                      <span class="text-muted ms-1">{{ $student->nomor_wa }}</span>
                    @else
                      <span class="badge bg-label-warning">Belum Diisi</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#editStudentModal-{{ $student->id }}"
                    >
                      Edit
                    </button>
                    <form
                      action="{{ route('admin.students.destroy', $student) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Hapus data siswa ini?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $isSmk ? 8 : 7 }}" class="text-center py-5 text-muted">
                    Data siswa belum tersedia.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: {{ $students->total() }} siswa</small>
        {{ $students->links() }}
      </div>
    </div>
  </div>
</div>

@if (session('student_import_log'))
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h6 class="card-title mb-1">Log Import Data Siswa</h6>
        </div>
        <div class="card-body">
          <ul class="mb-0">
            @foreach ((array) session('student_import_log') as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Siswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">NISN</label>
              <input type="text" name="nisn" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">NIS</label>
              <input type="text" name="nis" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Nama Siswa</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            @if ($isSmk)
              <div class="col-md-6">
                <label class="form-label">Jurusan</label>
                <select name="major_id" class="form-select">
                  <option value="">- Pilih Jurusan -</option>
                  @foreach ($majors as $major)
                    <option value="{{ $major->id }}">{{ $major->name }} ({{ $major->code }})</option>
                  @endforeach
                </select>
              </div>
            @endif
            <div class="col-md-6">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" name="tempat_lahir" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" name="tanggal_lahir" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Orang Tua</label>
              <input type="text" name="nama_orang_tua" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nomor WA</label>
              <input type="text" name="nomor_wa" class="form-control" placeholder="08xxxxxxxxxx">
            </div>
            <div class="col-md-6">
              <label class="form-label">Pas Foto</label>
              <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,image/*">
              <small class="text-muted">Format `jpg`, `jpeg`, atau `png`. Maksimal 2 MB.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Akses Unduh Dokumen</label>
              <select name="status_administrasi" class="form-select">
                <option value="1">Terbuka</option>
                <option value="0">Terkunci</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Siswa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="importStudentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Import Data Siswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <a href="{{ route('admin.students.template.download') }}" class="btn btn-outline-primary btn-sm mb-3">
            Download Template Excel
          </a>
          @if ($isSmk)
            <p class="small text-muted mb-3">Isi kolom <strong>Kode Jurusan</strong> dengan kode jurusan, contoh: `AKL`.</p>
          @endif
          <div>
            <label class="form-label" for="student_file">Upload File Excel</label>
            <input id="student_file" type="file" name="student_file" class="form-control" accept=".xlsx,.xls,.csv" required>
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

@foreach ($students as $student)
  <div class="modal fade" id="editStudentModal-{{ $student->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Siswa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">NISN</label>
                <input type="text" name="nisn" class="form-control" value="{{ $student->nisn }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">NIS</label>
                <input type="text" name="nis" class="form-control" value="{{ $student->nis }}">
              </div>
              <div class="col-12">
                <label class="form-label">Nama Siswa</label>
                <input type="text" name="name" class="form-control" value="{{ $student->name }}" required>
              </div>
              @if ($isSmk)
                <div class="col-md-6">
                  <label class="form-label">Jurusan</label>
                  <select name="major_id" class="form-select">
                    <option value="">- Pilih Jurusan -</option>
                    @foreach ($majors as $major)
                      <option value="{{ $major->id }}" @selected((int) $student->major_id === (int) $major->id)>
                        {{ $major->name }} ({{ $major->code }})
                      </option>
                    @endforeach
                  </select>
                </div>
              @endif
              <div class="col-md-6">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control" value="{{ $student->tempat_lahir }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" value="{{ optional($student->tanggal_lahir)->format('Y-m-d') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Nama Orang Tua</label>
                <input type="text" name="nama_orang_tua" class="form-control" value="{{ $student->nama_orang_tua }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Nomor WA</label>
                <input type="text" name="nomor_wa" class="form-control" value="{{ $student->nomor_wa }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Pas Foto</label>
                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                @if ($resolveMediaUrl($student->photo))
                  <div class="mt-2">
                    <img
                      src="{{ $resolveMediaUrl($student->photo) }}"
                      alt="Preview Pas Foto {{ $student->name }}"
                      class="rounded border"
                      style="width: 72px; height: 96px; object-fit: cover;"
                    >
                  </div>
                @else
                  <small class="text-muted d-block mt-2">Pas foto belum diunggah.</small>
                @endif
              </div>
              <div class="col-md-6">
                <label class="form-label">Status Kelulusan</label>
                <select name="status" class="form-select" required>
                  <option value="Pending" @selected($student->status === 'Pending')>Pending</option>
                  <option value="Lulus" @selected($student->status === 'Lulus')>Lulus</option>
                  <option value="Tidak Lulus" @selected($student->status === 'Tidak Lulus')>Tidak Lulus</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Akses Unduh Dokumen</label>
                <select name="status_administrasi" class="form-select">
                  <option value="1" @selected((bool) $student->status_administrasi === true)>Terbuka</option>
                  <option value="0" @selected((bool) $student->status_administrasi === false)>Terkunci</option>
                </select>
              </div>
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
@endforeach
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const openModal = @json($openModal);
    if (openModal === 'import') {
      const element = document.getElementById('importStudentModal');
      if (element && window.bootstrap) {
        const modal = new bootstrap.Modal(element);
        modal.show();
      }
    }
  });
</script>
@endpush
