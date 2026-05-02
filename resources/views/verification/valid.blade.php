<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Terverifikasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .verification-box {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon-check {
            font-size: 80px;
            color: #198754;
            margin-bottom: 15px;
        }
        .data-table th { width: 40%; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-box border-top border-4 border-success">
            <div class="icon-check">✅</div>
            <h2 class="text-success fw-bold">TERVERIFIKASI</h2>
            <p class="text-muted mt-2">Dokumen ini diterbitkan secara sah oleh:<br><strong>{{ $school->nama_sekolah ?? $school->name }}</strong></p>
            
            <div class="mt-4 text-start">
                <table class="table table-bordered data-table">
                    <tbody>
                        <tr>
                            <th>Nomor Sertifikat</th>
                            <td class="fw-bold">{{ $record->certificate_number }}</td>
                        </tr>
                        <tr>
                            <th>Nama Lengkap</th>
                            <td>{{ $student->name }}</td>
                        </tr>
                        <tr>
                            <th>NISN</th>
                            <td>{{ $student->nisn }}</td>
                        </tr>
                        <tr>
                            <th>Kompetensi Keahlian</th>
                            <td>{{ $major->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Predikat UKK</th>
                            <td class="fw-bold text-success">{{ strtoupper($record->ukk_status ?? 'KOMPETEN') }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Terbit</th>
                            <td>{{ $record->exam_date ? $record->exam_date->translatedFormat('d F Y') : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-success mt-4">
                <small>Sertifikat ini adalah dokumen resmi dan tercatat di pangkalan data kelulusan.</small>
            </div>
        </div>
    </div>
</body>
</html>
