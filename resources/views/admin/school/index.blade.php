@extends('layouts.app')

@section('title', 'Profil Sekolah - SIK-T')

@push('styles')
<style>
  .upload-dropzone {
    border: 1px dashed rgba(105, 108, 255, 0.4);
    border-radius: 0.5rem;
    padding: 0.75rem;
    background: rgba(105, 108, 255, 0.04);
  }
  .upload-dropzone.compact {
    padding: 0.65rem;
  }
  .upload-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.55rem;
  }
  .upload-preview {
    width: 100%;
    max-height: 100px;
    object-fit: contain;
    background: #fff;
    border: 1px solid #eceef1;
    border-radius: 0.5rem;
    padding: 0.35rem;
  }
  .upload-preview.letterhead-preview {
    max-height: 92px;
    object-fit: cover;
    object-position: top center;
  }
  .upload-file-name {
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
  }
  .upload-file-name.wide {
    max-width: 100%;
  }
  .upload-meta {
    font-size: 12px;
    line-height: 1.45;
  }
  .wa-qr-box {
    min-height: 220px;
    border: 1px dashed #d8dbe5;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafbff;
  }
  .number-preview-box {
    border: 1px solid #e7eaf3;
    background: #fafbff;
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
  }
  .number-preview-box code {
    display: block;
    white-space: normal;
    word-break: break-all;
    font-size: 12px;
    color: #3f51b5;
  }
</style>
@endpush

@section('content')
@php
  $resolveMediaUrl = static function (?string $path, string $fallback): string {
      if (!is_string($path) || $path === '') {
          return asset($fallback);
      }

      if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:'])) {
          return $path;
      }

      $normalized = ltrim($path, '/');

      if (\Illuminate\Support\Str::startsWith($normalized, ['assets/', 'storage/'])) {
          return asset($normalized);
      }

      return asset('storage/' . $normalized);
  };
  $resolveMediaName = static function (?string $path, string $fallbackName): string {
      if (!is_string($path) || trim($path) === '') {
          return $fallbackName;
      }

      return basename(str_replace('\\', '/', $path));
  };
@endphp

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

