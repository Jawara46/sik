@extends('layouts.app')

@section('title', 'Koneksi WhatsApp Gateway - SIK-T')

@section('content')
  @include('admin.whatsapp._nav')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h5 class="card-title mb-1">Koneksi Gateway (All-in-One QR Scan)</h5>
            <p class="mb-0 text-muted">Endpoint gateway: {{ config('services.wapi.url') }}</p>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span id="wa-status-badge"
              class="badge {{ ($waStatus['status'] ?? '') === 'CONNECTED' ? 'bg-success' : 'bg-danger' }}">
              {{ ($waStatus['status'] ?? 'DISCONNECTED') === 'CONNECTED' ? 'Connected' : 'Disconnected' }}
            </span>
            <button type="button" id="wa-ping-btn" class="btn btn-outline-info"><i class="ri ri-pulse-line me-1"></i> Ping
              API</button>
            <button type="button" id="wa-test-btn" class="btn btn-outline-primary d-none"><i
                class="ri ri-send-plane-fill me-1"></i> Tes Kirim Pesan</button>
            <button type="button" id="wa-disconnect-btn" class="btn btn-outline-danger d-none"><i
                class="ri ri-logout-box-r-line me-1"></i> Disconnect</button>
            <button type="button" id="wa-connect-btn" class="btn btn-success">Ambil QR Code</button>
          </div>
        </div>
        <div class="card-body">
          <div class="border border-success rounded-3 p-5 text-center" id="qr-container"
            style="background: linear-gradient(145deg, #ffffff, #f0fdf4); box-shadow: 0 8px 30px rgba(74, 222, 128, 0.1);">
            <!-- State: QR -->
            <img id="wa-qr-image" src="{{ $waStatus['qr_code'] ?? '' }}" alt="QR WhatsApp" class="shadow-sm rounded-3"
              style="max-width: 250px; width: 100%; display:none; border: 4px solid #fff;">

            <!-- State: Connected Icon -->
            <div id="wa-connected-icon" class="d-none py-4">
              <div class="mb-3">
                <div
                  class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow"
                  style="width: 120px; height: 120px; animation: pulse 2s infinite;">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="60" height="60"
                    fill="currentColor">
                    <path
                      d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                  </svg>
                </div>
              </div>
              <h4 class="mt-4 text-success fw-bold">WhatsApp Gateway Terhubung!</h4>
              <p class="mb-0 text-muted fs-6"> terintegrasi dan siap memproses <strong class="text-dark">WA Blast</strong>
                maupun pengiriman otomatis.</p>
            </div>

            <!-- State: Placeholder -->
            <p id="wa-qr-placeholder" class="text-muted mb-0">
              <i class="ri ri-wifi-off-line d-block mb-3 fs-1 text-secondary"></i>
              Koneksi terputus atau QR belum tersedia. Klik <strong class="text-dark">Ambil QR Code</strong> untuk
              menyegarkan.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    @keyframes pulse {
      0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
      }

      70% {
        transform: scale(1);
        box-shadow: 0 0 0 20px rgba(25, 135, 84, 0);
      }

      100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
      }
    }
  </style>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const statusBadge = document.getElementById('wa-status-badge');
      const connectBtn = document.getElementById('wa-connect-btn');
      const qrImage = document.getElementById('wa-qr-image');
      const qrPlaceholder = document.getElementById('wa-qr-placeholder');

      const statusUrl = @json(route('admin.whatsapp.connection.status'));
      const qrUrl = @json(route('admin.whatsapp.connection.qr'));

      function renderStatus(data) {
        const status = (data.status || 'DISCONNECTED').toUpperCase();
        const isConnected = status === 'CONNECTED';

        statusBadge.textContent = isConnected ? 'Connected' : 'Disconnected';
        statusBadge.classList.toggle('bg-success', isConnected);
        statusBadge.classList.toggle('bg-danger', !isConnected);

        const connectedIcon = document.getElementById('wa-connected-icon');

        if (isConnected) {
          qrImage.style.display = 'none';
          qrPlaceholder.classList.add('d-none');
          connectedIcon.classList.remove('d-none');
        } else if (data.qr_code && data.qr_code.includes('data:image')) {
          qrImage.src = data.qr_code;
          qrImage.style.display = 'inline-block';
          qrPlaceholder.classList.add('d-none');
          connectedIcon.classList.add('d-none');
        } else {
          qrImage.style.display = 'none';
          qrPlaceholder.classList.remove('d-none');
          connectedIcon.classList.add('d-none');
        }
        if (isConnected) {
          document.getElementById('wa-test-btn').classList.remove('d-none');
          document.getElementById('wa-disconnect-btn').classList.remove('d-none');
        } else {
          document.getElementById('wa-test-btn').classList.add('d-none');
          document.getElementById('wa-disconnect-btn').classList.add('d-none');
        }
      }

      const disconnectBtn = document.getElementById('wa-disconnect-btn');
      disconnectBtn.addEventListener('click', async function () {
        const { isConfirmed } = await Swal.fire({
          title: 'Putuskan Koneksi WhatsApp?',
          text: 'Sesi WhatsApp Anda akan dihentikan dan folder autentikasi akan dibersihkan.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Putuskan',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#ea5455',
        });

        if (!isConfirmed) return;

        disconnectBtn.disabled = true;
        disconnectBtn.innerHTML = 'Memutuskan...';

        try {
          const response = await fetch(@json(route('admin.whatsapp.connection.disconnect')), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          const data = await response.json();
          if (data.success) {
            Swal.fire('Berhasil!', data.message, 'success');
            loadStatus();
          } else {
            Swal.fire('Gagal!', data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
        } finally {
          disconnectBtn.disabled = false;
          disconnectBtn.innerHTML = '<i class="ri ri-logout-box-r-line me-1"></i> Disconnect';
        }
      });

      const testBtn = document.getElementById('wa-test-btn');
      testBtn.addEventListener('click', async function () {
        const { value: number } = await Swal.fire({
          title: 'Tes Kirim Pesan',
          icon: 'question',
          input: 'text',
          inputLabel: 'Masukkan Nomor WhatsApp Target',
          inputPlaceholder: 'Contoh: 081234567890',
          showCancelButton: true,
          confirmButtonText: 'Kirim Pesan',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#198754',
          inputValidator: (value) => {
            if (!value) {
              return 'Nomor WhatsApp wajib diisi!';
            }
          }
        });

        if (!number) return;

        connectBtn.disabled = true;
        testBtn.textContent = 'Mengirim...';
        testBtn.disabled = true;

        Swal.fire({
          title: 'Mengirim Pesan...',
          text: 'Harap tunggu, sedang menembak WAPI.',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading() }
        });

        try {
          const response = await fetch(@json(route('admin.whatsapp.connection.test')), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              number: number,
              message: 'Hello! Ini adalah pesan pengujian otomatis dari *Sistem Informasi Kelulusan Yazid Digital*. Jika pesan ini diterima, maka *WhatsApp Gateway* telah berhasil terhubung dan siap melakukan Broadcast.'
            })
          });

          const data = await response.json();
          if (data.success) {
            Swal.fire('Berhasil Terkirim!', data.message, 'success');
          } else {
            Swal.fire('Gagal!', data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Jaringan Putus!', 'Terjadi kesalahan saat menghungi server WAPI lokal.', 'error');
        } finally {
          connectBtn.disabled = false;
          testBtn.innerHTML = '<i class="ri ri-send-plane-fill me-1"></i> Tes Kirim Pesan';
          testBtn.disabled = false;
        }
      });

      const pingBtn = document.getElementById('wa-ping-btn');
      pingBtn.addEventListener('click', async function () {
        pingBtn.disabled = true;
        pingBtn.innerHTML = 'Pinging...';
        try {
          const ts = new Date().getTime();
          const response = await fetch(`${statusUrl}?t=${ts}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          if (response.ok) {
            const data = await response.json();
            renderStatus(data);
            Swal.fire({
              toast: true,
              position: 'bottom-end',
              icon: 'success',
              title: 'WAPI Online: Status ' + data.status,
              showConfirmButton: false,
              timer: 3000
            });
          } else {
            Swal.fire('Ping Gagal', 'API Gateway mati atau tidak dapat diakses.', 'error');
          }
        } catch (e) {
          Swal.fire('Ping Gagal', 'Koneksi ke backend menolak.', 'error');
        } finally {
          pingBtn.disabled = false;
          pingBtn.innerHTML = '<i class="ri ri-pulse-line me-1"></i> Ping API';
        }
      });

      async function loadStatus() {
        const ts = new Date().getTime();
        const response = await fetch(`${statusUrl}?t=${ts}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) return;
        renderStatus(await response.json());
      }

      async function loadQr() {
        connectBtn.disabled = true;
        connectBtn.textContent = 'Memproses...';

        try {
          const ts = new Date().getTime();
          const response = await fetch(`${qrUrl}?t=${ts}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          if (!response.ok) return;
          renderStatus(await response.json());
        } finally {
          connectBtn.disabled = false;
          connectBtn.textContent = 'Ambil QR Code';
        }
      }

      connectBtn.addEventListener('click', loadQr);
      loadStatus();
      setInterval(loadStatus, 10000);
    });
  </script>
@endpush