<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Approval Tidak Valid</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            color: #dc2626;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 15px;
        }

        .error-message {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .error-reasons {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .error-reasons h3 {
            font-size: 16px;
            color: #991b1b;
            margin-bottom: 12px;
        }

        .error-reasons ul {
            list-style: none;
            padding: 0;
        }

        .error-reasons li {
            font-size: 14px;
            color: #7f1d1d;
            padding: 6px 0;
            padding-left: 24px;
            position: relative;
        }

        .error-reasons li:before {
            content: "•";
            position: absolute;
            left: 8px;
            font-weight: bold;
        }

        .action-box {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .action-box h3 {
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 12px;
        }

        .action-box p {
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .help-text {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 20px;
        }

        @media (max-width: 640px) {
            .error-container {
                padding: 30px 20px;
            }

            .error-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
            </path>
        </svg>

        <h1 class="error-title">Link Approval Tidak Valid</h1>

        <p class="error-message">
            Maaf, link approval yang Anda gunakan tidak valid atau sudah kedaluwarsa.
        </p>

        <div class="error-reasons">
            <h3>Kemungkinan Penyebab:</h3>
            <ul>
                <li>Link sudah melewati batas waktu kedaluwarsa (7 hari)</li>
                <li>Link sudah pernah digunakan untuk melakukan approval</li>
                <li>Link tidak lengkap atau rusak</li>
                <li>Permintaan sudah dibatalkan atau diubah</li>
            </ul>
        </div>

        <div class="action-box">
            <h3>Apa yang Harus Dilakukan?</h3>
            <p>
                Silakan hubungi pemohon atau tim Finance untuk mendapatkan link approval yang baru.
                Atau Anda dapat login ke sistem untuk melakukan approval secara langsung.
            </p>
        </div>

        <div class="btn-container">
            @if (config('app.url'))
                <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                    Login ke Dashboard
                </a>
            @endif

            <a href="mailto:{{ config('mail.from.address', 'finance@company.com') }}" class="btn btn-secondary">
                Hubungi Tim Finance
            </a>
        </div>

        <p class="help-text">
            Butuh bantuan? Hubungi tim IT atau Finance di kantor Anda.
        </p>
    </div>
</body>

</html>