<form method="POST" action="{{ route('admin.school.profile.update') }}" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="row g-6">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-1">Identitas Sekolah</h5>
          <p class="mb-0 text-muted">Data dasar institusi sekolah.</p>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="npsn">NPSN</label>
              <input id="npsn" name="npsn" class="form-control" value="{{ old('npsn', $school->npsn) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tipe_sekolah">Tipe Sekolah</label>
              <select id="tipe_sekolah" name="tipe_sekolah" class="form-select" required>
                @foreach (['SMP', 'MTs', 'SMK'] as $type)
                  <option value="{{ $type }}" @selected(old('tipe_sekolah', $school->tipe_sekolah) === $type)>{{ $type }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="nama_sekolah">Nama Sekolah</label>
              <input id="nama_sekolah" name="nama_sekolah" class="form-control" value="{{ old('nama_sekolah', $school->nama_sekolah ?? $school->name) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email_sekolah">Email Sekolah</label>
              <input id="email_sekolah" type="email" name="email_sekolah" class="form-control" value="{{ old('email_sekolah', $school->email_sekolah) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="telepon_sekolah">No. Telp. Sekolah</label>
              <input id="telepon_sekolah" name="telepon_sekolah" class="form-control" value="{{ old('telepon_sekolah', $school->telepon_sekolah) }}" placeholder="Contoh: (0254) 123456">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="web_sekolah">Website Sekolah</label>
              <input id="web_sekolah" type="url" name="web_sekolah" class="form-control" value="{{ old('web_sekolah', $school->web_sekolah) }}">
            </div>
            <div class="col-12">
              <label class="form-label" for="alamat_sekolah">Alamat Sekolah</label>
              <textarea id="alamat_sekolah" name="alamat_sekolah" class="form-control" rows="3">{{ old('alamat_sekolah', $school->alamat_sekolah) }}</textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tempat_surat">Tempat Surat</label>
              <input id="tempat_surat" name="tempat_surat" class="form-control" value="{{ old('tempat_surat', $school->tempat_surat) }}" placeholder="Contoh: Pasir Kupa">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tanggal_surat">Tanggal Surat</label>
              <input id="tanggal_surat" type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', optional($school->tanggal_surat)->format('Y-m-d')) }}">
            </div>

            @if ($school->tipe_sekolah === 'SMK')
            <div class="col-12 mt-4">
              <div class="border rounded p-3">
                <h6 class="mb-3">Data Penguji Kompetensi (Asesor UKK)</h6>
                <div class="table-responsive text-nowrap">
                  <table class="table table-hover table-sm">
                    <thead>
                      <tr>
                        <th>JURUSAN</th>
                        <th>PENGUJI INTERNAL</th>
                        <th>PENGUJI EKSTERNAL</th>
                        <th>AKSI</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      @forelse ($majors as $major)
                      @php
                        $assessor = $major->smkAssessor;
                      @endphp
                      <tr>
                        <td>
                          <span class="fw-medium text-primary">{{ $major->code }}</span><br>
                          <small class="text-muted">{{ $major->name }}</small>
                        </td>
                        <td>
                          @if($assessor && $assessor->internal_name)
                            <span class="fw-medium text-heading">{{ $assessor->internal_name }}</span><br>
                            <small class="text-muted">NIP. {{ $assessor->internal_nip ?? '-' }}</small>
                          @else
                            <span class="text-danger">Belum Diatur</span>
                          @endif
                        </td>
                        <td>
                          @if($assessor && $assessor->external_name)
                            <span class="fw-medium text-heading">{{ $assessor->external_name }}</span><br>
                            <small class="text-muted">{{ $assessor->external_position ?? 'Asesor' }} - {{ $assessor->external_company ?? '-' }}</small>
                          @else
                            <span class="text-danger">Belum Diatur</span>
                          @endif
                        </td>
                        <td>
                          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assessorModal-{{ $major->id }}">
                            Edit
                          </button>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada jurusan yang didaftarkan.</td>
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-1">Pejabat & Akademik</h5>
          <p class="mb-0 text-muted">Data kepala sekolah dan periode akademik aktif.</p>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-12">
              <label class="form-label" for="nama_kepsek">Nama Kepala Sekolah</label>
              <input id="nama_kepsek" name="nama_kepsek" class="form-control" value="{{ old('nama_kepsek', $school->nama_kepsek) }}">
            </div>
            <div class="col-12">
              <label class="form-label" for="nip_kepsek">NIP Kepala Sekolah</label>
              <input id="nip_kepsek" name="nip_kepsek" class="form-control" value="{{ old('nip_kepsek', $school->nip_kepsek) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tahun_pelajaran">Tahun Pelajaran</label>
              <input id="tahun_pelajaran" name="tahun_pelajaran" class="form-control" placeholder="2025/2026" value="{{ old('tahun_pelajaran', $school->tahun_pelajaran) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="semester">Semester</label>
              <select id="semester" name="semester" class="form-select">
                <option value="Ganjil" @selected(old('semester', $school->semester) === 'Ganjil')>Ganjil</option>
                <option value="Genap" @selected(old('semester', $school->semester) === 'Genap')>Genap</option>
              </select>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="show_pkl_transcript"
                  name="show_pkl_transcript"
                  value="1"
                  @checked(old('show_pkl_transcript', $school->show_pkl_transcript ?? true))
                >
                <label class="form-check-label" for="show_pkl_transcript">
                  Tampilkan nilai PKL (Kelompok C) pada transkrip
                </label>
              </div>
              <small class="text-muted">Jika nonaktif, baris PKL tidak akan ditampilkan dan tidak dihitung.</small>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="show_student_photo_on_skl"
                  name="show_student_photo_on_skl"
                  value="1"
                  @checked(old('show_student_photo_on_skl', $school->show_student_photo_on_skl ?? false))
                >
                <label class="form-check-label" for="show_student_photo_on_skl">
                  Tampilkan pas foto siswa pada SKL
                </label>
              </div>
              <small class="text-muted">Jika aktif, SKL akan memakai pas foto siswa yang sudah diunggah pada data siswa.</small>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="show_grades_on_skl"
                  name="show_grades_on_skl"
                  value="1"
                  @checked(old('show_grades_on_skl', $school->show_grades_on_skl ?? false))
                >
                <label class="form-check-label" for="show_grades_on_skl">
                  Tampilkan rincian nilai pada SKL
                </label>
              </div>
              <small class="text-muted">Jika aktif, SKL akan menyertakan tabel rincian nilai (Daftar Nilai Sementara) di bawah pernyataan kelulusan.</small>
            </div>
            <div class="col-12">
              <label class="form-label" for="skl_number_pattern">Pola Nomor SKL</label>
              <div class="input-group">
                <select class="form-select" id="skl_number_mode" name="skl_number_mode" style="max-width: 130px;">
                  <option value="dynamic" @selected(old('skl_number_mode', $school->skl_number_mode ?? 'dynamic') === 'dynamic')>Dinamis</option>
                  <option value="static" @selected(old('skl_number_mode', $school->skl_number_mode) === 'static')>Statis</option>
                </select>
                <input
                  id="skl_number_pattern"
                  name="skl_number_pattern"
                  class="form-control"
                  value="{{ old('skl_number_pattern', $school->skl_number_pattern) }}"
                  placeholder="421.5/SKL/{YEAR}/{NO}"
                >
              </div>
              <small class="text-muted d-block mt-1">Placeholder: `{YEAR}`, `{NO}`, `{NPSN}`, `{TYPE}`.</small>
              <div class="number-preview-box mt-2">
                <small class="text-muted d-block mb-1">Preview Nomor SKL</small>
                <code id="skl-number-preview">421.5/SKL/2026/001</code>
              </div>
            </div>

            <div class="col-12 mt-3">
              <label class="form-label" for="transcript_number_pattern">Pola Nomor Transkrip</label>
              <div class="input-group">
                <select class="form-select" id="transcript_number_mode" name="transcript_number_mode" style="max-width: 130px;">
                  <option value="dynamic" @selected(old('transcript_number_mode', $school->transcript_number_mode ?? 'dynamic') === 'dynamic')>Dinamis</option>
                  <option value="static" @selected(old('transcript_number_mode', $school->transcript_number_mode) === 'static')>Statis</option>
                </select>
                <input
                  id="transcript_number_pattern"
                  name="transcript_number_pattern"
                  class="form-control"
                  value="{{ old('transcript_number_pattern', $school->transcript_number_pattern) }}"
                  placeholder="421.5/TRS/{YEAR}/{NO}"
                >
              </div>
              <small class="text-muted d-block mt-1">Placeholder: `{YEAR}`, `{NO}`, `{NPSN}`, `{TYPE}`.</small>
              <div class="number-preview-box mt-2">
                <small class="text-muted d-block mb-1">Preview Nomor Transkrip</small>
                <code id="transcript-number-preview">421.5/TRS/2026/001</code>
              </div>
            </div>

            @if ($school->tipe_sekolah === 'SMK')
            <div class="col-12 mt-3">
              <label class="form-label" for="certificate_number_pattern">Pola Nomor Sertifikat UKK</label>
              <div class="input-group">
                <select class="form-select" id="certificate_number_mode" name="certificate_number_mode" style="max-width: 130px;">
                  <option value="dynamic" @selected(old('certificate_number_mode', $school->certificate_number_mode ?? 'dynamic') === 'dynamic')>Dinamis</option>
                  <option value="static" @selected(old('certificate_number_mode', $school->certificate_number_mode) === 'static')>Statis</option>
                </select>
                <input
                  id="certificate_number_pattern"
                  name="certificate_number_pattern"
                  class="form-control"
                  value="{{ old('certificate_number_pattern', $school->certificate_number_pattern) }}"
                  placeholder="420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}"
                >
              </div>
              <small class="text-muted d-block mt-1">Placeholder: `{JURUSAN}`, `{BULAN}`, `{TAHUN}`, `{NO}`.</small>
              <div class="number-preview-box mt-2">
                <small class="text-muted d-block mb-1">Preview Nomor Sertifikat</small>
                <code id="cert-number-preview">420/UKK.UMUM/V/2026/001</code>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-1">Dokumen Resmi & Tampilan Portal</h5>
          <p class="mb-0 text-muted">Upload dokumen sekolah dalam tampilan yang lebih ringkas dan mudah dikelola.</p>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-2">
              <label class="form-label">Logo Sekolah</label>
              <div class="upload-dropzone compact">
                <input type="file" name="logo" id="logo" class="d-none media-input" accept="image/*" data-preview-target="preview-logo" data-name-target="name-logo">
                <img id="preview-logo" class="upload-preview" src="{{ $resolveMediaUrl($school->logo, 'assets/img/logo.png') }}" alt="Preview Logo">
                <div class="upload-actions">
                  <span id="name-logo" class="upload-file-name">{{ $resolveMediaName($school->logo, 'Belum ada file') }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary media-picker" data-input-id="logo">Ganti File</button>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
              <label class="form-label">Kop Surat Sekolah</label>
              <div class="upload-dropzone compact">
                <input type="file" name="kop_surat" id="kop_surat" class="d-none media-input" accept="image/*" data-preview-target="preview-kop-surat" data-name-target="name-kop-surat">
                <img id="preview-kop-surat" class="upload-preview letterhead-preview" src="{{ $resolveMediaUrl($school->kop_surat, 'assets/img/layouts/layout-without-menu-light.png') }}" alt="Preview Kop Surat">
                <div class="upload-actions">
                  <span id="name-kop-surat" class="upload-file-name wide">{{ $resolveMediaName($school->kop_surat, 'Belum ada file') }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary media-picker" data-input-id="kop_surat">Ganti File</button>
                </div>
                <small class="text-muted d-block mt-2 upload-meta">Rekomendasi: `2480 x 420 px` atau rasio landscape lebar. Dipakai untuk header surat resmi.</small>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
              <label class="form-label">TTD Kepala Sekolah (PNG)</label>
              <div class="upload-dropzone compact">
                <input type="file" name="ttd_kepsek" id="ttd_kepsek" class="d-none media-input" accept="image/png,image/*" data-preview-target="preview-ttd-kepsek" data-name-target="name-ttd-kepsek">
                <img id="preview-ttd-kepsek" class="upload-preview" src="{{ $resolveMediaUrl($school->ttd_kepsek, 'assets/img/illustrations/auth-login-illustration-light.png') }}" alt="Preview TTD">
                <div class="upload-actions">
                  <span id="name-ttd-kepsek" class="upload-file-name">{{ $resolveMediaName($school->ttd_kepsek, 'Belum ada file') }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary media-picker" data-input-id="ttd_kepsek">Ganti File</button>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
              <label class="form-label">Stempel Sekolah (PNG)</label>
              <div class="upload-dropzone compact" style="margin-bottom: 8px;">
                <input type="file" name="stempel_sekolah" id="stempel_sekolah" class="d-none media-input" accept="image/png,image/*" data-preview-target="preview-stempel-sekolah" data-name-target="name-stempel-sekolah">
                <img id="preview-stempel-sekolah" class="upload-preview" src="{{ $resolveMediaUrl($school->stempel_sekolah, 'assets/img/illustrations/auth-login-illustration-light.png') }}" alt="Preview Stempel">
                <div class="upload-actions">
                  <span id="name-stempel-sekolah" class="upload-file-name">{{ $resolveMediaName($school->stempel_sekolah, 'Belum ada file') }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary media-picker" data-input-id="stempel_sekolah">Ganti File</button>
                </div>
              </div>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="use_digital_stamp" name="use_digital_stamp" value="1" @checked(old('use_digital_stamp', $school->use_digital_stamp ?? true))>
                <label class="form-check-label" for="use_digital_stamp" style="font-size: 13px;">Gunakan Digital Stempel (Cetak PDF)</label>
              </div>
              <small class="text-muted upload-meta">Matikan opsi ini jika ingin memakai stempel basah.</small>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
              <label class="form-label">Background Countdown/Login</label>
              <div class="upload-dropzone compact">
                <input type="file" name="bg_countdown" id="bg_countdown" class="d-none media-input" accept="image/*" data-preview-target="preview-bg-countdown" data-name-target="name-bg-countdown">
                <img id="preview-bg-countdown" class="upload-preview" src="{{ $resolveMediaUrl($school->bg_countdown, 'assets/img/illustrations/auth-login-illustration-light.png') }}" alt="Preview Background">
                <div class="upload-actions">
                  <span id="name-bg-countdown" class="upload-file-name">{{ $resolveMediaName($school->bg_countdown, 'Belum ada file') }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary media-picker" data-input-id="bg_countdown">Ganti File</button>
                </div>
              </div>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="use_envelope_animation" name="use_envelope_animation" value="1" @checked(old('use_envelope_animation', $school->use_envelope_animation ?? true))>
                <label class="form-check-label" for="use_envelope_animation" style="font-size: 13px;">Gunakan Efek Amplop & Confetti</label>
              </div>
              <small class="text-muted upload-meta">Ditampilkan pada layar siswa setiap saat berhasil login.</small>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">Simpan Profil Sekolah</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.media-picker').forEach(function (button) {
      button.addEventListener('click', function () {
        const inputId = button.getAttribute('data-input-id');
        const input = document.getElementById(inputId);
        if (input) {
          input.click();
        }
      });
    });

    document.querySelectorAll('.media-input').forEach(function (input) {
      input.addEventListener('change', function () {
        const previewTarget = document.getElementById(input.dataset.previewTarget);
        const nameTarget = document.getElementById(input.dataset.nameTarget);
        const file = input.files && input.files[0] ? input.files[0] : null;

        if (nameTarget) {
          nameTarget.textContent = file ? file.name : 'Belum ada file';
        }

        if (!file || !previewTarget || !file.type.startsWith('image/')) {
          return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
          if (typeof event.target?.result === 'string') {
            previewTarget.src = event.target.result;
          }
        };
        reader.readAsDataURL(file);
      });
    });

    const numberPatternTargets = [
      {
        input: document.getElementById('skl_number_pattern'),
        modeInput: document.getElementById('skl_number_mode'),
        preview: document.getElementById('skl-number-preview'),
        fallback: '421.5/SKL/{YEAR}/{NO}',
        type: 'SKL'
      },
      {
        input: document.getElementById('transcript_number_pattern'),
        modeInput: document.getElementById('transcript_number_mode'),
        preview: document.getElementById('transcript-number-preview'),
        fallback: '421.5/TRS/{YEAR}/{NO}',
        type: 'TRS'
      },
      {
        input: document.getElementById('certificate_number_pattern'),
        modeInput: document.getElementById('certificate_number_mode'),
        preview: document.getElementById('cert-number-preview'),
        fallback: '420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}',
        type: 'SERTI'
      }
    ];

    const npsnInput = document.getElementById('npsn');

    const renderPatternPreview = function (pattern, type, mode) {
      const activePattern = (pattern || '').trim() || '';
      let template = activePattern;
      if (template === '') {
        if (type === 'SKL') template = '421.5/SKL/{YEAR}/{NO}';
        else if (type === 'TRS') template = '421.5/TRS/{YEAR}/{NO}';
        else template = '420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}';
      }
      
      const year = new Date().getFullYear().toString();
      const month = new Date().getMonth() + 1;
      const romanMonths = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
      const romanMonth = romanMonths[month - 1];
      const npsn = npsnInput && npsnInput.value ? npsnInput.value.trim() : '00000000';

      let resolved = template
        .replaceAll('{YEAR}', year)
        .replaceAll('{TAHUN}', year)
        .replaceAll('{BULAN}', romanMonth)
        .replaceAll('{JURUSAN}', 'UMUM')
        .replaceAll('{NPSN}', npsn !== '' ? npsn : '00000000')
        .replaceAll('{TYPE}', type);

      if (mode === 'static') {
        return resolved;
      }

      return resolved.replaceAll('{NO}', '001');
    };

    const updateNumberPreviews = function () {
      numberPatternTargets.forEach(function (target) {
        if (!target.input || !target.preview) {
          return;
        }

        const mode = target.modeInput ? target.modeInput.value : 'dynamic';
        target.preview.textContent = renderPatternPreview(target.input.value || target.fallback, target.type, mode);
      });
    };

    numberPatternTargets.forEach(function (target) {
      if (target.input) {
        target.input.addEventListener('input', updateNumberPreviews);
      }
      if (target.modeInput) {
        target.modeInput.addEventListener('change', updateNumberPreviews);
      }
    });

    if (npsnInput) {
      npsnInput.addEventListener('input', updateNumberPreviews);
    }

    updateNumberPreviews();
  });
</script>
@endpush

<div class="row mt-1">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-1">Jadwal Rilis Pengumuman</h5>
        <p class="mb-0 text-muted">Pengaturan waktu buka portal kelulusan siswa.</p>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">
          Waktu server saat ini: {{ now(config('sik.announcement_timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i:s') }} WIB
        </p>

        <form method="POST" action="{{ route('admin.school.profile.release.update') }}">
          @csrf
          <div class="mb-4">
            <label for="announcement_date" class="form-label">Tanggal & Jam Rilis</label>
            <input
              type="datetime-local"
              id="announcement_date"
              name="announcement_date"
              class="form-control @error('announcement_date') is-invalid @enderror"
              value="{{ old('announcement_date', optional($announcementAt)->format('Y-m-d\TH:i')) }}"
              required
            />
            @error('announcement_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <p class="small text-muted mb-4">
            Zona waktu: {{ config('sik.announcement_timezone', 'Asia/Jakarta') }}.
          </p>

          <button type="submit" class="btn btn-primary">Simpan Jadwal Rilis</button>
        </form>
      </div>
    </div>
  </div>
</div>



@if ($school->tipe_sekolah === 'SMK')
@foreach ($majors as $major)
@php $assessor = $major->smkAssessor; @endphp
<div class="modal fade" id="assessorModal-{{ $major->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form action="{{ route('admin.school.profile.assessor.update', $major->id) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header pb-2 border-bottom">
        <h5 class="modal-title">Penguji UKK: <span class="text-primary">{{ $major->code }}</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pb-0">
        <div class="alert alert-primary d-flex align-items-center" role="alert">
          <i class="ri-information-line me-2"></i>
          <div>Data ini akan dicetak pada lembar Sertifikat Kompetensi. Kosongkan jika belum tersedia.</div>
        </div>
        
        <h6 class="mb-3 text-heading fw-semibold"><i class="ri-user-settings-line border rounded p-1 me-2 bg-label-primary"></i>Penguji Internal (Sekolah)</h6>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <label class="form-label">Nama Lengkap & Gelar</label>
            <input type="text" name="internal_name" class="form-control" value="{{ $assessor->internal_name ?? '' }}" placeholder="Contoh: Budi Santoso, S.Kom">
          </div>
          <div class="col-12">
            <label class="form-label">NIP / NIY</label>
            <input type="text" name="internal_nip" class="form-control" value="{{ $assessor->internal_nip ?? '' }}" placeholder="Contoh: 19800101 200501 1 003">
          </div>
        </div>

        <h6 class="mb-3 text-heading fw-semibold"><i class="ri-user-star-line border rounded p-1 me-2 bg-label-success"></i>Penguji Eksternal (Industri/DUDI)</h6>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nama Lengkap Asesor</label>
            <input type="text" name="external_name" class="form-control" value="{{ $assessor->external_name ?? '' }}" placeholder="Contoh: Andi Wijaya">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Nama Instansi/Perusahaan</label>
            <input type="text" name="external_company" class="form-control" value="{{ $assessor->external_company ?? '' }}" placeholder="Contoh: PT Telkom">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Jabatan (Opsional)</label>
            <input type="text" name="external_position" class="form-control" value="{{ $assessor->external_position ?? '' }}" placeholder="Contoh: Manager IT">
          </div>
        </div>
      </div>
      <div class="modal-footer pt-3 border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-primary">Simpan Penguji</button>
      </div>
    </form>
  </div>
</div>
@endforeach
@endif

<div class="row mt-1">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h5 class="card-title mb-1">WA Gateway All-in-One</h5>
          <p class="mb-0 text-muted">Hubungkan WhatsApp tanpa input URL server dan token manual.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span id="wa-status-badge" class="badge {{ ($waStatus['status'] ?? '') === 'CONNECTED' ? 'bg-success' : 'bg-danger' }}">
            {{ ($waStatus['status'] ?? 'DISCONNECTED') === 'CONNECTED' ? 'Connected' : 'Disconnected' }}
          </span>
          <button type="button" id="wa-connect-btn" class="btn btn-success">Hubungkan WhatsApp</button>
        </div>
      </div>
      <div class="card-body">
        <div class="wa-qr-box">
          <img id="wa-qr-image" src="{{ $waStatus['qr_code'] ?? '' }}" alt="QR WhatsApp" style="max-width: 260px; width: 100%; {{ empty($waStatus['qr_code']) || ($waStatus['status'] ?? '') === 'CONNECTED' ? 'display:none;' : '' }}">
          <p id="wa-qr-placeholder" class="mb-0 {{ empty($waStatus['qr_code']) || ($waStatus['status'] ?? '') === 'CONNECTED' ? '' : 'd-none' }} {{ ($waStatus['status'] ?? '') === 'CONNECTED' ? 'text-success fw-medium' : 'text-muted' }}">
            {{ ($waStatus['status'] ?? '') === 'CONNECTED' ? 'WhatsApp Gateway berhasil terhubung. Anda sudah dapat mengirim pesan otomatis.' : 'QR Code belum tersedia. Klik tombol "Hubungkan WhatsApp".' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const statusBadge = document.getElementById('wa-status-badge');
    const connectBtn = document.getElementById('wa-connect-btn');
    const qrImage = document.getElementById('wa-qr-image');
    const qrPlaceholder = document.getElementById('wa-qr-placeholder');

    const statusUrl = @json(route('admin.school.wa.status'));
    const qrUrl = @json(route('admin.school.wa.qr'));

    function renderStatus(data) {
      const status = (data.status || 'DISCONNECTED').toUpperCase();
      const isConnected = status === 'CONNECTED';

      statusBadge.textContent = isConnected ? 'Connected' : 'Disconnected';
      statusBadge.classList.toggle('bg-success', isConnected);
      statusBadge.classList.toggle('bg-danger', !isConnected);

      if (isConnected) {
        qrImage.style.display = 'none';
        qrPlaceholder.textContent = 'WhatsApp Gateway berhasil terhubung. Anda sudah dapat mengirim pesan otomatis.';
        qrPlaceholder.className = 'mb-0 text-success fw-medium';
      } else if (data.qr_code) {
        qrImage.src = data.qr_code;
        qrImage.style.display = 'block';
        qrPlaceholder.className = 'mb-0 d-none text-muted';
      } else {
        qrImage.style.display = 'none';
        qrPlaceholder.textContent = 'QR Code belum tersedia. Klik tombol "Hubungkan WhatsApp".';
        qrPlaceholder.className = 'mb-0 text-muted';
      }
    }

    async function loadStatus() {
      try {
        const response = await fetch(statusUrl, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const data = await response.json();
        renderStatus(data);
      } catch (error) {
        console.error('Gagal mengecek status WA:', error);
      }
    }

    async function connectWhatsApp() {
      connectBtn.disabled = true;
      connectBtn.textContent = 'Memproses...';
      try {
        const response = await fetch(qrUrl, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const data = await response.json();
        renderStatus(data);
      } catch (error) {
        console.error('Gagal mengambil QR WA:', error);
      } finally {
        connectBtn.disabled = false;
        connectBtn.textContent = 'Hubungkan WhatsApp';
      }
    }

    connectBtn.addEventListener('click', connectWhatsApp);
    loadStatus();
    setInterval(loadStatus, 10000);
  });
</script>
@endpush
