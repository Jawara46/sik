@extends('layouts.app')

@section('title', 'Template Surat - SIK-T')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}">
<style>
  .template-shell {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 1rem;
    background: #fff;
  }

  .template-chip {
    border: 1px solid rgba(115, 103, 240, 0.14);
    background: rgba(115, 103, 240, 0.06);
    color: #7367f0;
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    font-size: 0.8125rem;
    cursor: pointer;
    text-align: left;
  }

  .template-chip:hover {
    background: rgba(115, 103, 240, 0.12);
  }

  .template-placeholder-group {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 1rem;
    background: #fafbff;
  }

  .template-placeholder-sidebar {
    position: sticky;
    top: 5.5rem;
  }

  .template-placeholder-panel {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(47, 43, 61, 0.05);
  }

  .template-placeholder-panel .accordion-button {
    background: transparent;
    box-shadow: none;
    font-weight: 600;
    padding-inline: 0;
  }

  .template-placeholder-panel .accordion-item {
    border: 0;
    border-top: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 0;
    background: transparent;
  }

  .template-placeholder-panel .accordion-item:first-child {
    border-top: 0;
  }

  .template-placeholder-panel .accordion-body {
    padding-inline: 0;
    padding-top: 0;
  }

  .template-placeholder-scroll {
    max-height: calc(100vh - 14rem);
    overflow-y: auto;
    padding-right: 0.25rem;
  }

  .template-editor {
    min-height: 140px;
    background: #fff;
  }

  .template-editor.template-title-editor {
    min-height: 100px;
  }

  .template-preview {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 1rem;
    background: #fafbff;
    min-height: 100%;
  }

  .template-preview-canvas {
    border: 1px solid rgba(47, 43, 61, 0.08);
    border-radius: 0.875rem;
    background: #fff;
    padding: 1.5rem;
    box-shadow: 0 10px 24px rgba(47, 43, 61, 0.05);
  }

  .template-preview-canvas p {
    margin: 0 0 0.85rem;
    line-height: 1.75;
  }

  .template-preview-canvas .ql-align-center { text-align: center; }
  .template-preview-canvas .ql-align-right { text-align: right; }
  .template-preview-canvas .ql-align-justify { text-align: justify; }

  .template-identity-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
  }

  .template-identity-table td {
    padding: 0.15rem 0;
    vertical-align: top;
  }

  .template-unit-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
  }

  .template-unit-table th,
  .template-unit-table td {
    border: 1px solid rgba(47, 43, 61, 0.2);
    padding: 0.45rem 0.55rem;
    font-size: 0.875rem;
  }

  .template-sticky-preview {
    position: sticky;
    top: 5.5rem;
  }

  @media (max-width: 1199.98px) {
    .template-placeholder-sidebar,
    .template-sticky-preview {
      position: static;
    }

    .template-placeholder-scroll {
      max-height: none;
      overflow: visible;
      padding-right: 0;
    }
  }
</style>
@endpush

