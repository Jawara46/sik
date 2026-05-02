<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat UKK - {{ $student->name }}</title>
    <style>
        @page {
            margin: 0px;
            size: 210mm 297mm;
        }
        body, html {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #333;
        }

        /* Front Page Full-Bleed Background */
        .front-page {
            width: 210mm;
            height: 297mm;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            page-break-after: always;
        }

        .front-page-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
        }

        /* Back Page Dimensions */
        .back-page {
            padding: 15mm 20mm;
            background-color: #ffffff;
            position: relative;
            min-height: 267mm;
            box-sizing: border-box;
        }

        .back-page.break-after {
            page-break-after: always;
        }

        /* Certificate Content Container */
        .cert-content {
            position: relative;
            z-index: 1;
            padding-top: 40px;
            padding-left: 16mm;
            padding-right: 16mm;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 5px;
        }
        .logo-container img {
            height: 90px;
        }

        /* Typography */
        h1.cert-title-id {
            font-size: 26px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        h2.cert-title-en {
            font-size: 20px;
            margin: 5px 0 20px 0;
            color: #007bff; /* modern blue */
            font-style: italic;
            font-weight: normal;
            text-transform: uppercase;
        }
        .cert-number {
            font-size: 13px;
            margin-bottom: 25px;
        }

        .declare-text {
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.4;
        }
        .declare-text .en { font-style: italic; font-size: 13px; color: #555; }

        .student-name {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .student-nisn {
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 25px;
        }

        .info-text {
            font-size: 14px;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        .info-text .en { font-style: italic; font-size: 13px; color: #555;}

        .major-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .achievement-level {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .achievement-level-en {
            font-size: 18px;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 25px;
            text-transform: uppercase;
            color: #555;
        }

        .validity {
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 25px;
        }
        .validity .en { font-style: italic; }

        .signatures-table {
            width: 90%;
            margin: 0 auto;
            margin-top: 16px;
            font-size: 13px;
            border-collapse: collapse;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }
        .signatures-table .qr-col {
            width: 20%;
        }
        .signatures-table .sign-col {
            width: 40%;
        }
        .signature-space {
            height: 74px;
        }
        .signature-space img {
            display: block;
            margin: 0 auto;
        }
        .qr-box img {
            display: block;
            margin: 0 auto;
            height: 86px;
        }

        /* Styles for Back Page */
        .back-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .back-info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .units-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .units-table th, .units-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        .units-table th {
            text-align: center;
        }
        
        .text-center { text-align: center; }

    </style>
</head>
<body>

    @php
        $status = $record->ukk_status ?? 'Kompeten';
        $statusEn = match($status) {
            'Sangat Kompeten' => 'Very Competent',
            'Kompeten' => 'Competent',
            'Cukup Kompeten' => 'Sufficiently Competent',
            'Belum Kompeten' => 'Not Competent',
            default => 'Competent'
        };
        $dateText = $documentDate ? $documentDate->locale('id')->translatedFormat('d F Y') : now()->locale('id')->translatedFormat('d F Y');
    @endphp

    <!-- FRONT PAGE -->
    <div class="front-page">
        @if($bgSertiPath)
            <img src="{{ $bgSertiPath }}" alt="" class="front-page-bg">
        @endif
        <div class="cert-content">
            
            <div class="logo-container">
                @if($tutWuriPath)
                <img src="{{ $tutWuriPath }}" alt="Logo Tut Wuri Handayani">
                @endif
            </div>

            <h1 class="cert-title-id">SERTIFIKAT KOMPETENSI</h1>
            <h2 class="cert-title-en">COMPETENCY CERTIFICATE</h2>

            <div class="cert-number">
                Nomor : {{ $record->certificate_number }}
            </div>

            <div class="declare-text">
                Dengan ini menyatakan bahwa,<br>
                <div class="en">Hereby declare that</div>
            </div>

            <div class="student-name">{{ $student->name }}</div>
            <div class="student-nisn">NISN: {{ $student->nisn }}</div>

            <div class="info-text">
                Telah mengikuti Uji Kompetensi Keahlian<br>
                <div class="en">Has taken the skills competency test</div>
            </div>

            <div class="info-text" style="margin-top:20px;">
                pada Kompetensi /Konsentrasi Keahlian<br>
                <div class="en">on Competency of</div>
            </div>

            <div class="major-name">{{ strtoupper($major->name ?? '-') }}</div>

            <div class="info-text">
                pada Judul Penugasan<br>
                <div class="en">on Assignment</div>
            </div>

            <div class="assignment-title">Ujian Praktik Kejuruan</div>

            <div class="info-text">
                dengan predikat<br>
                <div class="en">with achievement level</div>
            </div>

            <div class="achievement-level">{{ strtoupper($status) }}</div>
            <div class="achievement-level-en">{{ strtoupper($statusEn) }}</div>

            <div class="validity">
                Sertifikat ini berlaku untuk : 3 (tiga) Tahun<br>
                <div class="en">This certificate is valid for : 3 (three) Years</div>
            </div>

            <table class="signatures-table">
                <tr>
                    <td class="sign-col">
                        Atas nama {{ $school->name ?? 'Sekolah' }}<br>
                        <span style="font-style:italic">On behalf of {{ $school->name ?? 'Sekolah' }}</span>
                    </td>
                    <td class="qr-col"></td>
                    <td class="sign-col">
                        {{ $documentPlace }}, {{ $dateText }}
                    </td>
                </tr>
                <tr>
                    <td class="sign-col">
                        <div class="signature-space">
                            @if($stempelPath)
                                <img src="{{ $stempelPath }}" style="height: 72px; opacity: 0.55; margin-bottom: -54px;" alt="">
                            @endif
                            @if($ttdKepsekPath)
                                <img src="{{ $ttdKepsekPath }}" style="height: 54px;" alt="">
                            @endif
                        </div>
                    </td>
                    <td class="qr-col">
                        <div class="qr-box">
                            <img src="{{ $qrCode }}" alt="QR Verifikasi">
                        </div>
                    </td>
                    <td class="sign-col">
                        <div class="signature-space">
                            @if($ttdPengujiPath)
                                <img src="{{ $ttdPengujiPath }}" style="height: 45px; margin-top: 14px;" alt="">
                            @elseif($assessor && $assessor->external_company)
                                <div style="margin-top: 20px;">{{ strtoupper($assessor->external_company) }}</div>
                            @else
                                <div style="margin-top: 20px;">TUK / INDUSTRI MITRA</div>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="sign-col">
                        <strong>{{ strtoupper($school->nama_kepsek ?? 'Nama Kepala Sekolah') }}</strong><br>
                        Kepala Sekolah<br>
                        <span style="font-style:italic">School Principal</span><br>
                        @if($school->nip_kepsek)
                            NIP. {{ $school->nip_kepsek }}
                        @endif
                    </td>
                    <td class="qr-col">
                        &nbsp;
                    </td>
                    <td class="sign-col">
                        <strong>{{ strtoupper($assessor->external_name ?? 'Nama Penguji Eksternal') }}</strong><br>
                        Penguji Eksternal<br>
                        <span style="font-style:italic">External Assessor</span><br>
                        @if($assessor && $assessor->external_position)
                            {{ $assessor->external_position }}
                        @else
                            <br>
                        @endif
                    </td>
                </tr>
            </table>

        </div>
    </div>

    <!-- PAGE 2: DAFTAR UNIT KOMPETENSI -->
    <div class="back-page">
        <div class="back-title" style="margin-top: 20px;">
            DAFTAR UNIT KOMPETENSI<br>
            <span style="font-size:16px; font-weight:normal; font-style:italic; text-transform:none;">List of Competency Unit</span>
        </div>
        
        <table class="units-table" style="margin-top:30px;">
            <tr style="background-color: #e2e8f0;">
                <th width="30%">Kode Unit<br><span style="font-style:italic; font-weight:normal;">Unit Code</span></th>
                <th width="70%">Judul Unit<br><span style="font-style:italic; font-weight:normal;">Unit Title</span></th>
            </tr>
            @forelse($record->units as $index => $ru)
            <tr>
                    <td>{{ $ru->smkUnit->kode_unit ?? '-' }}</td>
                    <td>{{ $ru->smkUnit->judul_unit ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="text-center">Belum ada rincian unit kompetensi yang diujikan.</td>
                </tr>
                @endforelse
        </table>

        <table style="width: 100%; margin-top:50px; font-size:13px;">
            <tr>
                <td width="30%" align="left" style="vertical-align:bottom;">
                    Penguji Internal<br>
                    <span style="font-style:italic;">Internal Assessor</span>
                </td>
                <td width="70%" align="right" style="vertical-align:bottom;">
                    {{ strtoupper($assessor->internal_name ?? 'NAMA PENGUJI INTERNAL') }}
                </td>
            </tr>
            <tr><td colspan="2" style="height:50px;"></td></tr>
            <tr>
                <td width="30%" align="left" style="vertical-align:bottom;">
                    Penguji Eksternal<br>
                    <span style="font-style:italic;">External Assessor</span>
                </td>
                <td width="70%" align="right" style="vertical-align:bottom;">
                    {{ strtoupper($assessor->external_name ?? 'NAMA PENGUJI EKSTERNAL') }} {{ $assessor && $assessor->external_company ? '('.$assessor->external_company.')' : '' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
