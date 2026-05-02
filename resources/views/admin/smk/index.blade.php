@extends('layouts.app')

@section('title', 'Data Kompetensi & PKL (SMK) - SIK-T')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
@endpush

@section('content')
<div class="row g-6">
  <!-- Template Download Card -->
  <div class="col-12 col-xl-5">
    <div class="card h-100">
      <div class="card-header pb-3">
        <h5 class="card-title mb-1">Unduh Template</h5>
        <p class="text-muted mb-0">Pilih jenis template yang ingin diunduh untuk pengisian offline.</p>
      </div>
      <div class="card-body d-flex flex-column gap-4">
        <div class="grade-card-muted rounded-4 p-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08), rgba(40, 199, 111, 0.08)); border: 1px dashed rgba(115, 103, 240, 0.2);">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Total Siswa SMK</span>
            <strong>{{ count($students) }}</strong>
          </div>
          <div class="d-flex justify-content-between mb-0">
            <span class="text-muted">Jurusan Terdaftar</span>
            <strong>{{ count($majors) }}</strong>
          </div>
        </div>

        <div class="d-grid gap-3">
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDownload">
            <i class="ri-download-2-line me-2"></i> Pilih & Unduh Template
          </button>
        </div>

        <div class="small text-muted mt-auto">
          Template <strong>Nilai PKL/UKK Utama</strong> berisi rekap nilai Tempat PKL dan Nilai Akhir UKK.<br>
          Template <strong>Nilai Unit</strong> berisi lembar yang sudah disesuaikan dengan Unit Kompetensi (SKKNI) per jurusan.
        </div>
      </div>
    </div>
  </div>

  <!-- Upload Data Card -->
  <div class="col-12 col-xl-7">
    <div class="card h-100">
      <div class="card-header pb-3">
        <h5 class="card-title mb-1">Upload Nilai Kompetensi</h5>
        <p class="text-muted mb-0">Unggah file Excel hasil isian penilai. Sistem akan menyinkronisasi data ke siswa terkait berdasarkan NISN.</p>
      </div>
      <div class="card-body">
        <form action="{{ route('admin.grades.competency.import') }}" method="POST" enctype="multipart/form-data" class="row g-4">
          @csrf
          <div class="col-12">
            <label class="form-label d-block text-body">Jenis Data yang Diunggah</label>
            <div class="form-check form-check-inline mt-2">
              <input class="form-check-input" type="radio" name="import_type" id="import_type_pkl_card" value="pkl" checked>
              <label class="form-check-label" for="import_type_pkl_card">Data Tempat PKL & Nilai PKL Akhir (Saja)</label>
            </div>
            <div class="form-check form-check-inline mt-2">
              <input class="form-check-input" type="radio" name="import_type" id="import_type_units_card" value="units">
              <label class="form-check-label" for="import_type_units_card">Nilai Per-Unit Kompetensi (Jurusan)</label>
            </div>
          </div>
          <div class="col-12 mt-1">
            <label for="pkl_file_card" class="form-label">File Excel (.xlsx)</label>
            <input type="file" id="pkl_file_card" name="pkl_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            <small class="text-muted d-block mt-2">Pastikan kolom NISN sesuai agar data terbaca.</small>
          </div>
          <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
            <button type="submit" class="btn btn-primary">
               <i class="ri-upload-2-line me-2"></i> Upload Sekarang
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h5 class="card-title mb-1">Pusat Data Kompetensi Keahlian & PKL</h5>
          <p class="text-muted mb-0">Kelola riwayat Praktik Kerja Lapangan (PKL) dan Uji Kompetensi Keahlian (UKK) siswa SMK.</p>
        </div>
      </div>
      <div class="card-body pb-0">
         @if (session('status'))
             <div class="alert alert-success alert-dismissible" role="alert">
                 {{ session('status') }}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif
         @if ($errors->any())
             <div class="alert alert-danger alert-dismissible" role="alert">
                 {{ $errors->first() }}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif
         @if (session('import_log') && count(session('import_log')) > 0)
             <div class="alert alert-warning alert-dismissible" role="alert">
                 <strong>Beberapa baris dilewati selama import:</strong>
                 <ul class="mb-0 mt-2">
                     @foreach(session('import_log') as $log)
                        <li>{{ $log }}</li>
                     @endforeach
                 </ul>
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif
         <div class="alert alert-info py-2 mb-3 mt-2">
            <i class="ri-information-line me-2"></i> Toggle sembunyikan atau tampilkan data PKL di dalam SKL/Transkrip dapat diatur secara global di menu <strong>Profil Sekolah</strong>.
         </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table border-top" id="smkRecordsTable">
          <thead>
            <tr>
              <th>NISN / Nama</th>
              <th>Jurusan</th>
              <th style="width: 20%">Tempat PKL</th>
              <th style="width: 10%">Nilai PKL</th>
              <th>UKK (Nilai Akhir)</th>
              <th>Status UKK</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($students as $student)
            <tr>
              <td>
                <div class="fw-semibold">{{ $student->name }}</div>
                <small class="text-muted">{{ $student->nisn }}</small>
              </td>
              <td>{{ $student->major?->name ?? '-' }}</td>
              <td>
                  <input type="text" class="form-control form-control-sm border-0 bg-transparent px-1 pkl-inline-input" 
                         data-id="{{ $student->id }}" data-field="company_name" 
                         value="{{ $student->smkRecord?->company_name }}" 
                         placeholder="Ketuk untuk input...">
              </td>
              <td>
                  <input type="number" step="0.01" min="0" max="100" 
                         class="form-control form-control-sm border-0 bg-transparent px-1 pkl-inline-input" 
                         data-id="{{ $student->id }}" data-field="pkl_score" 
                         value="{{ $student->smkRecord?->pkl_score }}" 
                         placeholder="-">
              </td>
              <td>
                 @if($student->smkRecord?->ukk_final_score)
                    <span class="fw-semibold">{{ $student->smkRecord->ukk_final_score }}</span>
                 @else
                    -
                 @endif
              </td>
              <td>
                 @if($student->smkRecord?->ukk_status)
                    <span class="badge bg-label-success">{{ $student->smkRecord->ukk_status }}</span>
                 @else
                    <span class="badge bg-label-warning">Belum Ada Data</span>
                 @endif
              </td>
              <td>
                <div class="d-flex align-items-center gap-2 text-nowrap">
                  <button type="button" class="btn btn-sm btn-outline-primary btn-edit-record" 
                          data-id="{{ $student->id }}"
                          data-name="{{ $student->name }}"
                          title="Input Nilai UKK">
                    <i class="ri ri-pencil-line"></i> Input UKK
                  </button>
                  
                  @if($student->smkRecord !== null)
                  <button type="button" class="btn btn-sm btn-outline-danger btn-delete-record"
                          data-id="{{ $student->id }}"
                          data-name="{{ $student->name }}"
                          title="Hapus / Reset Seluruh Nilai (Termasuk PKL)">
                    <i class="ri ri-delete-bin-line"></i> Reset
                  </button>
                  @endif
                  
                  @if(in_array($student->smkRecord?->ukk_status, ['Kompeten', 'Sangat Kompeten']))
                    <a href="{{ route('admin.grades.competency.print.certificate', $student->id) }}" target="_blank" class="btn btn-sm btn-outline-success" title="Cetak Sertifikat UKK">
                      <i class="ri ri-award-line"></i> Sertifikat
                    </a>
                    <a href="{{ route('admin.grades.competency.print.statement', $student->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Surat Keterangan UKK">
                      <i class="ri ri-file-text-line"></i> Surat UKK
                    </a>
                  @endif
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Input Record -->
<div class="modal fade" id="modalRecord" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRecordLabel">Input Kompetensi Keahlian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formRecord" onsubmit="return false">
        <div class="modal-body">
          <p class="text-muted mb-4">Siswa: <strong id="display_student_name"></strong></p>
          
          <input type="hidden" id="student_id" name="student_id">
          
          <h6 class="mb-3">1. Praktik Kerja Lapangan (PKL)</h6>
          <div class="mb-3">
            <label class="form-label" for="company_name">Tempat PKL / Industri Terkait</label>
            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Misal: PT Telkom Akses">
          </div>
          <div class="mb-4">
            <label class="form-label" for="pkl_score">Nilai Akhir PKL</label>
            <input type="number" step="0.01" min="0" max="100" class="form-control" id="pkl_score" name="pkl_score" placeholder="0 - 100">
          </div>
          
          <h6 class="mb-3">2. Uji Kompetensi Keahlian (UKK)</h6>
          <div id="loading_units" class="text-center d-none my-3">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="text-muted mt-2">Memuat Unit Kompetensi...</p>
          </div>
          
          <div id="unitsContainer">
              <!-- Dynamically populated via AJAX -->
          </div>
          
          <div class="mb-3 mt-4 border-top pt-3">
             <div class="form-text mt-0 mb-3 text-info">
                 <i class="ri-information-line"></i> Nilai akhir UKK (`ukk_final_score`) akan dihitung otomatis berisi Rata-rata dari nilai per-unit di atas. Jika Anda mengosongkan dropdown Status di bawah ini, sistem otomatis menilainya berdasar kriteria ketuntasan minimal.
             </div>
             <label class="form-label" for="ukk_status">Paksa Override Status (Opsional)</label>
             <select id="ukk_status" name="ukk_status" class="form-select">
                <option value="">-- Hitung Otomatis Berdasar Rata-Rata Nilai Unit --</option>
                <option value="Sangat Kompeten">Sangat Kompeten</option>
                <option value="Kompeten">Kompeten</option>
                <option value="Tidak Kompeten">Tidak Kompeten</option>
             </select>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btnSaveRecord">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Download Template -->