@section('content')
<div class="row g-6">
  <div class="col-12">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <span class="badge bg-label-primary mb-2">Layanan Kelulusan</span>
        <h4 class="mb-1">Template Surat</h4>
        <p class="text-muted mb-0">Editor ini dipakai untuk mengatur redaksi isi surat tanpa mengubah kode. Kop surat tetap mengikuti gambar kop yang diupload di profil sekolah.</p>
      </div>
      <a href="{{ route('admin.graduation.documents.index') }}" class="btn btn-outline-primary">
        <i class="ri-printer-line me-2"></i>Kembali ke Cetak SKL
      </a>
    </div>
  </div>

  <div class="col-12">
    @if (session('status'))
      <div class="alert alert-success mb-0">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger mb-0 mt-3">{{ $errors->first() }}</div>
    @endif
  </div>

  <div class="col-12">
    <div class="template-shell p-4 p-lg-5">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
          <h5 class="mb-1">Editor Isi Surat</h5>
          <p class="text-muted mb-0">Gunakan placeholder agar isi surat otomatis menyesuaikan data siswa dan sekolah saat dicetak.</p>
        </div>
        <div class="small text-muted">
          Sampel preview: <strong>{{ $sampleStudent?->name ?? 'Siswa Contoh' }}</strong>
        </div>
      </div>

      <ul class="nav nav-pills mb-4 gap-2" role="tablist">
        @foreach ($supportedTypes as $type)
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeType === $type['type'] ? 'active' : '' }}" id="tab-{{ $type['type'] }}" data-bs-toggle="pill" data-bs-target="#pane-{{ $type['type'] }}" type="button" role="tab" aria-controls="pane-{{ $type['type'] }}" aria-selected="{{ $activeType === $type['type'] ? 'true' : 'false' }}">
              {{ $type['name'] }}
            </button>
          </li>
        @endforeach
      </ul>

      <div class="tab-content p-0">
        @foreach ($supportedTypes as $type)
          @php
            $template = $templates[$type['type']];
            $variables = $previewVariables[$type['type']] ?? [];
            $prefix = $type['type'];
          @endphp
          <div class="tab-pane fade {{ $activeType === $type['type'] ? 'show active' : '' }}" id="pane-{{ $type['type'] }}" role="tabpanel" aria-labelledby="tab-{{ $type['type'] }}">
            <div class="row g-5">
              <div class="col-12 col-xl-4">
                <div class="template-placeholder-sidebar">
                  <div class="template-placeholder-panel p-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                      <div>
                        <h6 class="mb-1">Placeholder</h6>
                        <div class="small text-muted">Klik tag untuk menyisipkan token ke editor aktif.</div>
                      </div>
                      <span class="badge bg-label-primary">{{ count($placeholders) }} grup</span>
                    </div>

                    <div class="template-placeholder-scroll">
                      <div class="accordion" id="placeholderAccordion-{{ $type['type'] }}">
                        @foreach ($placeholders as $index => $group)
                          @php
                            $groupId = $type['type'] . '-' . $group['category'];
                          @endphp
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-{{ $groupId }}">
                              <button class="accordion-button {{ $index > 1 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $groupId }}" aria-expanded="{{ $index <= 1 ? 'true' : 'false' }}" aria-controls="collapse-{{ $groupId }}">
                                {{ $group['title'] }}
                              </button>
                            </h2>
                            <div id="collapse-{{ $groupId }}" class="accordion-collapse collapse {{ $index <= 1 ? 'show' : '' }}" aria-labelledby="heading-{{ $groupId }}">
                              <div class="accordion-body">
                                <div class="d-flex flex-wrap gap-2 pb-2">
                                  @foreach ($group['items'] as $placeholder)
                                    <button type="button" class="template-chip border-0 insert-placeholder" data-template-type="{{ $type['type'] }}" data-placeholder="{{ $placeholder['token'] }}" title="{{ $placeholder['token'] }}">
                                      {{ $placeholder['label'] }}
                                    </button>
                                  @endforeach
                                </div>
                              </div>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-xl-8">
                <form method="POST" action="{{ route('admin.graduation.templates.update', $type['type']) }}" class="document-template-form" data-template-type="{{ $type['type'] }}">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="document_type" value="{{ $type['type'] }}">

                  <div class="mb-4">
                    <label class="form-label">Nama Template</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $template->name) }}" maxlength="120" required>
                  </div>
                  <div class="row g-5">
                    <div class="col-12 col-xxl-7">
                      <div class="mb-4">
                        <label class="form-label">Judul Surat</label>
                        <div class="template-editor template-title-editor document-template-editor" data-template-type="{{ $type['type'] }}" data-hidden-input="{{ $prefix }}_title_html">{!! old('title_html', $template->title_html) !!}</div>
                        <input type="hidden" id="{{ $prefix }}_title_html" name="title_html" value="{{ old('title_html', $template->title_html) }}">
                      </div>

                      <div class="mb-4">
                        <label class="form-label">Paragraf Pembuka</label>
                        <div class="template-editor document-template-editor" data-template-type="{{ $type['type'] }}" data-hidden-input="{{ $prefix }}_intro_html">{!! old('intro_html', $template->intro_html) !!}</div>
                        <input type="hidden" id="{{ $prefix }}_intro_html" name="intro_html" value="{{ old('intro_html', $template->intro_html) }}">
                      </div>

                      <div class="mb-4">
                        <label class="form-label">Isi Utama</label>
                        <div class="template-editor document-template-editor" data-template-type="{{ $type['type'] }}" data-hidden-input="{{ $prefix }}_body_html">{!! old('body_html', $template->body_html) !!}</div>
                        <input type="hidden" id="{{ $prefix }}_body_html" name="body_html" value="{{ old('body_html', $template->body_html) }}">
                      </div>

                      <div class="mb-4">
                        <label class="form-label">Paragraf Penutup</label>
                        <div class="template-editor document-template-editor" data-template-type="{{ $type['type'] }}" data-hidden-input="{{ $prefix }}_closing_html">{!! old('closing_html', $template->closing_html) !!}</div>
                        <input type="hidden" id="{{ $prefix }}_closing_html" name="closing_html" value="{{ old('closing_html', $template->closing_html) }}">
                      </div>

                      <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                          <i class="ri-save-line me-2"></i>Simpan Template
                        </button>
                        <a href="{{ route('admin.graduation.templates.index', ['type' => $type['type']]) }}" class="btn btn-outline-secondary">
                          Reset Tampilan
                        </a>
                      </div>
                    </div>

                    <div class="col-12 col-xxl-5">
                      <div class="template-sticky-preview">
                        <div class="template-preview p-4">
                          <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div>
                              <h6 class="mb-1">Preview Live</h6>
                              <div class="small text-muted">Pratinjau dengan data sampel sekolah aktif.</div>
                            </div>
                            <span class="badge bg-label-info">{{ $type['name'] }}</span>
                          </div>

                          <div class="template-preview-canvas ql-editor" id="preview-{{ $type['type'] }}"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const toolbarOptions = [
      [{ header: [false, 1, 2, 3] }],
      ['bold', 'italic', 'underline'],
      [{ align: [] }],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['blockquote', 'clean']
    ];

    const editors = [];
    const focusedByType = {};
    const previewVariables = @json($previewVariables);
    const placeholderStateKey = 'sik-template-placeholder-state';
    let placeholderState = {};

    try {
      placeholderState = JSON.parse(window.localStorage.getItem(placeholderStateKey) || '{}') || {};
    } catch (error) {
      placeholderState = {};
    }

    const persistPlaceholderState = () => {
      try {
        window.localStorage.setItem(placeholderStateKey, JSON.stringify(placeholderState));
      } catch (error) {
        // ignore storage write failures
      }
    };

    const replacePlaceholders = (html, variables) => {
      let rendered = html || '';
      Object.entries(variables || {}).forEach(([token, value]) => {
        rendered = rendered.split(token).join(value);
      });
      return rendered;
    };

    const normalizeEditorHtml = html => {
      if (!html || html === '<p><br></p>') {
        return '';
      }
      return html;
    };

    const buildPreviewHtml = type => {
      const title = normalizeEditorHtml(document.getElementById(`${type}_title_html`)?.value || '');
      const intro = normalizeEditorHtml(document.getElementById(`${type}_intro_html`)?.value || '');
      const body = normalizeEditorHtml(document.getElementById(`${type}_body_html`)?.value || '');
      const closing = normalizeEditorHtml(document.getElementById(`${type}_closing_html`)?.value || '');
      const vars = previewVariables[type] || {};

      const identityHtml = `
        <table class="template-identity-table">
          <tr><td width="120">Nama</td><td width="12">:</td><td><strong>${vars['@{{nama_siswa}}'] || '-'}</strong></td></tr>
          <tr><td>NISN</td><td>:</td><td>${vars['@{{nisn}}'] || '-'}</td></tr>
          <tr><td>Tempat, Tgl Lahir</td><td>:</td><td>${vars['@{{tempat_lahir}}'] || '-'}, ${vars['@{{tanggal_lahir}}'] || '-'}</td></tr>
          <tr><td>Orang Tua/Wali</td><td>:</td><td>${vars['@{{nama_orang_tua}}'] || '-'}</td></tr>
          <tr><td>Jurusan</td><td>:</td><td>${vars['@{{jurusan}}'] || '-'}</td></tr>
        </table>
      `;

      const unitTable = `
        <table class="template-unit-table">
          <tr><th>Kode Unit</th><th>Judul Unit</th><th>Keterangan</th></tr>
          <tr><td>AKL.CP01</td><td>Penyusunan Laporan Keuangan</td><td>Kompeten</td></tr>
          <tr><td>AKL.CP02</td><td>Pengelolaan Buku Besar</td><td>Kompeten</td></tr>
        </table>
      `;

      const transcriptTable = `
        <table class="template-unit-table">
          <tr><th>Mata Pelajaran</th><th>S1</th><th>S2</th><th>S3</th><th>S4</th><th>S5</th><th>S6</th><th>Rata-rata</th></tr>
          <tr><td>Bahasa Indonesia</td><td>88</td><td>89</td><td>90</td><td>90</td><td>91</td><td>92</td><td>90.00</td></tr>
          <tr><td>Matematika</td><td>84</td><td>85</td><td>86</td><td>86</td><td>87</td><td>88</td><td>86.00</td></tr>
        </table>
        <table class="template-unit-table">
          <tr><th>Kelompok A</th><th>Kelompok B</th><th>Kelompok C / PKL</th><th>Rata-rata Umum</th></tr>
          <tr><td>89.50</td><td>87.20</td><td>90.00</td><td>88.90</td></tr>
        </table>
      `;

      if (type === 'ukk_statement') {
        return `
          ${replacePlaceholders(title, vars)}
          ${replacePlaceholders(intro, vars)}
          ${identityHtml}
          ${replacePlaceholders(body, vars)}
          ${unitTable}
          ${replacePlaceholders(closing, vars)}
          <p class="ql-align-right"><strong>${vars['@{{tempat_surat}}'] || '-'}, ${vars['@{{tanggal_surat}}'] || '-'}</strong><br>Kepala Sekolah<br><br><br><strong>${vars['@{{nama_kepsek}}'] || '-'}</strong></p>
        `;
      }

      if (type === 'transcript') {
        return `
          ${replacePlaceholders(title, vars)}
          ${replacePlaceholders(intro, vars)}
          ${identityHtml}
          ${replacePlaceholders(body, vars)}
          ${transcriptTable}
          ${replacePlaceholders(closing, vars)}
          <p class="ql-align-right"><strong>${vars['@{{tempat_surat}}'] || '-'}, ${vars['@{{tanggal_surat}}'] || '-'}</strong><br>Kepala Sekolah<br><br><br><strong>${vars['@{{nama_kepsek}}'] || '-'}</strong></p>
        `;
      }

      return `
        ${replacePlaceholders(title, vars)}
        ${replacePlaceholders(intro, vars)}
        ${identityHtml}
        ${replacePlaceholders(body, vars)}
        ${replacePlaceholders(closing, vars)}
        <p class="ql-align-right"><strong>${vars['@{{tempat_surat}}'] || '-'}, ${vars['@{{tanggal_surat}}'] || '-'}</strong><br>Kepala Sekolah<br><br><br><strong>${vars['@{{nama_kepsek}}'] || '-'}</strong></p>
      `;
    };

    const refreshPreview = type => {
      const preview = document.getElementById(`preview-${type}`);
      if (!preview) {
        return;
      }
      preview.innerHTML = buildPreviewHtml(type);
    };

    document.querySelectorAll('.document-template-editor').forEach(function (element) {
      const hiddenInputId = element.dataset.hiddenInput;
      const templateType = element.dataset.templateType;
      const hiddenInput = document.getElementById(hiddenInputId);

      const quill = new Quill(element, {
        modules: { toolbar: toolbarOptions },
        theme: 'snow'
      });

      if (hiddenInput && hiddenInput.value) {
        quill.root.innerHTML = hiddenInput.value;
      }

      quill.on('editor-change', function () {
        focusedByType[templateType] = quill;
      });

      quill.on('text-change', function () {
        if (hiddenInput) {
          hiddenInput.value = normalizeEditorHtml(quill.root.innerHTML);
        }
        refreshPreview(templateType);
      });

      editors.push({ quill, templateType, hiddenInput });
      refreshPreview(templateType);
    });

    document.querySelectorAll('.insert-placeholder').forEach(function (button) {
      button.addEventListener('click', function () {
        const templateType = button.dataset.templateType;
        const placeholder = button.dataset.placeholder || '';
        const editor = focusedByType[templateType] || editors.find(item => item.templateType === templateType)?.quill;

        if (!editor) {
          return;
        }

        const range = editor.getSelection(true);
        const index = range ? range.index : editor.getLength();
        editor.insertText(index, placeholder, 'user');
        editor.setSelection(index + placeholder.length, 0, 'silent');
      });
    });

    document.querySelectorAll('.document-template-form').forEach(function (form) {
      form.addEventListener('submit', function () {
        editors.forEach(function (item) {
          if (item.hiddenInput) {
            item.hiddenInput.value = normalizeEditorHtml(item.quill.root.innerHTML);
          }
        });
      });
    });

    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (tabButton) {
      tabButton.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target') || '';
        const type = target.replace('#pane-', '');
        refreshPreview(type);
      });
    });

    document.querySelectorAll('.accordion-collapse[id^="collapse-"]').forEach(function (element) {
      const elementId = element.id;
      if (Object.prototype.hasOwnProperty.call(placeholderState, elementId)) {
        const shouldShow = placeholderState[elementId] === true;
        element.classList.toggle('show', shouldShow);
        const trigger = document.querySelector(`[data-bs-target="#${elementId}"]`);
        if (trigger) {
          trigger.classList.toggle('collapsed', !shouldShow);
          trigger.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
        }
      }

      element.addEventListener('shown.bs.collapse', function () {
        placeholderState[elementId] = true;
        persistPlaceholderState();
      });

      element.addEventListener('hidden.bs.collapse', function () {
        placeholderState[elementId] = false;
        persistPlaceholderState();
      });
    });
  });
</script>
@endpush
