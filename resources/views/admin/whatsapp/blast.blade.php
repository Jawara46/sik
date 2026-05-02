@extends('layouts.app')

@section('title', 'Blast Notifikasi - SIK-T')

@section('content')
@include('admin.whatsapp._nav')
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
          <h5 class="card-title mb-1">Blast Notifikasi WhatsApp</h5>
          <p class="text-muted mb-0">Kirim pesan massal ke siswa yang memiliki nomor WhatsApp aktif.</p>
        </div>
        <div class="d-flex flex-wrap gap-3">
          <div class="border rounded p-3 min-w-140">
            <small class="text-muted d-block">Total Log</small>
            <strong>{{ $stats['total_logs'] }}</strong>
          </div>
          <div class="border rounded p-3 min-w-140">
            <small class="text-muted d-block">Terkirim</small>
            <strong class="text-success">{{ $stats['sent'] }}</strong>
          </div>
          <div class="border rounded p-3 min-w-140">
            <small class="text-muted d-block">Gagal</small>
            <strong class="text-danger">{{ $stats['failed'] }}</strong>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('admin.whatsapp.blast.index') }}" class="row g-4 mb-5">
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
            <label class="form-label">Status Kelulusan</label>
            <select name="status" class="form-select">
              <option value="">Semua Status</option>
              @foreach (['Lulus', 'Tidak Lulus', 'Pending'] as $status)
                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Akses Unduh</label>
            <select name="access" class="form-select">
              <option value="">Semua</option>
              <option value="open" @selected($filters['access'] === 'open')>Terbuka</option>
              <option value="locked" @selected($filters['access'] === 'locked')>Terkunci</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Cari</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nama, NISN, WA">
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary">Terapkan Filter</button>
          </div>
        </form>

        <div class="alert alert-info" role="alert">
          <strong>{{ $recipients->count() }}</strong> penerima siap dikirimi berdasarkan filter saat ini.
        </div>

        <form id="blast-form" method="POST" action="{{ route('admin.whatsapp.blast.send') }}" class="row g-4">
          @csrf
          <input type="hidden" name="major_id" value="{{ $filters['major_id'] }}">
          <input type="hidden" name="status" value="{{ $filters['status'] }}">
          <input type="hidden" name="access" value="{{ $filters['access'] }}">
          <input type="hidden" name="q" value="{{ $filters['q'] }}">

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Isi Pesan</label>
              <div class="d-flex gap-2">
                <select id="template-selector" class="form-select form-select-sm" style="width: auto; min-width: 200px;">
                  <option value="">-- Pilih Template Tersimpan --</option>
                  @foreach ($templates as $template)
                    <option value="{{ $template->id }}" data-content="{{ $template->content }}">{{ $template->name }}</option>
                  @endforeach
                </select>
                <button type="button" id="btn-save-template" class="btn btn-sm btn-outline-primary">
                  <i class="ri ri-save-line me-1"></i> Simpan
                </button>
                <button type="button" id="btn-delete-template" class="btn btn-sm btn-outline-danger d-none">
                  <i class="ri ri-delete-bin-line"></i>
                </button>
              </div>
            </div>
            <textarea name="message" id="blast-message" rows="7" class="form-control">{{ old('message', $defaultMessage) }}</textarea>
            <small class="text-muted d-block mt-2">
              Token tersedia: <code>{nama_siswa}</code>, <code>{nisn}</code>, <code>{jurusan}</code>, <code>{kode_jurusan}</code>, <code>{nama_sekolah}</code>, <code>{tanggal_rilis}</code>, <code>{status_kelulusan}</code>
            </small>
          </div>
          <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">Pesan diproses di latar belakang (Asinkron). Hasil akhir dapat dilihat di tab Riwayat.</div>
            <button type="submit" class="btn btn-success" @disabled($recipients->isEmpty())>Kirim Blast Sekarang</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h6 class="card-title mb-1">Preview Penerima</h6>
        <p class="text-muted mb-0">Daftar 10 penerima pertama dari filter saat ini.</p>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Siswa</th>
                <th>NISN</th>
                <th>Jurusan</th>
                <th>Status</th>
                <th>WA</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recipients->take(10) as $student)
                <tr>
                  <td>{{ $student->name }}</td>
                  <td>{{ $student->nisn }}</td>
                  <td>{{ $student->major?->name ?? '-' }}</td>
                  <td><span class="badge bg-label-secondary">{{ $student->status ?? 'Pending' }}</span></td>
                  <td>{{ $student->nomor_wa }}</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-icon btn-label-success btn-send-individual" 
                      data-id="{{ $student->id }}" data-name="{{ $student->name }}" title="Kirim ke Siswa Ini">
                      <i class="ri ri-whatsapp-line"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">Belum ada penerima yang cocok dengan filter.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
  </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="blastProgressModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-5">
        <div class="spinner-border text-success mb-4" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="mb-2">Memproses Blast WhatsApp</h4>
        <p class="text-muted mb-4">Jangan tutup halaman ini sampai proses selesai.</p>
        
        <div class="progress mb-3" style="height: 20px;">
          <div id="blast-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%">0%</div>
        </div>
        
        <div class="d-flex justify-content-between text-muted small">
          <span id="blast-processed-count">0/0 terkirim</span>
          <span id="blast-failed-count" class="text-danger">0 gagal</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const templateSelector = document.getElementById('template-selector');
  const messageTextarea = document.getElementById('blast-message');
  const btnSaveTemplate = document.getElementById('btn-save-template');
  const btnDeleteTemplate = document.getElementById('btn-delete-template');

  // Load template content to textarea
  templateSelector.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
      messageTextarea.value = selectedOption.getAttribute('data-content');
      btnDeleteTemplate.classList.remove('d-none');
    } else {
      btnDeleteTemplate.classList.add('d-none');
    }
  });

  // Save new template
  btnSaveTemplate.addEventListener('click', async function() {
    const content = messageTextarea.value;
    if (!content.trim()) {
      return Swal.fire('Error', 'Isi pesan tidak boleh kosong.', 'error');
    }

    const { value: templateName } = await Swal.fire({
      title: 'Simpan Template Baru',
      input: 'text',
      inputLabel: 'Nama Template',
      inputPlaceholder: 'Contoh: Pengumuman Lulus',
      showCancelButton: true,
      inputValidator: (value) => {
        if (!value) return 'Nama template wajib diisi!';
      }
    });

    if (!templateName) return;

    btnSaveTemplate.disabled = true;
    try {
      const response = await fetch(@json(route('admin.whatsapp.templates.store')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ name: templateName, content: content })
      });

      const data = await response.json();
      if (data.success) {
        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
      } else {
        Swal.fire('Gagal!', data.message, 'error');
      }
    } catch (err) {
      Swal.fire('Error!', 'Terjadi kesalahan server.', 'error');
    } finally {
      btnSaveTemplate.disabled = false;
    }
  });

  // Delete template
  btnDeleteTemplate.addEventListener('click', async function() {
    const templateId = templateSelector.value;
    if (!templateId) return;

    const { isConfirmed } = await Swal.fire({
      title: 'Hapus Template?',
      text: 'Template ini tidak dapat dipulihkan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ea5455'
    });

    if (!isConfirmed) return;

    btnDeleteTemplate.disabled = true;
    try {
      const response = await fetch(@json(route('admin.whatsapp.templates.delete', ':id')).replace(':id', templateId), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json();
      if (data.success) {
        Swal.fire('Terhapus!', data.message, 'success').then(() => location.reload());
      } else {
        Swal.fire('Gagal!', data.message, 'error');
      }
    } catch (err) {
      Swal.fire('Error!', 'Terjadi kesalahan server.', 'error');
    } finally {
      btnDeleteTemplate.disabled = false;
    }
  });

  // Individual message sending
  const individualBtns = document.querySelectorAll('.btn-send-individual');
  individualBtns.forEach(btn => {
    btn.addEventListener('click', async function() {
      const studentId = this.getAttribute('data-id');
      const studentName = this.getAttribute('data-name');
      const message = messageTextarea.value;

      if (!message.trim()) {
        return Swal.fire('Error', 'Isi pesan tidak boleh kosong.', 'error');
      }

      const { isConfirmed } = await Swal.fire({
        title: 'Kirim Pesan Individual?',
        text: `Kirim pesan ini ke ${studentName}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#28c76f'
      });

      if (!isConfirmed) return;

      this.disabled = true;
      try {
        const response = await fetch(@json(route('admin.whatsapp.send-individual')), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ student_id: studentId, message: message })
        });

        const data = await response.json();
        if (data.success) {
          Swal.fire('Terkirim!', data.message, 'success');
        } else {
          Swal.fire('Gagal!', data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error!', 'Terjadi kesalahan server.', 'error');
      } finally {
        this.disabled = false;
      }
    });
  });

  // Async Blast Form Handling
  const blastForm = document.getElementById('blast-form');
  const blastProgressModal = new bootstrap.Modal(document.getElementById('blastProgressModal'));
  const progressBar = document.getElementById('blast-progress-bar');
  const processedCountText = document.getElementById('blast-processed-count');
  const failedCountText = document.getElementById('blast-failed-count');

  blastForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (individualBtns.length === 0) {
      return Swal.fire('Error', 'Tidak ada penerima untuk filter saat ini.', 'error');
    }

    const { isConfirmed } = await Swal.fire({
      title: 'Mulai Blast WhatsApp?',
      text: `Pesan akan dikirim ke ${individualBtns.length} penerima.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Mulai Blast',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#28c76f'
    });

    if (!isConfirmed) return;

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
      const response = await fetch(this.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        blastProgressModal.show();
        pollBlastStatus(data.batch_id);
      } else {
        Swal.fire('Gagal!', data.message, 'error');
        submitBtn.disabled = false;
      }
    } catch (err) {
      Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
      submitBtn.disabled = false;
    }
  });

  function pollBlastStatus(batchId) {
    const pollInterval = setInterval(async () => {
      try {
        const response = await fetch(@json(route('admin.whatsapp.blast.status', ':id')).replace(':id', batchId), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        progressBar.style.width = data.percent + '%';
        progressBar.textContent = data.percent + '%';
        processedCountText.textContent = `${data.processed}/${data.total} terkirim`;
        failedCountText.textContent = `${data.failed} gagal`;

        if (data.status === 'completed' || data.status === 'failed') {
          clearInterval(pollInterval);
          setTimeout(() => {
            blastProgressModal.hide();
            Swal.fire({
              title: data.status === 'completed' ? 'Selesai!' : 'Gagal!',
              text: `Blast selesai dengan ${data.sent} terkirim dan ${data.failed} gagal.`,
              icon: data.status === 'completed' ? 'success' : 'error',
              confirmButtonText: 'Lihat Riwayat'
            }).then(() => {
              window.location.href = @json(route('admin.whatsapp.history.index'));
            });
          }, 1000);
        }
      } catch (err) {
        clearInterval(pollInterval);
        blastProgressModal.hide();
        console.error('Polling error:', err);
      }
    }, 2000);
  }
});
</script>
@endpush
