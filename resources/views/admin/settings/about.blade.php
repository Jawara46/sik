@extends('layouts.app')

@section('title', 'Tentang Aplikasi — SIK-T')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="mb-1 fw-bold">Tentang Aplikasi</h4>
        <p class="text-muted mb-0">Informasi sistem, versi, dan pembaruan aplikasi.</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- Card Informasi Aplikasi --}}
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2 pb-2">
        <div class="avatar avatar-sm flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="ri ri-information-line"></i>
          </span>
        </div>
        <h5 class="card-title mb-0">Informasi Aplikasi</h5>
      </div>
      <div class="card-body pt-0">
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" height="48" class="me-3"
               onerror="this.src='{{ asset('assets/img/favicon/favicon.ico') }}'">
          <div>
            <h5 class="mb-0 fw-bold">{{ config('sik.app_name', 'SIK-T') }}</h5>
            <span class="text-muted">{{ config('sik.app_fullname', 'Sistem Informasi Kelulusan Terpadu') }}</span>
          </div>
        </div>

        <table class="table table-borderless table-sm mb-0">
          <tbody>
            <tr>
              <td class="text-muted pe-3" style="width: 160px;">Versi Aplikasi</td>
              <td>
                <span class="badge bg-label-primary rounded-pill">v{{ $version }}</span>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Pengembang</td>
              <td>
                <a href="{{ config('sik.developer_url', '#') }}" target="_blank" class="text-body text-decoration-none fw-semibold">
                  {{ config('sik.developer', 'Yazid Digital') }}
                </a>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Metode Instalasi</td>
              <td>
                @if($env['install_method'] === 'git')
                  <span class="badge bg-label-info rounded-pill"><i class="ri ri-git-branch-line me-1"></i> Git Clone</span>
                @else
                  <span class="badge bg-label-warning rounded-pill"><i class="ri ri-file-zip-line me-1"></i> ZIP / Manual</span>
                @endif
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Card Environment --}}
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2 pb-2">
        <div class="avatar avatar-sm flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-secondary">
            <i class="ri ri-server-line"></i>
          </span>
        </div>
        <h5 class="card-title mb-0">Lingkungan Server</h5>
      </div>
      <div class="card-body pt-0">
        <table class="table table-borderless table-sm mb-0">
          <tbody>
            <tr>
              <td class="text-muted pe-3" style="width: 160px;">PHP</td>
              <td><code>{{ $env['php_version'] }}</code></td>
            </tr>
            <tr>
              <td class="text-muted">Laravel</td>
              <td><code>{{ $env['laravel_version'] }}</code></td>
            </tr>
            <tr>
              <td class="text-muted">Node.js</td>
              <td><code>{{ $env['node_version'] }}</code></td>
            </tr>
            <tr>
              <td class="text-muted">Web Server</td>
              <td><code>{{ $env['server_software'] }}</code></td>
            </tr>
            <tr>
              <td class="text-muted">Timezone</td>
              <td><code>{{ $env['timezone'] }}</code></td>
            </tr>
            <tr>
              <td class="text-muted">Bahasa</td>
              <td><code>{{ strtoupper($env['locale']) }}</code></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Card Pembaruan Aplikasi --}}
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between pb-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-sm flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-success">
              <i class="ri ri-refresh-line"></i>
            </span>
          </div>
          <h5 class="card-title mb-0">Pembaruan Aplikasi</h5>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFixStorage" onclick="fixStorage()">
            <i class="ri ri-link-m me-1"></i> Perbaiki Tautan Gambar
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnCheckUpdate" onclick="checkForUpdate()">
            <i class="ri ri-search-eye-line me-1"></i> Periksa Update
          </button>
        </div>
      </div>
      <div class="card-body pt-0">
        <div id="updateStatus" class="d-none">
          {{-- Dynamic content via JS --}}
        </div>

        <div id="updateDefault" class="text-center py-4">
          <i class="ri ri-shield-check-line text-success" style="font-size: 3rem;"></i>
          <p class="text-muted mt-2 mb-0">Klik <strong>"Periksa Update"</strong> untuk memeriksa versi terbaru dari server.</p>
          <small class="text-muted">Versi terpasang: <strong>v{{ $version }}</strong></small>
        </div>

        {{-- Update Log --}}
        <div id="updateLogWrapper" class="d-none mt-3">
          <h6 class="fw-semibold mb-2">Log Proses:</h6>
          <div id="updateLog" class="bg-dark text-light p-3 rounded-3" style="max-height: 300px; overflow-y: auto; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82rem; line-height: 1.6;">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Card Deskripsi Fitur --}}
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2 pb-2">
        <div class="avatar avatar-sm flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-info">
            <i class="ri ri-sparkling-line"></i>
          </span>
        </div>
        <h5 class="card-title mb-0">Fitur Unggulan</h5>
      </div>
      <div class="card-body pt-0">
        <div class="row g-3">
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-graduation-cap-line text-primary mt-1"></i>
              <div>
                <strong>Manajemen Kelulusan</strong>
                <p class="text-muted mb-0 small">SKL, Transkrip Nilai, dan Sertifikat UKK otomatis dengan penomoran dinamis.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-whatsapp-line text-success mt-1"></i>
              <div>
                <strong>WhatsApp Gateway</strong>
                <p class="text-muted mb-0 small">Blast notifikasi, auto-respond cek kelulusan, dan log pesan real-time.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-file-excel-2-line text-warning mt-1"></i>
              <div>
                <strong>Import/Export Excel</strong>
                <p class="text-muted mb-0 small">Template nilai leger semester, import siswa, dan mapping mapel per jurusan.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-qr-code-line text-info mt-1"></i>
              <div>
                <strong>QR Verifikasi Dokumen</strong>
                <p class="text-muted mb-0 small">Setiap dokumen memiliki QR Code untuk validasi keaslian secara publik.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-mail-open-line text-danger mt-1"></i>
              <div>
                <strong>Portal Siswa Interaktif</strong>
                <p class="text-muted mb-0 small">Animasi amplop & confetti, unduh dokumen, dan info kelulusan personal.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="d-flex gap-2">
              <i class="ri ri-database-2-line text-secondary mt-1"></i>
              <div>
                <strong>Backup & Restore</strong>
                <p class="text-muted mb-0 small">Backup database otomatis dan self-update tanpa akses terminal.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function checkForUpdate() {
  const btnCheck = document.getElementById('btnCheckUpdate');
  const statusDiv = document.getElementById('updateStatus');
  const defaultDiv = document.getElementById('updateDefault');

  btnCheck.disabled = true;
  btnCheck.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memeriksa...';

  fetch("{{ route('admin.settings.update.check') }}", {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    defaultDiv.classList.add('d-none');
    statusDiv.classList.remove('d-none');

    if (data.error) {
      statusDiv.innerHTML = `
        <div class="alert alert-warning mb-0">
          <i class="ri ri-error-warning-line me-1"></i>
          Tidak dapat terhubung ke server update. Periksa koneksi internet.
        </div>`;
      return;
    }

    if (data.has_update) {
      statusDiv.innerHTML = `
        <div class="alert alert-info border-0 mb-3">
          <div class="d-flex align-items-center">
            <i class="ri ri-arrow-up-circle-line me-2" style="font-size: 1.5rem;"></i>
            <div>
              <strong>Update Tersedia!</strong><br>
              <span class="text-muted">Versi terbaru: <strong>v${data.tag}</strong> — Terpasang: <strong>v${data.current}</strong></span>
            </div>
          </div>
        </div>
        <div class="mb-2"><strong>Catatan Rilis:</strong></div>
        <div class="bg-light p-3 rounded-3 mb-3" style="font-size: 0.88rem;">${data.notes || 'Tidak ada catatan.'}</div>
        <button type="button" class="btn btn-success" onclick="performUpdate()">
          <i class="ri ri-download-cloud-2-line me-1"></i> Jalankan Update ke v${data.tag}
        </button>`;
    } else {
      statusDiv.innerHTML = `
        <div class="alert alert-success border-0 mb-0">
          <i class="ri ri-checkbox-circle-line me-1"></i>
          Aplikasi sudah menggunakan versi terbaru <strong>(v${data.current})</strong>.
        </div>`;
    }
  })
  .catch(() => {
    defaultDiv.classList.add('d-none');
    statusDiv.classList.remove('d-none');
    statusDiv.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i class="ri ri-error-warning-line me-1"></i>
        Terjadi kesalahan saat memeriksa update.
      </div>`;
  })
  .finally(() => {
    btnCheck.disabled = false;
    btnCheck.innerHTML = '<i class="ri ri-search-eye-line me-1"></i> Periksa Update';
  });
}

function performUpdate() {
  const statusDiv = document.getElementById('updateStatus');
  const logWrapper = document.getElementById('updateLogWrapper');
  const logDiv = document.getElementById('updateLog');

  statusDiv.innerHTML = `
    <div class="alert alert-warning border-0 mb-0">
      <span class="spinner-border spinner-border-sm me-2"></span>
      <strong>Proses update sedang berjalan...</strong> Jangan tutup halaman ini.
    </div>`;

  logWrapper.classList.remove('d-none');
  logDiv.innerHTML = '⏳ Memulai proses update...\n';

  fetch("{{ route('admin.settings.update.perform') }}", {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    }
  })
  .then(r => r.json())
  .then(data => {
    if (data.log && data.log.length) {
      logDiv.innerHTML = data.log.join('\n');
    }

    if (data.success) {
      statusDiv.innerHTML = `
        <div class="alert alert-success border-0 mb-0">
          <i class="ri ri-checkbox-circle-line me-1"></i>
          <strong>${data.message}</strong> Metode: ${data.method.toUpperCase()}. Halaman akan dimuat ulang...
        </div>`;
      setTimeout(() => location.reload(), 3000);
    } else {
      statusDiv.innerHTML = `
        <div class="alert alert-danger border-0 mb-0">
          <i class="ri ri-error-warning-line me-1"></i>
          <strong>${data.message}</strong>
        </div>`;
    }
  })
  .catch(() => {
    statusDiv.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i class="ri ri-error-warning-line me-1"></i>
        Terjadi kesalahan saat menjalankan update.
      </div>`;
  });
}
function fixStorage() {
  const btn = document.getElementById('btnFixStorage');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memperbaiki...';

  fetch("{{ route('admin.settings.update.fix-storage') }}", {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    }
  })
  .then(r => r.json())
  .then(data => {
    alert(data.message);
    if (data.success) location.reload();
  })
  .catch(() => alert('Terjadi kesalahan.'))
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="ri ri-link-m me-1"></i> Perbaiki Tautan Gambar';
  });
}
</script>
@endpush
