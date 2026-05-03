@extends('layouts.app')

@section('title', 'Admin Dashboard - SIK-T')

@push('styles')
<style>
  /* ── Page Background: light blue tint ── */
  .layout-page {
    background: linear-gradient(180deg, #eef3fb 0%, #f4f7fc 40%, #f8faff 100%) !important;
  }

  /* ── Stat Cards ── */
  .dash-stat-card {
    transition: transform .18s ease, box-shadow .18s ease;
    border-radius: 16px !important;
    overflow: hidden;
    background: #fff;
  }
  .dash-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(26,58,143,.1) !important;
  }
  .stat-icon-wrap {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon-wrap i { font-size: 1.6rem; }

  /* Gradient bars on top of cards */
  .dash-stat-card .bar-top {
    height: 3px; width: 100%;
    display: block;
  }

  /* ── Welcome Banner — bg-lp.jpg visible ── */
  .welcome-banner {
    background: linear-gradient(135deg, rgba(26,58,143,0.6) 0%, rgba(37,99,196,0.45) 60%, rgba(37,99,196,0.35) 100%),
                url('{{ asset("assets/img/bg-lp.jpg") }}') center / cover no-repeat;
    border-radius: 18px; padding: 28px 32px; color: #fff;
    position: relative; overflow: hidden;
    box-shadow: 0 8px 32px rgba(26,58,143,.15);
  }
  .welcome-banner h4, .welcome-banner p { color: #fff !important; }
  .badge-date {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    backdrop-filter: blur(6px);
    color: #fff; border-radius: 12px;
    padding: 12px 20px; font-size: .84rem;
    display: inline-flex; align-items: center; gap: 8px;
  }

  /* ── Section icon bubble ── */
  .section-bubble {
    width: 38px; height: 38px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
  }

  /* ── Log items ── */
  .log-avatar {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
  }

  /* ── Activity items ── */
  .act-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700; flex-shrink: 0;
    color: #fff;
  }
  .act-icon-badge {
    position: relative;
  }
  .act-icon-badge .act-event-dot {
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; color: #fff;
    position: absolute; bottom: -2px; right: -4px;
    border: 2px solid #fff;
  }

  /* ── Row 2 cards ── */
  .card {
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 2px 12px rgba(26,58,143,.06) !important;
  }
</style>
@endpush

@section('content')

{{-- Welcome Banner --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="welcome-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex align-items-center gap-4">
        <div style="font-size:3.2rem; line-height:1; filter: drop-shadow(0 2px 8px rgba(0,0,0,.2));">🎓</div>
        <div>
          <h4 class="mb-1 fw-bold">Selamat datang, {{ Auth::user()->name }}! 👋</h4>
          <p class="mb-0" style="opacity:.88;">Berikut ringkasan data <strong>Sistem Informasi Kelulusan</strong> instansi Anda.</p>
        </div>
      </div>
      <div class="badge-date">
        <i class="ri ri-calendar-event-fill" style="font-size:1.1rem;"></i>
        <div>
          <div style="font-size:.72rem; opacity:.8; line-height:1.2;">Tanggal Pengumuman</div>
          <strong>{{ $school?->tanggal_pengumuman ? \Carbon\Carbon::parse($school->tanggal_pengumuman)->translatedFormat('d M Y, H:i') : 'Belum Diatur' }}</strong>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Stat Cards ── --}}
<div class="row">

  {{-- Total Siswa --}}
  <div class="col-sm-6 col-xl-3 mb-4">
    <div class="card dash-stat-card border-0 shadow-sm h-100">
      <span class="bar-top" style="background:linear-gradient(90deg,#3b82f6,#6366f1);"></span>
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon-wrap" style="background:linear-gradient(135deg,#dbeafe,#e0e7ff);">
          <i class="ri ri-user-3-fill" style="color:#3b82f6;"></i>

        </div>
        <div>
          <p class="mb-1 text-muted small fw-semibold text-uppercase" style="letter-spacing:.6px; font-size:.72rem;">Total Siswa</p>
          <h3 class="mb-0 fw-bold lh-1">{{ number_format($totalStudents) }}</h3>
          <small class="text-muted"><i class="ri ri-database-2-line me-1"></i>Berdasarkan Import</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Jurusan --}}
  <div class="col-sm-6 col-xl-3 mb-4">
    <div class="card dash-stat-card border-0 shadow-sm h-100">
      <span class="bar-top" style="background:linear-gradient(90deg,#10b981,#34d399);"></span>
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon-wrap" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);">
          <i class="ri ri-government-fill" style="color:#10b981;"></i>
        </div>
        <div>
          <p class="mb-1 text-muted small fw-semibold text-uppercase" style="letter-spacing:.6px; font-size:.72rem;">Kompetensi Keahlian</p>
          <h3 class="mb-0 fw-bold lh-1">{{ number_format($totalMajors) }}</h3>
          <small class="text-muted"><i class="ri ri-bookmark-line me-1"></i>Jurusan Terdaftar</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Lulus --}}
  <div class="col-sm-6 col-xl-3 mb-4">
    <div class="card dash-stat-card border-0 shadow-sm h-100">
      <span class="bar-top" style="background:linear-gradient(90deg,#f59e0b,#f97316);"></span>
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon-wrap" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
          <i class="ri ri-award-fill" style="color:#f59e0b;"></i>
        </div>
        <div>
          <p class="mb-1 text-muted small fw-semibold text-uppercase" style="letter-spacing:.6px; font-size:.72rem;">Lulus / Kompeten</p>
          <h3 class="mb-0 fw-bold lh-1">{{ number_format($lulusCount) }}</h3>
          <small class="text-muted"><i class="ri ri-shield-check-line me-1"></i>Memenuhi Syarat</small>
        </div>
      </div>
    </div>
  </div>

  {{-- WA Blast --}}
  <div class="col-sm-6 col-xl-3 mb-4">
    <div class="card dash-stat-card border-0 shadow-sm h-100">
      <span class="bar-top" style="background:linear-gradient(90deg,#25D366,#128C7E);"></span>
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon-wrap" style="background:linear-gradient(135deg,#d1fae5,#bbf7d0);">
          <i class="ri ri-whatsapp-line" style="color:#25D366;"></i>
        </div>
        <div>
          <p class="mb-1 text-muted small fw-semibold text-uppercase" style="letter-spacing:.6px; font-size:.72rem;">WA Broadcast</p>
          <h3 class="mb-0 fw-bold lh-1">{{ number_format($waSent) }}</h3>
          <small class="text-muted"><i class="ri ri-chat-check-line me-1"></i>Pesan Terkirim</small>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ── Row 2: Grafik + Log WA + Activity ── --}}
