@extends('layouts.student')

@section('title', 'Dashboard Siswa - SIK-T')

@section('content')

@if($showEnvelope)
<style>
  @keyframes float {
    0%,100% { transform: translateY(0px); }
    50%      { transform: translateY(-18px); }
  }
  @keyframes glow-pulse {
    0%,100% { box-shadow: 0 0 30px 10px rgba(102,126,234,0.25); }
    50%      { box-shadow: 0 0 60px 20px rgba(102,126,234,0.5); }
  }
  @keyframes shake {
    0%,100% { transform: rotate(0deg) scale(1.05); }
    25%      { transform: rotate(-3deg) scale(1.08); }
    75%      { transform: rotate(3deg) scale(1.08); }
  }
  @keyframes fadeOut {
    to { opacity:0; visibility:hidden; }
  }
  @keyframes fadeIn {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
  }

  #envelope-screen {
    background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 50%, #fce7f3 100%);
  }
  #envelope-wrap {
    animation: float 3s ease-in-out infinite;
    cursor: pointer;
    position: relative;
  }
  #envelope-wrap:hover { animation: shake 0.5s ease-in-out infinite; }

  /* ── SVG Envelope ── */
  .env-body    { fill: #667eea; }
  .env-flap    { fill: #4f5bc7; transform-origin: top center; transition: transform 0.5s ease; }
  .env-letter  { fill: #fff; opacity: 0; transition: opacity 0.5s ease, transform 0.5s ease; transform: translateY(15px); }
  .env-shine   { fill: rgba(255,255,255,0.15); }

  .envelope-opened .env-flap  { transform: rotateX(175deg); }
  .envelope-opened .env-letter { opacity: 1; transform: translateY(-25px); }

  #glow-ring {
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(102,126,234,0.2) 0%, transparent 70%);
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    animation: glow-pulse 2.5s ease-in-out infinite;
    pointer-events: none;
  }
</style>

<div id="envelope-screen" class="vh-100 vw-100 position-fixed top-0 start-0 d-flex flex-column justify-content-center align-items-center" style="z-index: 9999;">

  <div id="envelope-wrap" style="position:relative;">
    <div id="glow-ring"></div>

    {{-- SVG Envelope --}}
    <svg id="envelope-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 160" width="220" height="160" style="overflow:visible; filter: drop-shadow(0 20px 40px rgba(102,126,234,0.4));">
      <!-- Body -->
      <rect class="env-body" x="5" y="40" width="210" height="115" rx="12"/>
      <!-- Shine -->
      <rect class="env-shine" x="5" y="40" width="210" height="38" rx="12"/>
      <!-- Flap (top triangle) -->
      <polygon class="env-flap" points="5,40 110,105 215,40"/>
      <!-- Left fold -->
      <polygon style="fill:rgba(0,0,0,0.08);" points="5,40 5,155 75,98"/>
      <!-- Right fold -->
      <polygon style="fill:rgba(0,0,0,0.08);" points="215,40 215,155 145,98"/>
      <!-- Letter peeking out -->
      <rect class="env-letter" x="55" y="10" width="110" height="75" rx="6"/>
      <line class="env-letter" x1="70" y1="30" x2="150" y2="30" stroke="#667eea" stroke-width="4" stroke-linecap="round"/>
      <line class="env-letter" x1="70" y1="44" x2="150" y2="44" stroke="#b0b8f0" stroke-width="3" stroke-linecap="round"/>
      <line class="env-letter" x1="70" y1="56" x2="120" y2="56" stroke="#b0b8f0" stroke-width="3" stroke-linecap="round"/>
    </svg>
  </div>

  <h3 class="mt-5 fw-bold" style="color:#1e293b; font-size:1.7rem;">Pengumuman Kelulusan 🎓</h3>
  <p class="text-muted text-center px-4 mt-2" style="max-width: 380px; font-size:1rem;">
    Hai <strong>{{ $student->name }}</strong>, hasil evaluasi akhirmu sudah ditetapkan.<br>
    Klik amplop di bawah untuk membukanya!
  </p>

  <button id="btn-open-envelope" class="btn btn-primary btn-lg mt-4 px-5 rounded-pill shadow"
    style="background: linear-gradient(135deg,#667eea,#764ba2); border:none; font-size:1rem; transition: transform .2s;">
    <i class="ri ri-mail-open-line me-2"></i> Buka Hasil
  </button>

  <p class="text-muted small mt-3" style="animation: float 2s ease-in-out infinite; opacity:.6;">
    ↑ Klik amplop atau tombol di atas
  </p>
</div>

@endif


<div id="dashboard-content" class="row justify-content-center {{ $showEnvelope ? 'd-none' : '' }}" style="transition: opacity 1s ease; {{ $showEnvelope ? 'opacity: 0;' : '' }}">
  <div class="col-12 col-xl-10">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4 p-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
          <div>
            <span class="badge bg-label-primary mb-2">Portal Siswa</span>
            <h3 class="mb-1">{{ $student->name }}</h3>
            <p class="text-muted mb-0">
              NISN {{ $student->nisn }} • {{ $student->school?->nama_sekolah ?? $student->school?->name ?? 'Sekolah' }}
            </p>
          </div>
          <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Logout</button>
          </form>
        </div>

        @if (session('error'))
          <div class="alert alert-danger mb-4">{{ session('error') }}</div>
        @endif
        @if (session('status'))
          <div class="alert alert-success mb-4">{{ session('status') }}</div>
        @endif

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Status Kelulusan</small>
              <h6 class="mb-0 {{ $student->status === 'Lulus' ? 'text-success' : ($student->status === 'Tidak Lulus' ? 'text-danger' : 'text-warning') }}">
                {{ $student->status ?? 'Pending' }}
              </h6>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Status Administrasi</small>
              <h6 class="mb-0 {{ $canDownload ? 'text-success' : 'text-warning' }}">
                {{ $canDownload ? 'Akses Unduh Aktif' : 'Akses Unduh Dikunci' }}
              </h6>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <small class="text-muted d-block">Jurusan</small>
              <h6 class="mb-0">{{ $student->major?->name ?? '-' }}</h6>
            </div>
          </div>
        </div>

        {{-- ── Document Cards ── --}}
        <div class="row g-4">

          {{-- Helper: loop SKL + Transcript --}}
          @foreach ([
            ['key' => 'skl',        'label' => 'Surat Keterangan Lulus (SKL)',  'route_download' => 'student.documents.skl.download',        'route_preview' => 'student.documents.skl.preview',        'btn_color' => 'btn-primary',  'icon' => 'ri-file-text-fill'],
            ['key' => 'transcript', 'label' => 'Transkrip Nilai',               'route_download' => 'student.documents.transcript.download',  'route_preview' => 'student.documents.transcript.preview',  'btn_color' => 'btn-success',  'icon' => 'ri-file-list-3-fill'],
          ] as $doc)
          @php
            $document   = data_get($documents, $doc['key']);
            $isPublished = $document?->status === 'published';

            // Logika tombol unduh:
            // 1. Dokumen harus ada & berstatus published
            // 2. Siswa tidak terkunci (akses_unduh aktif)
            // 3. Status kelulusan harus final (canDownload)
            $canDownloadDoc = $canDownload && $isPublished;

            // Logika Cek Verifikasi: token harus ada
            $hasToken = !empty($document?->verification_token);
          @endphp

          <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px; overflow:hidden;">
              {{-- Top accent strip --}}
              <div style="height:4px; background:{{ $isPublished ? ($doc['key']==='skl' ? 'linear-gradient(90deg,#667eea,#764ba2)' : 'linear-gradient(90deg,#11998e,#38ef7d)') : '#e2e8f0' }};"></div>

              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;
                      background:{{ $isPublished ? ($doc['key']==='skl' ? 'linear-gradient(135deg,#667eea,#764ba2)' : 'linear-gradient(135deg,#11998e,#38ef7d)') : '#f1f5f9' }};">
                      <i class="ri {{ $doc['icon'] }}" style="color:{{ $isPublished ? '#fff' : '#94a3b8' }};font-size:1.2rem;"></i>
                    </div>
                    <div>
                      <small class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Dokumen</small>
                      <h6 class="mb-0 fw-bold">{{ $doc['label'] }}</h6>
                    </div>
                  </div>
                  @if($document)
                    @php
                      $badgeMap = ['published'=>'bg-label-success','draft'=>'bg-label-warning','revoked'=>'bg-label-danger'];
                      $badgeClass = $badgeMap[$document->status] ?? 'bg-label-secondary';
                      $statusLabel = ['published'=>'Tersedia','draft'=>'Draft','revoked'=>'Dicabut'][$document->status] ?? $document->status;
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                  @else
                    <span class="badge bg-label-secondary">Belum Ada</span>
                  @endif
                </div>

                {{-- Document Info --}}
                <div class="mb-3" style="font-size:.84rem;">
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Nomor Dokumen</span>
                    <strong>{{ $document?->document_number ?: '-' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Tanggal Terbit</span>
                    <strong>{{ $document?->published_at?->locale('id')->translatedFormat('d M Y') ?? '-' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Token Verifikasi</span>
                    <code style="font-size:.75rem;color:#667eea;">{{ $document?->verification_token ? substr($document->verification_token,0,8).'...' : '-' }}</code>
                  </div>
                </div>

                {{-- Buttons --}}
                <div class="d-grid gap-2">

                  {{-- Download Button --}}
                  @if($canDownloadDoc)
                    <div class="d-flex gap-2">
                      {{-- Primary: Download --}}
                      <a href="{{ route($doc['route_download']) }}"
                         class="btn {{ $doc['btn_color'] }} flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                        <i class="ri {{ $doc['icon'] }}"></i>
                        Unduh
                      </a>
                      {{-- Secondary: Preview --}}
                      <a href="{{ route($doc['route_preview']) }}"
                         target="_blank"
                         class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 px-3"
                         title="Preview dokumen di browser">
                        <i class="ri ri-eye-line"></i>
                        Preview
                      </a>
                    </div>
                  @else
                    <button type="button" class="btn {{ $doc['btn_color'] }} disabled d-flex align-items-center justify-content-center gap-2"
                      data-bs-toggle="tooltip"
                      title="{{ !$isPublished ? 'Dokumen belum diterbitkan oleh admin.' : (!$canDownload ? 'Akses unduh dikunci. Hubungi admin sekolah.' : '') }}">
                      <i class="ri ri-lock-line"></i>
                      Unduh {{ $doc['key'] === 'skl' ? 'SKL' : 'Transkrip' }}
                    </button>
                    @if(!$isPublished && $document)
                    <small class="text-muted text-center"><i class="ri ri-information-line me-1"></i>Dokumen masih dalam status <strong>{{ $document->status }}</strong>, menunggu diterbitkan admin.</small>
                    @elseif(!$canDownload)
                    <small class="text-warning text-center"><i class="ri ri-alert-line me-1"></i>Akses unduh dikunci. Pastikan status administrasi sudah aktif.</small>
                    @endif
                  @endif

                  {{-- Verification Button --}}
                  @if($hasToken)
                    <a href="{{ route('verification.document', $document->verification_token) }}"
                       target="_blank"
                       class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">
                      <i class="ri ri-shield-check-line"></i>
                      Cek Verifikasi Dokumen
                    </a>
                  @else
                    <button type="button" class="btn btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-2"
                      title="Token verifikasi belum tersedia.">
                      <i class="ri ri-shield-line"></i>
                      Cek Verifikasi Dokumen
                    </button>
                  @endif

                </div>
              </div>
            </div>
          </div>
          @endforeach

        </div>

        @if (!$canDownload)
          <div class="alert alert-warning mt-4 mb-0 d-flex align-items-center gap-2">
            <i class="ri ri-alert-line fs-5"></i>
            <div>Akses unduh dokumen masih dikunci. Hubungi admin sekolah jika status administrasi atau status kelulusan Anda belum final.</div>
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if($showEnvelope)
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

{{-- ── Result Letter Popup (inserted into DOM) ── --}}
@php $isLulus = strtolower($student->status ?? '') === 'lulus'; @endphp
<div id="result-popup" style="
  display:none; position:fixed; inset:0; z-index:99999;
  background:rgba(0,0,0,0.75); backdrop-filter:blur(8px);
  align-items:flex-start; justify-content:center; overflow-y:auto; padding: 40px 20px;">

  <div id="result-letter" style="
    background:#fff; border-radius:24px; padding:56px 48px; text-align:center;
    max-width:480px; width:90%; box-shadow:0 40px 100px rgba(0,0,0,0.4);
    margin: auto; transform:scale(0.6) translateY(60px); opacity:0;
    transition:transform 0.55s cubic-bezier(.34,1.56,.64,1), opacity 0.45s ease;
    position:relative; overflow:hidden;">

    {{-- Decorative top stripe --}}
    <div style="position:absolute;top:0;left:0;right:0;height:8px;
      background:{{ $isLulus ? 'linear-gradient(90deg,#28c76f,#48da89)' : 'linear-gradient(90deg,#ea5455,#ff6b6b)' }};"></div>

    {{-- Big emoji --}}
    <div id="result-emoji" style="font-size:4.5rem;line-height:1;margin-bottom:16px;">
      {{ $isLulus ? '🎉' : '📋' }}
    </div>

    {{-- School name --}}
    <p style="font-size:.8rem;color:#94a3b8;letter-spacing:1px;text-transform:uppercase;font-weight:600;margin-bottom:6px;">
      {{ $student->school?->nama_sekolah ?? 'Sekolah' }}
    </p>

    {{-- Title --}}
    <h2 style="font-size:1.1rem;color:#475569;font-weight:500;margin-bottom:20px;">
      Pengumuman Hasil Evaluasi Akhir
    </h2>

    {{-- Student name --}}
    <p style="color:#64748b;margin-bottom:4px;font-size:.95rem;">Dengan bangga menyampaikan kepada</p>
    <h3 style="font-size:1.6rem;font-weight:700;color:#1e293b;margin-bottom:24px;">
      {{ $student->name }}
    </h3>

    {{-- The BIG status --}}
    <div style="margin-bottom:24px;">
      <div style="font-size:.85rem;color:#94a3b8;margin-bottom:8px;">dinyatakan</div>
      <div id="result-status-text" style="
        font-size:{{ $isLulus ? '5rem' : '3rem' }};
        font-weight:900;
        line-height:1;
        letter-spacing:{{ $isLulus ? '-2px' : '0' }};
        color:{{ $isLulus ? '#28c76f' : '#ea5455' }};
        text-shadow:{{ $isLulus ? '0 4px 20px rgba(40,199,111,0.35)' : '0 2px 12px rgba(234,84,85,0.3)' }};
        transform:scale(0); opacity:0;
        transition:transform 0.5s cubic-bezier(.34,1.56,.64,1) 0.3s, opacity 0.3s ease 0.3s;
        display:block;">
        {{ $isLulus ? 'LULUS' : strtoupper($student->status ?? 'BELUM FINAL') }}
      </div>
    </div>

    {{-- Subtitle --}}
    @if($isLulus)
    <p style="color:#64748b;font-size:.9rem;line-height:1.6;margin-bottom:0;">
      Selamat! Anda telah berhasil menyelesaikan<br>seluruh evaluasi dengan hasil yang memuaskan. 🎓
    </p>
    @else
    <p style="color:#64748b;font-size:.9rem;line-height:1.6;margin-bottom:0;">
      Tetap semangat. Hubungi pihak sekolah<br>untuk informasi lebih lanjut mengenai hasil Anda.
    </p>
    @endif

    {{-- Progress bar countdown --}}
    <div style="margin-top:28px;height:4px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
      <div id="result-countdown-bar" style="height:100%;width:100%;
        background:{{ $isLulus ? 'linear-gradient(90deg,#28c76f,#48da89)' : 'linear-gradient(90deg,#ea5455,#ff6b6b)' }};
        border-radius:99px;transition:width linear;"></div>
    </div>
    <p style="font-size:.72rem;color:#cbd5e1;margin-top:6px;">Menuju portal dalam beberapa saat...</p>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btnOpen        = document.getElementById('btn-open-envelope');
  const envelopeWrap   = document.getElementById('envelope-wrap');
  const envelopeSvg    = document.getElementById('envelope-svg');
  const envelopeScreen = document.getElementById('envelope-screen');
  const dashContent    = document.getElementById('dashboard-content');
  const resultPopup    = document.getElementById('result-popup');
  const resultLetter   = document.getElementById('result-letter');
  const resultStatus   = document.getElementById('result-status-text');
  const countdownBar   = document.getElementById('result-countdown-bar');
  const isLulus        = {{ $isLulus ? 'true' : 'false' }};

  // Prevent double-click
  let opened = false;

  function openEnvelope() {
    if (opened) return;
    opened = true;

    // Step 1: animate SVG open
    envelopeSvg.classList.add('envelope-opened');
    envelopeWrap.style.animation = 'none';
    envelopeWrap.style.transform = 'scale(1.1)';
    if (btnOpen) { btnOpen.innerHTML = '<i class="ri ri-loader-4-line me-2"></i>Membuka...'; btnOpen.disabled = true; }

    // Step 2: fade out envelope →  show result popup
    setTimeout(function() {
      envelopeScreen.style.transition = 'opacity 0.6s ease';
      envelopeScreen.style.opacity = '0';

      setTimeout(function() {
        envelopeScreen.style.display = 'none';

        // Show popup
        resultPopup.style.display = 'flex';
        requestAnimationFrame(function() {
          requestAnimationFrame(function() {
            // Animate letter in
            resultLetter.style.transform = 'scale(1) translateY(0)';
            resultLetter.style.opacity   = '1';

            // Animate big status text
            setTimeout(function() {
              resultStatus.style.transform = 'scale(1)';
              resultStatus.style.opacity   = '1';

              // Confetti for lulus
              if (isLulus) triggerConfetti();
            }, 300);

            // Countdown bar shrink over DISPLAY_SECONDS
            var DISPLAY_MS = 4500;
            setTimeout(function() {
              countdownBar.style.transitionDuration = DISPLAY_MS + 'ms';
              countdownBar.style.width = '0%';
            }, 50);

            // Step 3: after countdown → hide popup, show dashboard
            setTimeout(function() {
              resultPopup.style.transition = 'opacity 0.6s ease';
              resultPopup.style.opacity    = '0';
              setTimeout(function() {
                resultPopup.style.display = 'none';
                dashContent.classList.remove('d-none');
                dashContent.style.opacity = '0';
                setTimeout(function() { dashContent.style.opacity = '1'; }, 30);
              }, 600);
            }, DISPLAY_MS + 200);
          });
        });
      }, 650);
    }, 550);
  }

  if (envelopeWrap) envelopeWrap.addEventListener('click', openEnvelope);
  if (btnOpen)       btnOpen.addEventListener('click', openEnvelope);
  if (btnOpen) {
    btnOpen.addEventListener('mouseenter', function() { if(!opened) this.style.transform='scale(1.05)'; });
    btnOpen.addEventListener('mouseleave', function() { if(!opened) this.style.transform='scale(1)'; });
  }

  function triggerConfetti() {
    var end = Date.now() + 5000;
    (function frame() {
      confetti({ particleCount: 5, angle: 60,  spread: 60, origin: { x: 0 }, zIndex: 100000 });
      confetti({ particleCount: 5, angle: 120, spread: 60, origin: { x: 1 }, zIndex: 100000 });
      if (Date.now() < end) requestAnimationFrame(frame);
    })();
  }
});
</script>
@endif
@endpush

