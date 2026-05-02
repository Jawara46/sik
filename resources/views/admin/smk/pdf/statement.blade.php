<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan UKK - {{ $student->name }}</title>
    <style>
        @page {
            margin: 22mm 18mm 20mm 18mm;
            size: 210mm 297mm;
        }
        body, html {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #111827;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px double #111827;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo {
            width: 92px;
            text-align: center;
        }
        .header-logo img {
            max-width: 78px;
            max-height: 78px;
            display: inline-block;
        }
        .letterhead-wrap {
            margin-bottom: 24px;
        }
        .letterhead-wrap img {
            width: 100%;
            max-height: 115px;
            display: block;
        }
        .header-center {
            text-align: center;
            padding: 0 8px;
        }
        .school-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .school-subtitle {
            font-size: 13px;
            margin-top: 3px;
        }
        .doc-title {
            text-align: center;
            margin: 26px 0 24px;
            line-height: 1.5;
        }
        .doc-title strong {
            font-size: 18px;
            text-transform: uppercase;
        }
        .identity-table,
        .units-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .identity-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .units-table {
            margin-top: 18px;
        }
        .units-table th,
        .units-table td {
            border: 1px solid #111827;
            padding: 6px 8px;
        }
        .units-table th {
            background: #eef2f7;
            text-align: center;
        }
        .content-text {
            line-height: 1.7;
            text-align: justify;
        }
        .signature-table {
            margin-top: 28px;
        }
        .signature-table td {
            vertical-align: top;
        }
        .signature-box {
            min-height: 96px;
            position: relative;
        }
        .signature-box img {
            display: block;
            margin-left: auto;
        }
        .template-html p {
            margin: 0 0 12px;
            line-height: 1.7;
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
    </style>
</head>
<body>
    @php
        $dateText = $documentDate ? $documentDate->locale('id')->translatedFormat('d F Y') : now()->locale('id')->translatedFormat('d F Y');
        $templateSections = $templateSections ?? null;
    @endphp

    @if($letterheadPath)
        <div class="letterhead-wrap">
            <img src="{{ $letterheadPath }}" alt="Kop Surat Sekolah">
        </div>
    @else
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if($schoolLogoPath)
                        <img src="{{ $schoolLogoPath }}" alt="Logo Sekolah">
                    @endif
                </td>
                <td class="header-center">
                    <div class="school-title">{{ strtoupper($school->nama_sekolah ?? $school->name) }}</div>
                    <div class="school-subtitle">{{ $school->alamat_sekolah ?? 'Alamat sekolah belum diatur' }}</div>
                    @if($school->email_sekolah || $school->web_sekolah)
                        <div class="school-subtitle">
                            {{ $school->email_sekolah ?: '-' }}
                            @if($school->web_sekolah)
                                | {{ $school->web_sekolah }}
                            @endif
                        </div>
                    @endif
                    @if($school->telepon_sekolah)
                        <div class="school-subtitle">Telp. {{ $school->telepon_sekolah }}</div>
                    @endif
                </td>
                <td class="header-logo">
                    @if($tutWuriPath)
                        <img src="{{ $tutWuriPath }}" alt="Logo Tut Wuri Handayani">
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <div class="doc-title template-html">
        {!! $templateSections['title_html'] ?? '<p class="ql-align-center"><strong>SURAT KETERANGAN</strong><br><strong>TELAH MENGIKUTI UJI KOMPETENSI KEAHLIAN</strong></p>' !!}
    </div>

    <div class="content-text template-html">
        {!! $templateSections['intro_html'] ?? ('<p>Kepala ' . e($school->nama_sekolah ?? $school->name) . ' menerangkan bahwa:</p>') !!}
    </div>

    <table class="identity-table" style="margin-top: 12px;">
        <tr>
            <td width="110">Nama</td>
            <td width="10">:</td>
            <td><strong>{{ $student->name }}</strong></td>
        </tr>
        <tr>
            <td>NISN</td>
            <td>:</td>
            <td>{{ $student->nisn }}</td>
        </tr>
        <tr>
            <td>Kompetensi</td>
            <td>:</td>
            <td>{{ $major->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="content-text template-html" style="margin-top: 16px;">
        {!! $templateSections['body_html'] ?? ('<p>Yang bersangkutan telah mengikuti Uji Kompetensi Keahlian pada paket keahlian <strong>' . e(strtolower($major->name ?? '-')) . '</strong> dengan rincian unit kompetensi sebagai berikut:</p>') !!}
    </div>

    <table class="units-table">
        <tr>
            <th width="24%">Kode Unit</th>
            <th width="56%">Judul Unit</th>
            <th width="20%">Keterangan</th>
        </tr>
        @forelse($record->units as $ru)
            <tr>
                <td>{{ $ru->smkUnit->kode_unit ?? '-' }}</td>
                <td>{{ $ru->smkUnit->judul_unit ?? '-' }}</td>
                <td style="text-align: center;">{{ $ru->score ?: 'Kompeten' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" style="text-align: center;">Belum ada rincian unit kompetensi yang diujikan.</td>
            </tr>
        @endforelse
    </table>

    <div class="content-text template-html" style="margin-top: 18px;">
        {!! $templateSections['closing_html'] ?? '<p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>' !!}
    </div>

    <table class="signature-table">
        <tr>
            <td width="55%"></td>
            <td width="45%" style="text-align: center;">
                {{ $documentPlace }}, {{ $dateText }}<br>
                Kepala Sekolah
                <div class="signature-box" style="margin-top: 8px;">
                    @if($stempelPath)
                        <img src="{{ $stempelPath }}" style="height: 80px; opacity: 0.55; margin-bottom: -58px; margin-right: 38px;" alt="">
                    @endif
                    @if($ttdKepsekPath)
                        <img src="{{ $ttdKepsekPath }}" style="height: 58px; margin-right: 0;" alt="">
                    @endif
                </div>
                <strong>{{ strtoupper($school->nama_kepsek ?? '_______________________') }}</strong><br>
                @if($school->nip_kepsek)
                    NIP. {{ $school->nip_kepsek }}
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
