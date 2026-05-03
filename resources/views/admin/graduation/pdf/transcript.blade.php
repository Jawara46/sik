<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip - {{ $student['name'] ?? 'Siswa' }}</title>
    <style>
        @page { margin: 22mm 14mm 18mm 14mm; }
        body { font-family: "Times New Roman", serif; font-size: 11px; color: #111827; line-height: 1.35; }
        .header { margin-bottom: 14px; }
        .header-image { width: 100%; max-height: 82px; object-fit: contain; margin-bottom: 8px; }
        .header-fallback { width: 100%; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 84px; text-align: center; }
        .logo-cell img { width: 64px; max-height: 64px; object-fit: contain; }
        .school-title { text-align: center; }
        .school-title h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .school-title p { margin: 2px 0; font-size: 10px; }
        .doc-title { text-align: center; margin: 10px 0 8px; }
        .doc-title h2 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .doc-title p { margin: 3px 0 0; font-size: 11px; }
        .student-box { border: 1px solid #9ca3af; padding: 8px 12px; margin-bottom: 10px; }
        .student-table { width: 100%; border-collapse: collapse; }
        .student-table td { padding: 2px 4px; vertical-align: top; }
        .student-table td:first-child { width: 140px; }
        .student-table td:nth-child(2) { width: 12px; }
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grades-table th, .grades-table td { border: 1px solid #9ca3af; padding: 4px 4px; font-size: 10px; }
        .grades-table thead th { background: #eef2ff; text-align: center; padding: 6px 4px; }
        .grades-table td.subject-name { white-space: normal; word-wrap: break-word; }
        .grades-table td.number, .grades-table th.number { text-align: center; }
        .grades-table th.col-no { width: 20px; }
        .grades-table th.col-score { width: 28px; }
        .grades-table th.col-avg { width: 45px; white-space: nowrap; }
        .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-grid td { width: 25%; border: 1px solid #d1d5db; padding: 6px 8px; }
        .summary-grid .label { display: block; color: #6b7280; font-size: 9px; margin-bottom: 2px; }
        .summary-grid .value { font-size: 11px; font-weight: bold; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; }
        .sign-space { height: 74px; position: relative; }
        .sign-space img.signature { max-width: 150px; max-height: 60px; object-fit: contain; }
        .sign-space img.stamp { position: absolute; top: 6px; left: 50%; transform: translateX(-50%); max-width: 85px; max-height: 85px; opacity: 0.30; }
        .qr-block { width: 90px; text-align: center; margin: 0 auto; }
        .qr-block img { width: 64px; height: 64px; }
        .footer-note { margin-top: 8px; color: #6b7280; font-size: 9px; }
        .template-html p { margin: 0 0 8px; line-height: 1.5; }
        .template-html .ql-align-center { text-align: center; }
        .template-html .ql-align-right { text-align: right; }
        .template-html .ql-align-justify { text-align: justify; }
    </style>
</head>
<body>
    @php
        $template = (array) data_get($document, 'snapshot_payload.template', []);
    @endphp
    <div class="header">
        @if (!empty($documentMeta['use_letterhead']) && $letterheadPath)
            <img src="{{ $letterheadPath }}" alt="Kop Surat" class="header-image">
        @else
            <div class="header-fallback">
                <table class="header-table">
                    <tr>
                        <td class="logo-cell">
                            @if ($schoolLogoPath)
                                <img src="{{ $schoolLogoPath }}" alt="Logo Sekolah">
                            @endif
                        </td>
                        <td class="school-title">
                            <h1>{{ $school['nama_sekolah'] ?? '-' }}</h1>
                            <p>NPSN: {{ $school['npsn'] ?? '-' }}</p>
                            <p>{{ $school['alamat_sekolah'] ?? '-' }}</p>
                            <p>
                                Telp: {{ $school['telepon_sekolah'] ?? '-' }}
                                @if (!empty($school['email_sekolah']))
                                    | Email: {{ $school['email_sekolah'] }}
                                @endif
                                @if (!empty($school['web_sekolah']))
                                    | Web: {{ $school['web_sekolah'] }}
                                @endif
                            </p>
                        </td>
                        <td class="logo-cell">
                            @if ($tutWuriPath)
                                <img src="{{ $tutWuriPath }}" alt="Logo Tut Wuri">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    </div>

    <div class="doc-title template-html">
        {!! $template['title_html'] ?? '<p class="ql-align-center"><strong>TRANSKRIP NILAI</strong></p>' !!}
        <p>Tahun Pelajaran {{ $school['tahun_pelajaran'] ?? '-' }}</p>
    </div>

    <div class="template-html" style="margin-bottom: 10px;">
        {!! $template['intro_html'] ?? '<p>Dokumen ini memuat rekap hasil belajar peserta didik.</p>' !!}
    </div>

    <div class="student-box">
        <table class="student-table">
            <tr>
                <td>Nama Siswa</td><td>:</td><td>{{ $student['name'] ?? '-' }}</td>
                <td>NISN</td><td>:</td><td>{{ $student['nisn'] ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tempat, Tanggal Lahir</td><td>:</td><td>{{ $student['tempat_lahir'] ?? '-' }}, {{ !empty($student['tanggal_lahir']) ? \Carbon\Carbon::parse($student['tanggal_lahir'])->translatedFormat('d F Y') : '-' }}</td>
                <td>Jurusan</td><td>:</td><td>{{ $student['major_name'] ?? '-' }}</td>
            </tr>
            <tr>
                <td>Nama Orang Tua / Wali</td><td>:</td><td>{{ $student['nama_orang_tua'] ?? '-' }}</td>
                <td>Status Kelulusan</td><td>:</td><td>{{ $documentMeta['graduation_status'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="template-html" style="margin-bottom: 10px;">
        {!! $template['body_html'] ?? '<p>Tabel berikut menampilkan nilai semester 1 sampai 6 beserta rata-rata akhir untuk setiap mata pelajaran.</p>' !!}
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th class="number col-no">No</th>
                <th>Mata Pelajaran</th>
                <th class="number col-score">S1</th>
                <th class="number col-score">S2</th>
                <th class="number col-score">S3</th>
                <th class="number col-score">S4</th>
                <th class="number col-score">S5</th>
                <th class="number col-score">S6</th>
                <th class="number col-avg">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($subjects as $index => $subject)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="subject-name">
                        {{ $subject['subject_name'] ?? '-' }}
                        @if (!empty($subject['category']))
                            <div style="font-size:9px;color:#6b7280;">{{ $subject['category'] }}</div>
                        @endif
                    </td>
                    @for ($semester = 1; $semester <= 6; $semester++)
                        <td class="number">{{ data_get($subject, 'semester_scores.' . $semester) !== null ? number_format((float) data_get($subject, 'semester_scores.' . $semester), 2) : '-' }}</td>
                    @endfor
                    <td class="number">{{ number_format((float) ($subject['final_score'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="number">Belum ada data nilai yang dapat ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-grid">
        <tr>
            <td>
                <span class="label">Rata-rata Kelompok A</span>
                <span class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_a', 0), 2) }}</span>
            </td>
            <td>
                <span class="label">Rata-rata Kelompok B</span>
                <span class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_b', 0), 2) }}</span>
            </td>
            <td>
                <span class="label">Rata-rata Kelompok C / PKL</span>
                <span class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_c', 0), 2) }}</span>
            </td>
            <td>
                <span class="label">Rata-rata Umum</span>
                <span class="value">{{ number_format((float) data_get($summary, 'overall_average', 0), 2) }}</span>
            </td>
        </tr>
    </table>

    <div class="template-html" style="margin-top: 6px;">
        {!! $template['closing_html'] ?? '<p>Transkrip ini diterbitkan untuk dipergunakan sebagaimana mestinya.</p>' !!}
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="qr-block">
                    @if (!empty($qrCode))
                        <img src="{{ $qrCode }}" alt="QR Verifikasi">
                    @endif
                </div>
                <div class="footer-note">Token: {{ $document->verification_token }}</div>
            </td>
            <td>
                <div>{{ $documentMeta['issued_place'] ?? '-' }}, {{ !empty($documentMeta['issued_date']) ? \Carbon\Carbon::parse($documentMeta['issued_date'])->translatedFormat('d F Y') : '-' }}</div>
                <div>Kepala Sekolah</div>
                <div class="sign-space">
                    @if (!empty($documentMeta['show_stamp']) && $stempelPath)
                        <img src="{{ $stempelPath }}" alt="Stempel" class="stamp">
                    @endif
                    @if (!empty($documentMeta['show_signature']) && $ttdKepsekPath)
                        <img src="{{ $ttdKepsekPath }}" alt="TTD Kepala Sekolah" class="signature">
                    @endif
                </div>
                <div><strong>{{ $school['nama_kepsek'] ?? '-' }}</strong></div>
                <div>NIP. {{ $school['nip_kepsek'] ?? '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