<div class="modal fade" id="modalDownload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDownloadLabel">Unduh Template Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.grades.competency.template') }}" method="GET">
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label d-block text-body">Jenis Template</label>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="type" id="type_pkl" value="pkl" checked onchange="toggleMajorSelect(this.value)">
              <label class="form-check-label" for="type_pkl">Data Tempat PKL & Nilai PKL Akhir (Saja)</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="type" id="type_units" value="units" onchange="toggleMajorSelect(this.value)">
              <label class="form-check-label" for="type_units">Nilai Per-Unit Kompetensi (Khusus Jurusan)</label>
            </div>
          </div>
          
          <div class="mb-3 d-none" id="major_select_container">
            <label for="major_id" class="form-label">Pilih Jurusan</label>
            <select class="form-select" id="major_id" name="major_id">
              <option value="">-- Pilih Jurusan --</option>
              @foreach ($majors as $major)
                <option value="{{ $major->id }}">{{ $major->name }} ({{ $major->code }})</option>
              @endforeach
            </select>
            <div class="form-text">Masing-masing jurusan memiliki daftar unit kompetensi (SKKNI) yang diujikan secara berbeda.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" onclick="setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('modalDownload')).hide(); }, 500)">
             <i class="ri-file-excel-line me-1"></i> Unduh Excel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /Modal -->
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('smkRecordsTable');
    if (table && typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.DataTable !== 'undefined') {
        window.jQuery(table).DataTable({
          pageLength: 25,
          order: [[0, 'asc']],
          language: {
            search: 'Cari:',
            lengthMenu: '_MENU_',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            infoEmpty: 'Belum ada data',
            zeroRecords: 'Data tidak ditemukan'
          }
        });
    }

    // Modal Handling
    const modalRecord = new bootstrap.Modal(document.getElementById('modalRecord'));
    
    // Use event delegation for datatables compatibility
    $(document).on('click', '.btn-edit-record', function() {
        const btn = $(this);
        const studentId = btn.data('id');
        
        document.getElementById('student_id').value = studentId;
        document.getElementById('display_student_name').innerText = btn.data('name');
        
        const formDiv = document.getElementById('formRecord');
        const unitsContainer = document.getElementById('unitsContainer');
        const loadingDiv = document.getElementById('loading_units');
        
        // Reset inputs
        document.getElementById('company_name').value = '';
        document.getElementById('pkl_score').value = '';
        document.getElementById('ukk_status').value = '';
        unitsContainer.innerHTML = '';
        
        loadingDiv.classList.remove('d-none');
        modalRecord.show();
        
        // Fetch data
          const url = `{{ url('admin/grades/competency') }}/${studentId}/record`;
        fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(json => {
            loadingDiv.classList.add('d-none');
            if (json.success) {
                const data = json.data;
                document.getElementById('company_name').value = data.company_name;
                document.getElementById('pkl_score').value = data.pkl_score;
                document.getElementById('ukk_status').value = data.ukk_status;
                
                if (data.units && data.units.length > 0) {
                    let html = '';
                    data.units.forEach((unit, idx) => {
                        html += `
                        <div class="row align-items-center mb-2">
                           <div class="col-8">
                               <label class="form-label mb-0 text-truncate d-block" title="${unit.judul_unit}">
                                   <small class="text-primary">${unit.kode_unit}</small><br>
                                   ${unit.judul_unit}
                               </label>
                           </div>
                           <div class="col-4">
                               <input type="number" step="0.01" min="0" max="100" 
                                      class="form-control unit-score-input" 
                                      data-unit-id="${unit.id}" 
                                      placeholder="0-100" 
                                      value="${unit.score !== null ? unit.score : ''}">
                           </div>
                        </div>`;
                    });
                    unitsContainer.innerHTML = html;
                } else {
                    unitsContainer.innerHTML = `<div class="alert alert-warning py-2 mb-0"><i class="ri-alert-line me-2"></i>Jurusan siswa ini belum memiliki Master Unit Kompetensi. Silakan tambah di menu Profil Sekolah > Jurusan terlebih dahulu.</div>`;
                }
            } else {
                unitsContainer.innerHTML = `<div class="text-danger">Gagal memuat data.</div>`;
            }
        })
        .catch(err => {
            loadingDiv.classList.add('d-none');
            unitsContainer.innerHTML = `<div class="text-danger">Terjadi kesalahan jaringan.</div>`;
        });
    });

    document.getElementById('formRecord').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btnSave = document.getElementById('btnSaveRecord');
        btnSave.disabled = true;
        btnSave.innerHTML = 'Menyimpan...';

        const studentId = document.getElementById('student_id').value;
          const url = `{{ url('admin/grades/competency') }}/${studentId}/record`;
        
        // Collect Unit Scores
        const unitInputs = document.querySelectorAll('.unit-score-input');
        const unitScores = {};
        unitInputs.forEach(input => {
            if (input.value !== '') {
                unitScores[input.getAttribute('data-unit-id')] = input.value;
            }
        });
        
        fetch(url, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                company_name: document.getElementById('company_name').value,
                pkl_score: document.getElementById('pkl_score').value,
                ukk_status: document.getElementById('ukk_status').value,
                unit_scores: unitScores
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error occurred');
                btnSave.disabled = false;
                btnSave.innerHTML = 'Simpan Data';
            }
        })
        .catch(err => {
            alert('A network error occurred.');
            console.error(err);
            btnSave.disabled = false;
            btnSave.innerHTML = 'Simpan Data';
        });
    });
  });

  function toggleMajorSelect(val) {
      const container = document.getElementById('major_select_container');
      const select = document.getElementById('major_id');
      if (val === 'units') {
          container.classList.remove('d-none');
          select.setAttribute('required', 'required');
      } else {
          container.classList.add('d-none');
          select.removeAttribute('required');
      }
  }

  // Delete/Reset Record Mapping
  $(document).on('click', '.btn-delete-record', function() {
      const studentId = $(this).data('id');
      const studentName = $(this).data('name');
      
      if (confirm(`Apakah Anda yakin ingin MENGHAPUS / MERESET seluruh data nilai PKL & UKK milik ${studentName}? Aksi ini tidak dapat dibatalkan.`)) {
            const url = `{{ url('admin/grades/competency') }}/${studentId}/record`;
          
          fetch(url, {
              method: 'DELETE',
              headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
              }
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  window.location.reload();
              } else {
                  alert(data.message || 'Gagal menghapus data.');
              }
          })
          .catch(err => {
              console.error(err);
              alert('Terjadi kesalahan jaringan.');
          });
      }
  });

  // Inline Auto Save for PKL
  let pklTimer;
  $(document).on('blur change', '.pkl-inline-input', function() {
      const input = $(this);
      const studentId = input.data('id');
      const field = input.data('field');
      const value = input.val();
      const originalValue = input[0].defaultValue;

      // Only save if value actually changed
      if (value === originalValue && input[0].type !== 'number') return;
      if (input[0].type === 'number' && parseFloat(value) === parseFloat(originalValue)) return;
      
      // Update default value so we don't save twice
      input[0].defaultValue = value;
      
      // Visual feedback
      input.addClass('bg-label-warning');
      
      clearTimeout(pklTimer);
      pklTimer = setTimeout(() => {
          const url = `{{ url('admin/grades/competency') }}/${studentId}/pkl-inline`;
          const payload = {};
          payload[field] = value;
          
          fetch(url, {
              method: 'PATCH',
              headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
              },
              body: JSON.stringify(payload)
          })
          .then(res => res.json())
          .then(data => {
              input.removeClass('bg-label-warning');
              if (data.success) {
                  input.addClass('bg-label-success');
                  setTimeout(() => input.removeClass('bg-label-success'), 1500);
              } else {
                  input.addClass('bg-label-danger');
                  alert(data.message || 'Gagal menyimpan.');
              }
          })
          .catch(err => {
              input.removeClass('bg-label-warning').addClass('bg-label-danger');
              console.error(err);
          });
      }, 500); // 500ms debounce
  });
</script>
@endpush
