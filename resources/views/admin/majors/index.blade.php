@extends('layouts.app')

@section('title', 'Data Jurusan - SIK-T')

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
      <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
          <h5 class="card-title mb-1">Data Jurusan</h5>
          <p class="mb-0 text-muted">Kelola nama dan kode jurusan untuk identifikasi siswa serta mapping mapel.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <form method="GET" action="{{ route('admin.school.majors.index') }}" class="d-flex gap-2">
            <input
              type="text"
              name="q"
              class="form-control"
              placeholder="Cari jurusan"
              value="{{ $keyword ?? '' }}"
            >
            <button type="submit" class="btn btn-outline-primary">Cari</button>
          </form>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMajorModal">
            Tambah Jurusan
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Nama Jurusan</th>
                <th>Kode Jurusan</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($majors as $major)
                <tr>
                  <td>{{ $major->name }}</td>
                  <td><span class="badge bg-label-primary">{{ $major->code }}</span></td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#editMajorModal-{{ $major->id }}"
                    >
                      Edit
                    </button>
                    <form
                      action="{{ route('admin.school.majors.destroy', $major) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Hapus jurusan ini?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center py-5 text-muted">Belum ada data jurusan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addMajorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.school.majors.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Jurusan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Jurusan</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Kode Jurusan</label>
            <input type="text" name="code" class="form-control text-uppercase" placeholder="AKL" required>
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

@foreach ($majors as $major)
  <div class="modal fade" id="editMajorModal-{{ $major->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.school.majors.update', $major) }}">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Jurusan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama Jurusan</label>
              <input type="text" name="name" class="form-control" value="{{ $major->name }}" required>
            </div>
            <div>
              <label class="form-label">Kode Jurusan</label>
              <input type="text" name="code" class="form-control text-uppercase" value="{{ $major->code }}" required>
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
