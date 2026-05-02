@extends('layouts.app')

@section('title', 'Auto-Respond WA - SIK-T')

@section('content')
@include('admin.whatsapp._nav')
<div class="row g-6">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h5 class="card-title mb-1">Auto-Respond Cek Kelulusan</h5>
          <p class="text-muted mb-0">Siswa dapat mengecek status kelulusan dengan mengirim pesan ke nomor WhatsApp sekolah.</p>
        </div>
        <div class="form-check form-switch bg-light p-2 px-3 rounded border">
          <input class="form-check-input me-2" type="checkbox" id="toggle-auto-respond" @checked($school->enable_wa_auto_respond)>
          <label class="form-check-label fw-bold" for="toggle-auto-respond">Fitur Auto-Respond</label>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info d-flex align-items-start mb-0" role="alert">
          <i class="ri ri-information-line me-2 fs-4"></i>
          <div>
            <h6 class="alert-heading mb-1 fw-bold">Cara Kerja:</h6>
            <p class="mb-2">Siswa dapat mengirim format: <strong class="text-primary">CEK [NISN]</strong>, <strong class="text-primary">#CEKNILAI [NISN]</strong>, atau <strong class="text-primary">STATUS [NISN]</strong>.</p>
            <p class="mb-2 small text-muted">Contoh: CEK 0012345001 atau #CEKNILAI 0012345001</p>
            <p class="mb-0 small">Sistem akan secara otomatis membalas dengan status kelulusan, nama siswa, dan informasi login portal jika NISN ditemukan.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Log Auto-Respond</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Waktu</th>
              <th>Pengirim</th>
              <th>NISN Dicari</th>
              <th>Siswa</th>
              <th>Status</th>
              <th>Respons</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($logs as $log)
              <tr>
                <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                <td>
                  <div class="fw-bold">{{ $log->sender_name ?? 'Siswa' }}</div>
                  <small class="text-muted">{{ $log->sender_number }}</small>
                </td>
                <td><code>{{ $log->nisn_queried }}</code></td>
                <td>
                  @if ($log->student)
                    <div class="text-primary fw-bold">{{ $log->student->name }}</div>
                  @else
                    <span class="text-muted">Tidak Ditemukan</span>
                  @endif
                </td>
                <td>
                  <span class="badge {{ $log->status === 'replied' ? 'bg-label-success' : 'bg-label-danger' }}">
                    {{ strtoupper($log->status) }}
                  </span>
                </td>
                <td class="text-wrap" style="max-width: 300px;">
                  <small>{{ \Illuminate\Support\Str::limit($log->response_message, 100) }}</small>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-5">
                  <i class="ri ri-chat-delete-line fs-1 d-block mb-2"></i>
                  Belum ada log auto-respond tercatat.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        {{ $logs->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('toggle-auto-respond');

  toggleBtn.addEventListener('change', async function() {
    const isEnabled = this.checked;
    
    try {
      const response = await fetch(@json(route('admin.whatsapp.auto-respond.toggle')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ enabled: isEnabled })
      });

      const data = await response.json();
      if (data.success) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: data.message,
          showConfirmButton: false,
          timer: 3000
        });
      } else {
        Swal.fire('Gagal!', data.message, 'error');
        this.checked = !isEnabled; // Revert
      }
    } catch (err) {
      Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
      this.checked = !isEnabled; // Revert
    }
  });
});
</script>
@endpush
