@extends('layouts.app')

@section('title', 'Edit Leger Nilai - SIK-T')

@push('styles')
<style>
  .ledger-input {
    min-width: 92px;
  }

  .ledger-sticky-col {
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 2;
  }

  .ledger-table thead .ledger-sticky-col {
    z-index: 3;
  }
</style>
@endpush

@section('content')
@php
  $student = $ledger['student'];
  $summary = $ledger['summary'];
@endphp
<div class="row g-6">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
        <div>
          <h4 class="mb-1">Leger Nilai Siswa</h4>
          <p class="mb-1 text-muted">Edit nilai semester 1-6 secara manual. Perubahan akan tersimpan otomatis saat field berubah.</p>
          <div class="d-flex flex-wrap gap-3 mt-3">
            <span class="badge bg-label-primary">{{ $student->nisn }}</span>
            <span class="badge bg-label-secondary">{{ $student->major?->name ?? 'Umum' }}</span>
            <span id="overallStatusBadge" class="badge bg-label-{{ $summary['status_class'] }}">
              {{ $summary['status_label'] }} ({{ $summary['completion_percentage'] }}%)
            </span>
          </div>
        </div>
        <div class="text-lg-end">
          <div class="fw-semibold fs-5">{{ $student->name }}</div>
          <div class="text-muted">{{ $summary['filled_semesters'] }} / {{ $summary['expected_semesters'] }} semester terisi</div>
          <div class="mt-2">Rata-rata akhir: <strong id="overallFinalAverage">{{ number_format((float) $summary['final_average'], 2) }}</strong></div>
          <a href="{{ route('admin.grades.academic.index') }}" class="btn btn-outline-primary btn-sm mt-3">Kembali ke Daftar Nilai</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-1">Leger Semester 1-6</h5>
        <p class="text-muted mb-0">Baris adalah mata pelajaran, kolom adalah semester, dan kolom terakhir menampilkan rata-rata mapel secara real-time.</p>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered ledger-table align-middle" id="ledgerTable">
            <thead>
              <tr>
                <th class="ledger-sticky-col">Mata Pelajaran</th>
                @foreach (range(1, 6) as $semester)
                  <th class="text-center">Smt {{ $semester }}</th>
                @endforeach
                <th class="text-center">Rata-rata</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($ledger['rows'] as $row)
                <tr data-subject-row="{{ $row['subject']->id }}">
                  <td class="ledger-sticky-col">
                    <div class="fw-semibold">{{ $row['subject']->name }}</div>
                    <small class="text-muted">{{ $row['subject']->category }}</small>
                  </td>
                  @foreach (range(1, 6) as $semester)
                    <td>
                      <input
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="form-control ledger-input"
                        value="{{ $row['semesters'][$semester]['score'] !== null ? number_format((float) $row['semesters'][$semester]['score'], 2, '.', '') : '' }}"
                        data-grade-input
                        data-student-id="{{ $student->id }}"
                        data-subject-id="{{ $row['subject']->id }}"
                        data-semester="{{ $semester }}"
                        data-url="{{ route('admin.grades.students.semesters.update', ['student' => $student, 'subject' => $row['subject'], 'semester' => $semester]) }}">
                    </td>
                  @endforeach
                  <td class="text-center fw-semibold" data-average-cell>{{ number_format((float) $row['average'], 2) }}</td>
                  <td class="text-center">
                    <span class="badge bg-label-{{ $row['is_complete'] ? 'success' : 'warning' }}" data-row-status>
                      {{ $row['is_complete'] ? 'Lengkap' : $row['completion_percentage'] . '%' }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const inputs = document.querySelectorAll('[data-grade-input]');

    function formatAverage(value) {
      return Number(value || 0).toFixed(2);
    }

    function setInputState(input, state) {
      input.classList.remove('border-success', 'border-danger');
      if (state === 'success') {
        input.classList.add('border-success');
      }
      if (state === 'error') {
        input.classList.add('border-danger');
      }
    }

    function updateOverall(summary) {
      const badge = document.getElementById('overallStatusBadge');
      const averageText = document.getElementById('overallFinalAverage');
      if (!badge || !averageText || !summary) {
        return;
      }

      badge.className = 'badge bg-label-' + summary.status_class;
      badge.textContent = summary.status_label + ' (' + summary.completion_percentage + '%)';
      averageText.textContent = formatAverage(summary.final_average);
    }

    async function saveGrade(input) {
      const rawValue = input.value.trim();
      const numericValue = rawValue === '' ? null : Number(rawValue);

      if (numericValue !== null && (Number.isNaN(numericValue) || numericValue < 0 || numericValue > 100)) {
        setInputState(input, 'error');
        window.M?.toast?.({ html: 'Nilai harus berada pada rentang 0 sampai 100.', classes: 'danger' });
        return;
      }

      input.disabled = true;

      try {
        const response = await fetch(input.dataset.url, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ score: numericValue })
        });

        if (!response.ok) {
          const payload = await response.json().catch(function () { return null; });
          const message = payload?.message || payload?.errors?.score?.[0] || 'Gagal menyimpan nilai.';
          throw new Error(message);
        }

        const payload = await response.json();
        const row = input.closest('tr');
        if (row) {
          const averageCell = row.querySelector('[data-average-cell]');
          const statusBadge = row.querySelector('[data-row-status]');
          if (averageCell) {
            averageCell.textContent = formatAverage(payload.average);
          }
          if (statusBadge) {
            statusBadge.className = 'badge bg-label-' + (payload.is_complete ? 'success' : 'warning');
            statusBadge.textContent = payload.is_complete ? 'Lengkap' : payload.completion_percentage + '%';
          }
        }

        setInputState(input, 'success');
        input.dataset.lastSavedValue = input.value;
        updateOverall(payload.overall);
      } catch (error) {
        setInputState(input, 'error');
        window.M?.toast?.({ html: error.message || 'Gagal menyimpan nilai.', classes: 'danger' });
      } finally {
        input.disabled = false;
      }
    }

    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        saveGrade(input);
      });

      input.dataset.lastSavedValue = input.value;
    });
  });
</script>
@endpush
