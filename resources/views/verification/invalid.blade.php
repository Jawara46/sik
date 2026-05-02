<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .verification-box {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon-cross {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-box border-top border-4 border-danger">
            <div class="icon-cross">🚫</div>
            <h2 class="text-danger fw-bold">TIDAK VALID</h2>
            <p class="text-muted mt-3">Nomor Sertifikat Spesifik:</p>
            <h5 class="fw-bold">{{ $nomor }}</h5>
            <p class="mt-4 text-secondary">
                Sertifikat dengan nomor di atas <strong>TIDAK DITEMUKAN</strong> di dalam pangkalan data resmi sekolah. Kami tidak menjamin keaslian dokumen ini.
            </p>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary mt-3">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
