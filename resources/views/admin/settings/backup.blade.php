@extends('layouts.app')

@section('title', 'Backup Database - SIK-T')

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

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
          <h5 class="card-title mb-1">Backup Database</h5>
          <p class="mb-0 text-muted">Snapshot database <strong>{{ $databaseName }}</strong> untuk pemulihan dan audit.</p>
        </div>
        <form method="POST" action="{{ route('admin.settings.backup.create') }}">
          @csrf
          <button type="submit" class="btn btn-primary">Buat Backup Baru</button>
        </form>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>File</th>
                <th>Ukuran</th>
                <th>Terakhir Dibuat</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($backups as $backup)
                <tr>
                  <td>{{ $backup['filename'] }}</td>
                  <td>{{ number_format((int) $backup['size']) }} byte</td>
                  <td>{{ \Carbon\Carbon::createFromTimestamp((int) $backup['last_modified'])->format('d M Y H:i') }}</td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-2">
                      <a href="{{ route('admin.settings.backup.download', ['filename' => $backup['filename']]) }}" class="btn btn-sm btn-outline-primary">Unduh</a>
                      <form method="POST" action="{{ route('admin.settings.backup.delete', ['filename' => $backup['filename']]) }}" onsubmit="return confirm('Hapus file backup ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Belum ada file backup database.</td>
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
