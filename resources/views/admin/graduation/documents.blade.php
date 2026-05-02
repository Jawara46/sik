@extends('layouts.app')

@section('title', 'Cetak SKL & Transkrip - SIK-T')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<style>
  .graduation-hero {
    border: 1px solid rgba(115, 103, 240, 0.12);
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.08), rgba(0, 200, 83, 0.05));
    border-radius: 1rem;
  }

  .graduation-overview-card {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 1rem;
    background: #fff;
  }

  .graduation-avatar {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    object-fit: cover;
    background: #f4f5fb;
  }

  .graduation-chip {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 999px;
    padding: 0.35rem 0.75rem;
    font-size: 0.8125rem;
    color: #6d6b77;
    background: #fff;
  }

  .graduation-helper-box {
    border: 1px dashed rgba(115, 103, 240, 0.2);
    background: rgba(115, 103, 240, 0.04);
    border-radius: 0.875rem;
  }

  .graduation-json {
    max-height: 260px;
    overflow: auto;
    border-radius: 0.875rem;
    background: #1f1f2e;
    color: #f7f7fb;
    padding: 1rem;
    font-size: 0.78rem;
    line-height: 1.55;
    margin: 0;
  }

  .graduation-action-stack {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
</style>
@endpush

@section('content')
@php
  $resolveMediaUrl = static function (?string $path, ?string $fallback = null): ?string {
      $normalized = trim((string) $path);
      if ($normalized === '') {
          return $fallback !== null ? asset($fallback) : null;
      }

      if (\Illuminate\Support\Str::startsWith($normalized, ['http://', 'https://', '//', 'data:'])) {
          return $normalized;
      }

      if (\Illuminate\Support\Str::startsWith($normalized, ['assets/', 'storage/'])) {
          return asset($normalized);
      }

      return asset('storage/' . ltrim($normalized, '/'));
  };

  $selectedStudent = collect($students)->firstWhere('id', $selectedStudentId);
@endphp

<div class="row g-6">
  <div class="col-12">
    <div class="graduation-hero p-4 p-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
          <span class="badge bg-label-primary mb-2">Layanan Kelulusan</span>
          <h4 class="mb-1">Cetak SKL & Transkrip</h4>
          <p class="text-muted mb-0">
            Satu klik `Cetak SKL` atau `Cetak Transkrip` akan otomatis sinkron data terbaru, validasi kelayakan, lalu membuka PDF siap cetak.
          </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span class="graduation-chip">{{ $students->count() }} siswa</span>
          <span class="graduation-chip">Service: {{ $linkedService }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success mb-0">{{ session('status') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger mb-0 mt-3">{{ session('error') }}</div>
    @endif
    @if (session('bulk_sync_log'))
      <div class="alert alert-warning mt-3 mb-0">
        <div class="fw-semibold mb-2">Catatan sinkronisasi draft</div>
        <ul class="mb-0 ps-4">
          @foreach ((array) session('bulk_sync_log') as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @if (session('bulk_cache_log'))
      <div class="alert alert-warning mt-3 mb-0">
        <div class="fw-semibold mb-2">Catatan generate cache PDF</div>
        <ul class="mb-0 ps-4">
          @foreach ((array) session('bulk_cache_log') as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @if (session('bulk_publish_log'))
      <div class="alert alert-warning mt-3 mb-0">
        <div class="fw-semibold mb-2">Catatan bulk publish</div>
        <ul class="mb-0 ps-4">
          @foreach ((array) session('bulk_publish_log') as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>

  <div class="col-12">
    <div class="row g-4">
      <div class="col-sm-6 col-xl-3">
        <div class="graduation-overview-card p-4 h-100">
          <small class="text-muted d-block mb-2">Siap Cetak SKL</small>
          <div class="d-flex align-items-end justify-content-between">
            <h3 class="mb-0 text-success">{{ $readinessOverview['skl_ready'] }}</h3>
            <span class="badge bg-label-success">SKL</span>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="graduation-overview-card p-4 h-100">
          <small class="text-muted d-block mb-2">Masih Blocker SKL</small>
          <div class="d-flex align-items-end justify-content-between">
            <h3 class="mb-0 text-danger">{{ $readinessOverview['skl_blocked'] }}</h3>
            <span class="badge bg-label-danger">SKL</span>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="graduation-overview-card p-4 h-100">
          <small class="text-muted d-block mb-2">Siap Cetak Transkrip</small>
          <div class="d-flex align-items-end justify-content-between">
            <h3 class="mb-0 text-success">{{ $readinessOverview['transcript_ready'] }}</h3>
            <span class="badge bg-label-success">Transkrip</span>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="graduation-overview-card p-4 h-100">
          <small class="text-muted d-block mb-2">Masih Blocker Transkrip</small>
          <div class="d-flex align-items-end justify-content-between">
            <h3 class="mb-0 text-danger">{{ $readinessOverview['transcript_blocked'] }}</h3>
            <span class="badge bg-label-danger">Transkrip</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
          <h5 class="card-title mb-1">Daftar Cetak Dokumen Kelulusan</h5>
          <p class="text-muted mb-0">Fokus utama halaman ini hanya dua aksi: `Cetak SKL` dan `Cetak Transkrip`.</p>
        </div>
        <a href="{{ route('admin.graduation.status.index') }}" class="btn btn-outline-primary">
          <i class="ri-shield-user-line me-2"></i>Status Kelulusan
        </a>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table border-top align-middle" id="graduationDocumentsTable">
          <thead>
            <tr>
              <th>No</th>
              <th>Foto</th>
              <th>NISN</th>
              <th>Nama Siswa</th>
              <th>Jurusan</th>
              <th>Status</th>
              <th>SKL</th>
              <th>Transkrip</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($students as $index => $row)
              @php
                $studentReadiness = $readinessByStudent->get($row->id, []);
                $sklState = (array) data_get($studentReadiness, 'documents.skl', []);
                $transcriptState = (array) data_get($studentReadiness, 'documents.transcript', []);
                $studentPhoto = $resolveMediaUrl($row->photo, 'assets/img/avatars/1.png');
              @endphp
              <tr>
                <td>{{ $index + 1 }}</td>
                <td><img src="{{ $studentPhoto }}" alt="Foto {{ $row->name }}" class="graduation-avatar"></td>
                <td class="fw-medium">{{ $row->nisn }}</td>
                <td>
                  <div class="fw-semibold">{{ $row->name }}</div>
                  <small class="text-muted">{{ $row->tempat_lahir ?? '-' }}{{ $row->tanggal_lahir ? ', ' . $row->tanggal_lahir->format('d M Y') : '' }}</small>
                </td>
                <td>{{ $row->major?->code ?? 'Umum' }}</td>
                <td>
                  <span class="badge {{ $row->status === 'Lulus' ? 'bg-label-success' : ($row->status === 'Tidak Lulus' ? 'bg-label-danger' : 'bg-label-warning') }}">
                    {{ $row->status ?? 'Pending' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge {{ ($sklState['ready'] ?? false) ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ ($sklState['ready'] ?? false) ? 'Siap Cetak' : 'Perlu Perbaikan' }}
                    </span>
                    <small class="text-muted">{{ count($sklState['blocking_errors'] ?? []) }} blocker</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge {{ ($transcriptState['ready'] ?? false) ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ ($transcriptState['ready'] ?? false) ? 'Siap Cetak' : 'Perlu Perbaikan' }}
                    </span>
                    <small class="text-muted">{{ count($transcriptState['blocking_errors'] ?? []) }} blocker</small>
                  </div>
                </td>
                <td>
                  <div class="graduation-action-stack">
                    <a href="{{ route('admin.graduation.documents.skl.print', $row) }}" target="_blank" class="btn btn-sm btn-success">
                      <i class="ri-printer-line me-1"></i>Cetak SKL
                    </a>
                    <a href="{{ route('admin.graduation.documents.transcript.print', $row) }}" target="_blank" class="btn btn-sm btn-outline-success">
                      <i class="ri-printer-line me-1"></i>Cetak Transkrip
                    </a>
                    <a href="{{ route('admin.graduation.documents.index', ['student' => $row->id]) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="ri-settings-3-line me-1"></i>Detail
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-5">Belum ada data siswa yang bisa diproses untuk dokumen kelulusan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="accordion" id="advancedGraduationPanel">
      <div class="accordion-item">
        <h2 class="accordion-header" id="advancedGraduationHeading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedGraduationCollapse" aria-expanded="false" aria-controls="advancedGraduationCollapse">
            Mode Lanjutan: Bulk Action, Draft, dan Detail Teknis
          </button>
        </h2>
        <div id="advancedGraduationCollapse" class="accordion-collapse collapse" aria-labelledby="advancedGraduationHeading" data-bs-parent="#advancedGraduationPanel">
          <div class="accordion-body">
            <div class="row g-4">
              <div class="col-12 col-xl-5">
                <div class="graduation-helper-box p-4 h-100">
                  <h6 class="mb-2">Tentang Draft</h6>
                  <p class="text-muted mb-3">
                    `Draft` adalah snapshot kerja sementara. Dipakai untuk sinkron data terbaru, preview, dan cek blocker sebelum dokumen resmi dipublish.
                  </p>
                  <ul class="small text-muted mb-0 ps-4">
                    <li>`Sync Draft` memperbarui snapshot dokumen.</li>
                    <li>`Preview` membuka draft PDF tanpa harus mengunduh.</li>
                    <li>`Publish` menandai dokumen final dan mengaktifkan nomor dokumen.</li>
                  </ul>
                </div>
              </div>
              <div class="col-12 col-xl-7">
                <div class="card border shadow-none h-100">
                  <div class="card-body">
                    <h6 class="mb-3">Aksi Massal</h6>
                    <form method="POST" action="{{ route('admin.graduation.documents.bulk-sync') }}" class="row g-3 align-items-end">
                      @csrf
                      <div class="col-md-4">
                        <label for="document_type" class="form-label">Jenis Dokumen</label>
                        <select id="document_type" name="document_type" class="form-select">
                          <option value="skl">SKL</option>
                          <option value="transcript">Transkrip</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label for="major_id" class="form-label">Jurusan</label>
                        <select id="major_id" name="major_id" class="form-select">
                          <option value="">Semua Jurusan</option>
                          @foreach ($majors as $major)
                            <option value="{{ $major->id }}">{{ $major->name }} ({{ $major->code }})</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label for="student_status" class="form-label">Status Siswa</label>
                        <select id="student_status" name="student_status" class="form-select">
                          <option value="">Semua Status</option>
                          <option value="Lulus">Lulus</option>
                          <option value="Tidak Lulus">Tidak Lulus</option>
                          <option value="Pending">Pending</option>
                        </select>
                      </div>
                      <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                          <button type="submit" class="btn btn-outline-primary">
                            <i class="ri-refresh-line me-2"></i>Sync Draft Massal
                          </button>
                          <button type="submit" class="btn btn-outline-secondary" formaction="{{ route('admin.graduation.documents.bulk-cache') }}">
                            <i class="ri-file-copy-2-line me-2"></i>Generate Cache PDF
                          </button>
                          <button type="submit" class="btn btn-primary" formaction="{{ route('admin.graduation.documents.bulk-publish') }}">
                            <i class="ri-send-plane-2-line me-2"></i>Bulk Publish
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              @if ($selectedStudent !== null && $documentPreview !== null)
                @php
                  $sklState = (array) data_get($documentPreview, 'readiness.documents.skl', []);
                  $transcriptState = (array) data_get($documentPreview, 'readiness.documents.transcript', []);
                  $sklDocument = data_get($documentPreview, 'documents.skl');
                  $transcriptDocument = data_get($documentPreview, 'documents.transcript');
                @endphp
                <div class="col-12">
                  <div class="card border shadow-none">
                    <div class="card-body">
                      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                        <div>
                          <h6 class="mb-1">Detail Lanjutan Siswa Terpilih</h6>
                          <p class="text-muted mb-0">{{ $documentPreview['student_name'] }} · NISN {{ $documentPreview['nisn'] }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <span class="graduation-chip">Rata-rata {{ number_format((float) ($documentPreview['average_score'] ?? 0), 2, ',', '.') }}</span>
                          <span class="graduation-chip">Overall transkrip {{ number_format((float) data_get($documentPreview, 'transcript_summary.overall_average', 0), 2, ',', '.') }}</span>
                        </div>
                      </div>

                      <div class="row g-4 mb-4">
                        <div class="col-12 col-xl-6">
                          <div class="graduation-helper-box p-4 h-100">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                              <h6 class="mb-0">SKL</h6>
                              <span class="badge {{ ($sklState['ready'] ?? false) ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ ($sklState['ready'] ?? false) ? 'Siap' : 'Blocker' }}
                              </span>
                            </div>
                            <div class="small text-muted mb-3">Status dokumen: {{ $sklDocument?->status ?? 'draft belum dibuat' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                              <form method="POST" action="{{ route('admin.graduation.documents.skl.sync', $selectedStudent) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Sync Draft</button>
                              </form>
                              <a href="{{ route('admin.graduation.documents.skl.preview', $selectedStudent) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Preview</a>
                              @if ($sklDocument?->status === 'published')
                                <form method="POST" action="{{ route('admin.graduation.documents.skl.revoke', $selectedStudent) }}">
                                  @csrf
                                  <button type="submit" class="btn btn-sm btn-outline-danger">Cabut</button>
                                </form>
                                <a href="{{ route('admin.graduation.documents.skl.download', $selectedStudent) }}" class="btn btn-sm btn-success">Unduh</a>
                              @else
                                <form method="POST" action="{{ route('admin.graduation.documents.skl.publish', $selectedStudent) }}">
                                  @csrf
                                  <button type="submit" class="btn btn-sm btn-primary">Publish</button>
                                </form>
                              @endif
                            </div>
                            @if (($sklState['blocking_errors'] ?? []) !== [])
                              <ul class="small text-muted ps-4 mb-0">
                                @foreach ((array) $sklState['blocking_errors'] as $item)
                                  <li>{{ $item }}</li>
                                @endforeach
                              </ul>
                            @endif
                          </div>
                        </div>

                        <div class="col-12 col-xl-6">
                          <div class="graduation-helper-box p-4 h-100">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                              <h6 class="mb-0">Transkrip</h6>
                              <span class="badge {{ ($transcriptState['ready'] ?? false) ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ ($transcriptState['ready'] ?? false) ? 'Siap' : 'Blocker' }}
                              </span>
                            </div>
                            <div class="small text-muted mb-3">Status dokumen: {{ $transcriptDocument?->status ?? 'draft belum dibuat' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                              <form method="POST" action="{{ route('admin.graduation.documents.transcript.sync', $selectedStudent) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Sync Draft</button>
                              </form>
                              <a href="{{ route('admin.graduation.documents.transcript.preview', $selectedStudent) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Preview</a>
                              @if ($transcriptDocument?->status === 'published')
                                <form method="POST" action="{{ route('admin.graduation.documents.transcript.revoke', $selectedStudent) }}">
                                  @csrf
                                  <button type="submit" class="btn btn-sm btn-outline-danger">Cabut</button>
                                </form>
                                <a href="{{ route('admin.graduation.documents.transcript.download', $selectedStudent) }}" class="btn btn-sm btn-success">Unduh</a>
                              @else
                                <form method="POST" action="{{ route('admin.graduation.documents.transcript.publish', $selectedStudent) }}">
                                  @csrf
                                  <button type="submit" class="btn btn-sm btn-primary">Publish</button>
                                </form>
                              @endif
                            </div>
                            @if (($transcriptState['blocking_errors'] ?? []) !== [])
                              <ul class="small text-muted ps-4 mb-0">
                                @foreach ((array) $transcriptState['blocking_errors'] as $item)
                                  <li>{{ $item }}</li>
                                @endforeach
                              </ul>
                            @endif
                          </div>
                        </div>
                      </div>

                      <div class="accordion" id="technicalDraftAccordion">
                        <div class="accordion-item">
                          <h2 class="accordion-header" id="technicalDraftHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalDraftCollapse" aria-expanded="false" aria-controls="technicalDraftCollapse">
                              Snapshot Draft (Teknis)
                            </button>
                          </h2>
                          <div id="technicalDraftCollapse" class="accordion-collapse collapse" aria-labelledby="technicalDraftHeading" data-bs-parent="#technicalDraftAccordion">
                            <div class="accordion-body">
                              <div class="row g-4">
                                <div class="col-12 col-xl-6">
                                  <div class="small text-muted mb-2">Snapshot SKL</div>
                                  <pre class="graduation-json">{{ json_encode(data_get($documentPreview, 'skl_snapshot_preview', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                <div class="col-12 col-xl-6">
                                  <div class="small text-muted mb-2">Snapshot Transkrip</div>
                                  <pre class="graduation-json">{{ json_encode(data_get($documentPreview, 'transcript_snapshot_preview', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('graduationDocumentsTable');
    if (!table || typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
      return;
    }

    window.jQuery(table).DataTable({
      pageLength: 10,
      order: [[3, 'asc']],
      columnDefs: [
        { targets: [1, 8], orderable: false, searchable: false }
      ],
      language: {
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ siswa',
        infoEmpty: 'Belum ada data siswa',
        zeroRecords: 'Data siswa tidak ditemukan',
        paginate: {
          previous: 'Prev',
          next: 'Next'
        }
      }
    });
  });
</script>
@endpush
