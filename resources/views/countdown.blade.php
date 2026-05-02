@extends('layouts.auth')

@section('content')
@php
    $bgUrl = $uiSettingsUrl['background_image'] ?? asset('assets/img/bg-lp.jpg');
    $logoUrl = $uiSettingsUrl['app_logo'] ?? asset('assets/img/logo.png');
@endphp

<!-- Background -->
<div class="lp-bg" style="background-image: url('{{ $bgUrl }}');"></div>

<!-- Brand Logo -->
<a href="{{ url('/') }}" class="lp-brand">
  <img src="{{ $logoUrl }}" alt="SIK-T" height="32" onerror="this.src='{{ asset('assets/img/favicon/favicon.ico') }}'">
  <span class="lp-brand-text">SIK-T</span>
</a>

<div class="d-flex min-vh-100 align-items-center justify-content-center" style="position: relative; z-index: 2;">
  <div class="text-center p-5 rounded-4 glass-panel" style="max-width: 620px; width: 100%; margin: 2rem;">

    <div class="lp-float mb-3">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
           style="width: 72px; height: 72px; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.25);">
        <i class="ri ri-timer-line text-white" style="font-size: 2rem;"></i>
      </div>
    </div>

    <h3 class="text-white fw-bold mb-3" style="text-shadow: 0 1px 8px rgba(0,0,0,0.4);">
      Pengumuman Kelulusan<br>Belum Dibuka
    </h3>

    <h1 class="display-1 text-white fw-bolder mb-4" id="countdown-timer"
        style="font-variant-numeric: tabular-nums; text-shadow: 0 2px 12px rgba(0,0,0,0.4); letter-spacing: 2px;">
      00:00:00:00
    </h1>

    <p class="text-white mb-4" style="opacity: 0.85; font-size: 1.05rem; text-shadow: 0 1px 4px rgba(0,0,0,0.5);">
      Harap bersabar. Portal pengumuman kelulusan akan otomatis terbuka setelah waktu hitung mundur selesai.
    </p>

    <div class="badge fs-6 py-2 px-4 shadow-sm"
         style="background: rgba(255,255,255,0.18); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);">
      <i class="ri ri-calendar-event-line me-1"></i>
      Jadwal Rilis:
      @if ($announcementAt)
        {{ $announcementAt->locale('id')->translatedFormat('d F Y H:i:s') }} WIB
      @else
        Belum diatur
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const countDownDate = Number(@json($announcementAt?->getTimestampMs()));
        
        if (!countDownDate || isNaN(countDownDate)) {
            console.error("Invalid countdown target. Set jadwal rilis dari panel admin.");
            return;
        }

        const timerInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;

            if (distance <= 0) {
                clearInterval(timerInterval);
                document.getElementById("countdown-timer").innerHTML = "00:00:00:00";
                window.location.replace("{{ route('login') }}");
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("countdown-timer").innerHTML = 
                (days > 0 ? days + "d " : "") + 
                hours.toString().padStart(2, '0') + "h " + 
                minutes.toString().padStart(2, '0') + "m " + 
                seconds.toString().padStart(2, '0') + "s";

        }, 1000);
    });
</script>
@endpush