<div class="row">

  {{-- Grafik Donut --}}
  <div class="col-lg-4 col-12 mb-4">
    <div class="card h-100 border-0">
      <div class="card-header border-0 pb-0 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="section-bubble" style="background:#eef3fb;">
            <i class="ri ri-pie-chart-2-fill" style="color:#3b82f6;"></i>
          </div>
          <div>
            <h5 class="m-0 fw-bold">Rasio Kelulusan</h5>
            <p class="text-muted mb-0 small">Status proporsi siswa</p>
          </div>
        </div>
        <button class="btn btn-sm btn-icon rounded-pill" style="color:#94a3b8;" title="Menu"><i class="ri ri-more-2-fill"></i></button>
      </div>
      <div class="card-body">
        <div id="graduationChart" style="min-height:240px;"></div>
        <div class="d-flex justify-content-center gap-4 mt-1">
          <div class="text-center">
            <div class="d-flex align-items-center gap-1 justify-content-center mb-1">
              <span class="rounded-circle" style="width:8px;height:8px;background:#28c76f;display:inline-block;"></span>
              <small class="fw-semibold">Lulus</small>
            </div>
            <h5 class="fw-bold text-success mb-0">{{ $totalStudents > 0 ? round(($lulusCount/$totalStudents)*100,1) : 0 }}%</h5>
            <small class="text-muted">{{ $lulusCount }} siswa</small>
          </div>
          <div class="text-center">
            <div class="d-flex align-items-center gap-1 justify-content-center mb-1">
              <span class="rounded-circle" style="width:8px;height:8px;background:#ea5455;display:inline-block;"></span>
              <small class="fw-semibold">Tertunda</small>
            </div>
            <h5 class="fw-bold text-danger mb-0">{{ $totalStudents > 0 ? round(($tidakLulusCount/$totalStudents)*100,1) : 0 }}%</h5>
            <small class="text-muted">{{ $tidakLulusCount }} siswa</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Log WA --}}
  <div class="col-lg-4 col-12 mb-4">
    <div class="card h-100 border-0">
      <div class="card-header border-0 pb-0 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="section-bubble" style="background:#d1fae5;">
            <i class="ri ri-whatsapp-line" style="color:#10b981;"></i>
          </div>
          <div>
            <h5 class="m-0 fw-bold">Log WAPI</h5>
            <p class="text-muted mb-0 small">Aktivitas pengiriman WA</p>
          </div>
        </div>
        <a href="{{ route('admin.whatsapp.history.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size:.75rem;">
          Semua <i class="ri ri-arrow-right-s-line"></i>
        </a>
      </div>
      <div class="card-body pt-3 pb-2">
        <ul class="list-unstyled m-0">
          @forelse($recentLogs->take(6) as $log)
          <li class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color: #f1f5f9 !important;">
            <div class="log-avatar" style="background:{{ $log->status === 'sent' ? '#d1fae5' : '#fee2e2' }}; color:{{ $log->status === 'sent' ? '#10b981' : '#ef4444' }};">
              <i class="ri ri-{{ $log->status === 'sent' ? 'check-double-line' : 'error-warning-line' }}"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <h6 class="mb-0 fw-semibold text-truncate" style="font-size:.88rem;">{{ $log->recipient_name ?: $log->recipient_number }}</h6>
              <small class="text-muted text-truncate d-block">{{ \Illuminate\Support\Str::limit($log->message, 35) }}</small>
            </div>
            <div class="text-end flex-shrink-0">
              <span class="badge rounded-pill {{ $log->status === 'sent' ? 'bg-label-success' : 'bg-label-danger' }} mb-1" style="font-size:.68rem;">{{ strtoupper($log->status) }}</span>
              <div style="font-size:.68rem;" class="text-muted">{{ $log->created_at->diffForHumans() }}</div>
            </div>
          </li>
          @empty
          <li class="text-center py-4">
            <div style="font-size:2.4rem;">📭</div>
            <p class="text-muted small mb-0 mt-2">Belum ada riwayat pengiriman.</p>
          </li>
          @endforelse
        </ul>
      </div>
      @if($recentLogs->count() > 0)
      <div class="card-footer bg-transparent border-0 pt-0 pb-3 text-center">
        <a href="{{ route('admin.whatsapp.history.index') }}" class="text-primary fw-semibold" style="font-size:.85rem;">
          Lihat Semua Log <i class="ri ri-arrow-right-s-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>

  {{-- Aktivitas Pengguna --}}
  <div class="col-lg-4 col-12 mb-4">
    <div class="card h-100 border-0">
      <div class="card-header border-0 pb-0 d-flex align-items-center gap-2">
        <div class="section-bubble" style="background:#eef3fb;">
          <i class="ri ri-pulse-line" style="color:#3b82f6;"></i>
        </div>
        <div>
          <h5 class="m-0 fw-bold">Aktivitas Pengguna</h5>
          <p class="text-muted mb-0 small">Log real-time portal siswa</p>
        </div>
      </div>
      <div class="card-body pt-3 pb-2">
        <ul class="list-unstyled m-0" id="activity-list">
          @forelse($recentActivities->take(5) as $act)
          @php
            $eventConfig = match($act->event) {
              'login'              => ['icon'=>'ri-login-box-line',      'color'=>'#3b82f6', 'bg'=>'#dbeafe', 'label'=>'Login'],
              'logout'             => ['icon'=>'ri-logout-box-r-line',   'color'=>'#6b7280', 'bg'=>'#f3f4f6', 'label'=>'Logout'],
              'open_envelope'      => ['icon'=>'ri-mail-open-line',      'color'=>'#ef4444', 'bg'=>'#fee2e2', 'label'=>'Buka Amplop'],
              'download_skl'       => ['icon'=>'ri-file-download-line',  'color'=>'#10b981', 'bg'=>'#d1fae5', 'label'=>'Unduh SKL'],
              'download_transkrip' => ['icon'=>'ri-file-list-3-line',    'color'=>'#8b5cf6', 'bg'=>'#ede9fe', 'label'=>'Transkrip Nilai'],
              'publish_results'    => ['icon'=>'ri-megaphone-line',      'color'=>'#f59e0b', 'bg'=>'#fef3c7', 'label'=>'Pengumuman diterbitkan'],
              'update_data'        => ['icon'=>'ri-user-settings-line',  'color'=>'#06b6d4', 'bg'=>'#cffafe', 'label'=>'Data siswa diperbarui'],
              'backup'             => ['icon'=>'ri-shield-check-line',   'color'=>'#10b981', 'bg'=>'#d1fae5', 'label'=>'Backup data berhasil dibuat'],
              default              => ['icon'=>'ri-time-line',           'color'=>'#94a3b8', 'bg'=>'#f1f5f9', 'label'=>'Aktivitas'],
            };
          @endphp
          <li class="d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color: #f1f5f9 !important;">
            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                 style="width:40px; height:40px; background:{{ $eventConfig['bg'] }}; color:{{ $eventConfig['color'] }};">
              <i class="ri {{ $eventConfig['icon'] }}" style="font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <h6 class="mb-0 fw-semibold" style="font-size:.88rem;">{{ $eventConfig['label'] }}</h6>
              <small class="text-muted d-block text-truncate">oleh {{ $act->subject_name }}</small>
            </div>
            <div class="text-end flex-shrink-0" style="font-size:.72rem;">
              <span class="text-muted">{{ $act->created_at->diffForHumans() }}</span>
            </div>
          </li>
          @empty
          <li class="text-center py-4">
            <div style="font-size:2.4rem;">🔇</div>
            <p class="text-muted small mb-0 mt-2">Belum ada aktivitas pengguna.</p>
          </li>
          @endforelse
        </ul>
      </div>
      @if($recentActivities->count() > 0)
      <div class="card-footer bg-transparent border-0 pt-0 pb-3 text-center">
        <a href="{{ route('admin.dashboard') }}" class="text-primary fw-semibold" style="font-size:.85rem;">
          Lihat Semua Aktivitas <i class="ri ri-arrow-right-s-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const lulusCount = {{ $lulusCount }};
  const tidakLulusCount = {{ $tidakLulusCount }};

  if (lulusCount === 0 && tidakLulusCount === 0) {
    document.querySelector('#graduationChart').innerHTML =
      '<div class="h-100 d-flex flex-column align-items-center justify-content-center py-5"><div style="font-size:2.5rem">📊</div><span class="text-muted mt-2 small">Data siswa kosong.</span></div>';
    return;
  }

  new ApexCharts(document.querySelector('#graduationChart'), {
    series: [lulusCount, tidakLulusCount],
    chart: { type: 'donut', height: 250, fontFamily: 'inherit',
      dropShadow: { enabled: true, blur: 6, opacity: .12 }
    },
    labels: ['Lulus / Kompeten', 'Tertunda / Batal'],
    colors: ['#28c76f', '#ea5455'],
    plotOptions: {
      pie: { donut: { size: '72%',
        labels: { show: true,
          name: { show: true, fontSize: '13px', fontWeight: 600 },
          value: { show: true, fontSize: '20px', fontWeight: 700,
            formatter: val => val + ' Siswa'
          },
          total: { show: true, showAlways: true, label: 'Total',
            fontSize: '12px', color: '#6c757d',
            formatter: w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) + ' Siswa'
          }
        }
      }}
    },
    dataLabels: { enabled: false },
    stroke: { width: 3, colors: ['#fff'] },
    legend: { show: false },
  }).render();
});
</script>
@endpush
