<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - {{ $school['nama_sekolah'] ?? 'Sekolah' }}</title>
    <style>
        :root {
            --page-bg: #eff3fb;
            --card-bg: #ffffff;
            --border: rgba(15, 23, 42, 0.10);
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --success-bg: rgba(22, 163, 74, 0.12);
            --success-text: #166534;
            --primary-bg: #eef2ff;
            --primary-text: #3730a3;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 30%),
                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.10), transparent 28%),
                var(--page-bg);
            color: var(--text-main);
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 40px 18px 56px;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .hero {
            padding: 30px 30px 22px;
            border-bottom: 1px solid var(--border);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--success-bg);
            color: var(--success-text);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .hero h1 {
            margin: 14px 0 10px;
            font-size: 30px;
            line-height: 1.15;
        }
        .hero p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 680px;
        }
        .letterhead {
            width: 100%;
            max-height: 120px;
            object-fit: contain;
            display: block;
            margin-bottom: 20px;
        }
        .school-head {
            display: grid;
            grid-template-columns: 88px 1fr;
            gap: 18px;
            align-items: center;
            margin-bottom: 18px;
        }
        .school-logo {
            width: 88px;
            height: 88px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: #fff;
            object-fit: contain;
            padding: 10px;
        }
        .school-meta h2 {
            margin: 0 0 4px;
            font-size: 22px;
        }
        .school-meta p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .content {
            padding: 28px 30px 32px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .panel {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            background: #fff;
        }
        .panel h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }
        .kv {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px 14px;
        }
        .kv div {
            padding: 6px 0;
            border-bottom: 1px dashed rgba(15, 23, 42, 0.08);
        }
        .kv div:nth-last-child(-n+2) {
            border-bottom: 0;
        }
        .label { color: var(--text-muted); }
        .value { font-weight: 600; }
        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .chip {
            background: var(--primary-bg);
            color: var(--primary-text);
            border-radius: 999px;
            padding: 9px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .alert {
            border-radius: 16px;
            padding: 16px 18px;
            background: rgba(22, 163, 74, 0.08);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.12);
            line-height: 1.6;
        }
        .footer {
            margin-top: 18px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }
        .code {
            display: inline-block;
            padding: 6px 10px;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        a.verify-link {
            color: #1d4ed8;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .hero, .content { padding: 22px 18px; }
            .grid { grid-template-columns: 1fr; }
            .kv { grid-template-columns: 1fr; }
            .school-head { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="hero">
                @if ($letterheadUrl)
                    <img src="{{ $letterheadUrl }}" alt="Kop Surat Sekolah" class="letterhead">
                @else
                    <div class="school-head">
                        @if ($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="Logo Sekolah" class="school-logo">
                        @endif
                        <div class="school-meta">
                            <h2>{{ $school['nama_sekolah'] ?? 'Sekolah' }}</h2>
                            <p>
                                NPSN {{ $school['npsn'] ?? '-' }}<br>
                                {{ $school['alamat_sekolah'] ?? '-' }}
                            </p>
                        </div>
                    </div>
                @endif

                <span class="status-pill">Dokumen Valid</span>
                <h1>Hasil Verifikasi Dokumen Resmi</h1>
                <p>Dokumen ini berhasil diverifikasi dan tercatat sebagai dokumen resmi yang telah dipublish oleh sekolah. Informasi di bawah ini diambil langsung dari snapshot dokumen yang tersimpan pada sistem.</p>
            </div>

            <div class="content">
                <div class="meta-row">
                    <span class="chip">{{ strtoupper($document->document_type) }}</span>
                    <span class="chip">Status: Published</span>
                    @if($document->published_at)
                        <span class="chip">Publish: {{ $document->published_at->locale('id')->translatedFormat('d M Y H:i') }}</span>
                    @endif
                </div>

                <div class="grid">
                    <div class="panel">
                        <h3>Identitas Dokumen</h3>
                        <div class="kv">
                            <div class="label">Jenis Dokumen</div>
                            <div class="value text-uppercase">{{ $document->document_type }}</div>

                            <div class="label">Nomor Dokumen</div>
                            <div class="value">{{ $document->document_number ?? 'Belum bernomor' }}</div>

                            <div class="label">Tanggal Terbit</div>
                            <div class="value">
                                {{ $document->issued_at?->locale('id')->translatedFormat('d F Y') ?? (!empty($documentMeta['issued_date']) ? \Illuminate\Support\Carbon::parse($documentMeta['issued_date'])->locale('id')->translatedFormat('d F Y') : '-') }}
                            </div>

                            <div class="label">Tempat Terbit</div>
                            <div class="value">{{ $documentMeta['issued_place'] ?? '-' }}</div>

                            <div class="label">Token Verifikasi</div>
                            <div class="value"><span class="code">{{ $document->verification_token }}</span></div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Identitas Pemilik Dokumen</h3>
                        <div class="kv">
                            <div class="label">Nama Siswa</div>
                            <div class="value">{{ $student['name'] ?? '-' }}</div>

                            <div class="label">NISN</div>
                            <div class="value">{{ $student['nisn'] ?? '-' }}</div>

                            @if(!empty($student['major_name']))
                                <div class="label">Jurusan</div>
                                <div class="value">{{ $student['major_name'] }}</div>
                            @endif

                            @if(!empty($student['tempat_lahir']) || !empty($student['tanggal_lahir']))
                                <div class="label">Tempat / Tanggal Lahir</div>
                                <div class="value">
                                    {{ $student['tempat_lahir'] ?? '-' }}
                                    @if(!empty($student['tanggal_lahir']))
                                        , {{ \Illuminate\Support\Carbon::parse($student['tanggal_lahir'])->locale('id')->translatedFormat('d F Y') }}
                                    @endif
                                </div>
                            @endif

                            <div class="label">Sekolah</div>
                            <div class="value">{{ $school['nama_sekolah'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                @if($document->document_type === 'transcript')
                    <div class="panel" style="margin-bottom:20px;">
                        <h3>Ringkasan Transkrip</h3>
                        <div class="kv">
                            <div class="label">Rata-rata Umum</div>
                            <div class="value">{{ number_format((float) data_get($summary, 'overall_average', 0), 2) }}</div>

                            <div class="label">Rata-rata Kelompok A</div>
                            <div class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_a', 0), 2) }}</div>

                            <div class="label">Rata-rata Kelompok B</div>
                            <div class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_b', 0), 2) }}</div>

                            <div class="label">Rata-rata Kelompok C / PKL</div>
                            <div class="value">{{ number_format((float) data_get($summary, 'category_averages.kelompok_c', 0), 2) }}</div>
                        </div>
                    </div>
                @endif

                <div class="alert">
                    Dokumen ini valid dan diterbitkan secara resmi oleh sekolah. Jika salinan cetak yang Anda terima berbeda dengan data pada halaman verifikasi ini, gunakan hasil verifikasi ini sebagai acuan utama.
                </div>

                <div class="footer">
                    URL verifikasi:
                    <a href="{{ $verificationUrl }}" class="verify-link">{{ $verificationUrl }}</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
