<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SKL - {{ $student['name'] ?? 'Siswa' }}</title>
    <style>
        @page {
            margin: 18mm 16mm 18mm 16mm;
            size: 210mm 297mm;
        }
        body, html {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #111827;
        }
        .letterhead {
            margin-bottom: 18px;
        }
        .letterhead img {
            width: 100%;
            max-height: 118px;
            display: block;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px double #111827;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-col {
            width: 88px;
            text-align: center;
        }
        .logo-col img {
            max-width: 74px;
            max-height: 74px;
        }
        .header-center {
            text-align: center;
        }
        .school-title {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .school-subtitle {
            font-size: 13px;
            margin-top: 3px;
        }
        .title {
            text-align: center;
            margin: 22px 0 18px;
            line-height: 1.6;
        }
        .title strong {
            font-size: 18px;
            text-transform: uppercase;
        }
        .doc-number {
            text-align: center;
            margin-bottom: 18px;
        }
        .content {
            line-height: 1.75;
            text-align: justify;
        }
        .identity-table {
            width: 100%;
            margin: 14px 0 18px;
            border-collapse: collapse;
        }
        .identity-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .photo-box {
            width: 120px;
            text-align: center;
            float: right;
            margin-left: 20px;
            margin-bottom: 12px;
        }
        .photo-box img {
            width: 110px;
            height: 145px;
            object-fit: cover;
            border: 1px solid #cfd5df;
            padding: 4px;
            background: #fff;
        }
        .signature-table {
            width: 100%;
            margin-top: 28px;
        }
        .signature-table td {
            vertical-align: top;
        }
        .signature-box {
            min-height: 92px;
            position: relative;
        }
        .signature-box img {
            display: block;
            margin-left: auto;
        }
        .qr-box img {
            height: 90px;
            display: block;
            margin: 0 auto;
        }
        .template-html p {
            margin: 0 0 12px;
            line-height: 1.75;
        }
        .template-html .ql-align-center {
            text-align: center;
        }
        .template-html .ql-align-right {
            text-align: right;
        }
        .template-html .ql-align-justify {
            text-align: justify;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 12px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #111827;
            padding: 3px 8px;
            vertical-align: middle;
        }
        .grades-table th {
            background-color: #f8fafc;
            text-align: center;
            font-weight: bold;
        }
        .grades-table .category-header {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .grades-table .number-col {
            width: 30px;
            text-align: center;
        }
        .grades-table .score-col {
            width: 80px;
            text-align: center;
        }
        .grades-table .avg-row {
            font-weight: bold;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    @php
        $issuedDate = \Illuminate\Support\Carbon::parse($documentMeta['issued_date'] ?? now())->locale('id')->translatedFormat('d F Y');
        $template = (array) data_get($documentMeta, 'template', data_get($document, 'snapshot_payload.template', []));
    @endphp

    @if($documentMeta['use_letterhead'] ?? false)
        @if($letterheadPath)
            <div class="letterhead">
                <img src="{{ $letterheadPath }}" alt="Kop Surat Sekolah">
            </div>
        @endif
    @else
        <table class="header-table">
            <tr>
                <td class="logo-col">
                    @if($schoolLogoPath)
                        <img src="{{ $schoolLogoPath }}" alt="Logo Sekolah">
                    @endif
                </td>
                <td class="header-center">
                    <h1 class="school-title">{{ strtoupper($school['nama_sekolah'] ?? 'SEKOLAH') }}</h1>
                    <div class="school-subtitle">{{ $school['alamat_sekolah'] ?? 'Alamat sekolah belum diatur' }}</div>
                    <div class="school-subtitle">
                        {{ $school['telepon_sekolah'] ?? '-' }}
                        @if(!empty($school['email_sekolah']))
                            | {{ $school['email_sekolah'] }}
                        @endif
                    </div>
                </td>
                <td class="logo-col">
                    @if($tutWuriPath)
                        <img src="{{ $tutWuriPath }}" alt="Tut Wuri">
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <div class="title template-html">
        <div style="margin-bottom: 2px;">{!! $template['title_html'] ?? '<strong>SURAT KETERANGAN LULUS</strong>' !!}</div>
        <div>Nomor: {{ $document->document_number ?? 'DRAFT-' . $document->id }}</div>
    </div>

    @if(($documentMeta['show_photo'] ?? false) && $studentPhotoPath)
        <div class="photo-box">
            <img src="{{ $studentPhotoPath }}" alt="Pas Foto Siswa">
        </div>
    @endif

    <div class="content template-html">
        {!! $template['intro_html'] ?? ('<p>Kepala ' . e($school['nama_sekolah'] ?? 'sekolah') . ' menerangkan bahwa:</p>') !!}
    </div>

    <table class="identity-table">
        <tr>
            <td width="120">Nama</td>
            <td width="12">:</td>
            <td><strong>{{ $student['name'] ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td>NISN</td>
            <td>:</td>
            <td>{{ $student['nisn'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tempat, Tgl Lahir</td>
            <td>:</td>
            <td>
                {{ $student['tempat_lahir'] ?? '-' }},
                {{ !empty($student['tanggal_lahir']) ? \Illuminate\Support\Carbon::parse($student['tanggal_lahir'])->locale('id')->translatedFormat('d F Y') : '-' }}
            </td>
        </tr>
        <tr>
            <td>Orang Tua/Wali</td>
            <td>:</td>
            <td>{{ $student['nama_orang_tua'] ?? '-' }}</td>
        </tr>
        @if(!empty($student['major_name']))
            <tr>
                <td>Program/Jurusan</td>
                <td>:</td>
                <td>{{ $student['major_name'] }}</td>
            </tr>
        @endif
    </table>

    <div class="content template-html">
        {!! $template['body_html'] ?? ('<p>Berdasarkan hasil rapat dewan guru dan ketentuan akademik yang berlaku, peserta didik tersebut di atas dinyatakan <strong>' . e(strtoupper($documentMeta['graduation_status'] ?? 'LULUS')) . '</strong> dari ' . e($school['nama_sekolah'] ?? 'sekolah ini') . ' pada tahun pelajaran berjalan.</p>') !!}
    </div>

    @if($documentMeta['show_grades'] ?? false)
        <div style="margin-top: 10px; font-weight: bold;">Dengan rincian nilai sebagai berikut:</div>
        <table class="grades-table">
            <thead>
                <tr>
                    <th class="number-col">No</th>
                    <th>Mata Pelajaran</th>
                    <th class="score-col">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $kelompokA = collect($subjects)->whereIn('category', ['Umum', 'Muatan Nasional', 'Kewilayahan']);
                    $kelompokB = collect($subjects)->whereIn('category', ['C1', 'C2', 'C3', 'UKK', 'PKL']);
                    $no = 1;
                @endphp

                @if($kelompokA->isNotEmpty())
                    <tr class="category-header">
                        <td>A.</td>
                        <td colspan="2">Kelompok Mata Pelajaran Umum</td>
                    </tr>
                    @foreach($kelompokA as $subject)
                        <tr>
                            <td class="number-col">{{ $no++ }}</td>
                            <td>{{ $subject['subject_name'] }}</td>
                            <td class="score-col">{{ number_format((float) ($subject['final_score'] ?? $subject['score'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                @endif

                @if($kelompokB->isNotEmpty())
                    <tr class="category-header">
                        <td>B.</td>
                        <td colspan="2">Kelompok Mata Pelajaran Kejuruan</td>
                    </tr>
                    @foreach($kelompokB as $subject)
                        <tr>
                            <td class="number-col">{{ $no++ }}</td>
                            <td>{{ $subject['subject_name'] }}</td>
                            <td class="score-col">{{ number_format((float) ($subject['final_score'] ?? $subject['score'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                @endif

                <tr class="avg-row">
                    <td colspan="2" style="text-align: center;">Rata-rata</td>
                    <td class="score-col">{{ number_format((float) ($summary['overall_average'] ?? 0), 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="content template-html" style="margin-top: 14px;">
        {!! $template['closing_html'] ?? '<p>Surat keterangan ini dipergunakan sebagaimana mestinya sambil menunggu dokumen resmi lainnya sesuai ketentuan yang berlaku.</p>' !!}
    </div>

    <table class="signature-table">
        <tr>
            <td width="55%"></td>
            <td width="20%" class="qr-box">
                <img src="{{ $qrCode }}" alt="QR Verifikasi">
            </td>
            <td width="25%" style="text-align: center;">
                {{ $documentMeta['issued_place'] ?? 'Kabupaten' }}, {{ $issuedDate }}<br>
                Kepala Sekolah
                <div class="signature-box" style="margin-top: 8px;">
                    @if(($documentMeta['show_stamp'] ?? false) && $stempelPath)
                        <img src="{{ $stempelPath }}" style="height: 80px; opacity: 0.55; margin-bottom: -58px; margin-right: 38px;" alt="">
                    @endif
                    @if(($documentMeta['show_signature'] ?? false) && $ttdKepsekPath)
                        <img src="{{ $ttdKepsekPath }}" style="height: 58px; margin-right: 0;" alt="">
                    @endif
                </div>
                <strong>{{ strtoupper($school['nama_kepsek'] ?? 'KEPALA SEKOLAH') }}</strong><br>
                @if(!empty($school['nip_kepsek']))
                    NIP. {{ $school['nip_kepsek'] }}
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
