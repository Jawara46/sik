<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Tidak Valid</title>
    <style>
        :root {
            --page-bg: #f7f8fc;
            --card-bg: #ffffff;
            --danger-bg: rgba(220, 38, 38, 0.10);
            --danger-text: #991b1b;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: rgba(15, 23, 42, 0.10);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(220, 38, 38, 0.08), transparent 28%),
                var(--page-bg);
            color: var(--text-main);
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
            padding: 34px 30px;
            text-align: center;
        }
        .icon {
            width: 84px;
            height: 84px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--danger-bg);
            color: var(--danger-text);
            font-size: 44px;
            font-weight: 700;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }
        p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.7;
        }
        .token-box {
            margin: 24px 0;
            padding: 14px 16px;
            border-radius: 16px;
            background: #0f172a;
            color: #e2e8f0;
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .note {
            margin-top: 24px;
            padding: 16px 18px;
            border-radius: 16px;
            background: var(--danger-bg);
            color: var(--danger-text);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="icon">!</div>
            <h1>Dokumen Tidak Valid</h1>
            <p>Token verifikasi ini tidak ditemukan, belum dipublish, atau dokumennya sudah dicabut dari sistem sekolah.</p>

            <div class="token-box">{{ $reference }}</div>

            <div class="note">
                Pastikan Anda membuka tautan resmi atau memindai QR code asli yang tercetak pada dokumen.
            </div>
        </div>
    </div>
</body>
</html>
