@extends('layouts.app')

@section('title', 'Riwayat Pesan WhatsApp - SIK-T')

@section('content')
@include('admin.whatsapp._nav')
<div class="row g-6">
  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
          <h5 class="card-title mb-1">Riwayat Pesan WhatsApp</h5>
          <p class="mb-0 text-muted">Audit pengiriman pesan keluar dari panel admin.</p>
        </div>
        <div class="d-flex flex-wrap gap-3">
          <span class="badge bg-label-primary">Total {{ $stats['total_logs'] }}</span>
          <span class="badge bg-label-success">Terkirim {{ $stats['sent'] }}</span>
          <span class="badge bg-label-danger">Gagal {{ $stats['failed'] }}</span>
        </div>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('admin.whatsapp.history.index') }}" class="row g-4 mb-5">
          <div class="col-md-3">
            <label class="form-label">Jurusan</label>
            <select name="major_id" class="form-select">
              <option value="">Semua Jurusan</option>
              @foreach ($majors as $major)
                <option value="{{ $major->id }}" @selected($filters['major_id'] == $major->id)>{{ $major->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Semua Status</option>
              <option value="sent" @selected($filters['status'] === 'sent')>Terkirim</option>
              <option value="failed" @selected($filters['status'] === 'failed')>Gagal</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cari</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nama penerima, nomor WA, isi pesan">
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary">Filter Riwayat</button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Penerima</th>
                <th>Jurusan</th>
                <th>Status</th>
                <th>Pesan</th>
                <th>Admin</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($logs as $log)
                <tr>
                  <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                  <td>
                    <div>{{ $log->recipient_name ?? '-' }}</div>
                    <small class="text-muted">{{ $log->recipient_number }}</small>
                  </td>
                  <td>{{ $log->student?->major?->name ?? ($log->meta['major_name'] ?? '-') }}</td>
                  <td>
                    <span class="badge {{ $log->status === 'sent' ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ strtoupper($log->status) }}
                    </span>
                  </td>
                  <td class="text-wrap" style="min-width: 320px;">{{ \Illuminate\Support\Str::limit($log->message, 180) }}</td>
                  <td>{{ $log->adminUser?->name ?? '-' }}</td>
                  <td>
                    @if ($log->status === 'failed')
                    <form action="{{ route('admin.whatsapp.history.retry', $log) }}" method="POST">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-icon btn-label-primary" title="Kirim Ulang">
                        <i class="ri ri-refresh-line"></i>
                      </button>
                    </form>
                    @else
                    -
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Riwayat pesan belum tersedia.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $logs->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
