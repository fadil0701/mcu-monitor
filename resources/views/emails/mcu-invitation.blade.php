<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Medical Check Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .info-box {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #475569;
        }
        .value {
            color: #1e293b;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #f1f5f9;
            border-radius: 6px;
            font-size: 14px;
            color: #64748b;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .important {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 Undangan Medical Check Up</h1>
        <p>Sistem Monitoring MCU</p>
    </div>
    
    <div class="content">
        <p>Kepada Yth. <strong>{{ $schedule->nama_lengkap }}</strong>,</p>
        
        <p>Kami mengundang Anda untuk mengikuti Medical Check Up dengan detail sebagai berikut:</p>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">📅 Tanggal Pemeriksaan:</span>
                <span class="value">{{ $schedule->tanggal_pemeriksaan->format('l, d F Y') }}</span>
            </div>
            <div class="info-row">
                <span class="label">🕐 Jam Pemeriksaan:</span>
                <span class="value">{{ $schedule->jam_pemeriksaan->format('H:i') }} WIB</span>
            </div>
            <div class="info-row">
                <span class="label">📍 Lokasi Pemeriksaan:</span>
                <span class="value">{{ $schedule->lokasi_pemeriksaan }}</span>
            </div>
            <div class="info-row">
                <span class="label">🏢 SKPD:</span>
                <span class="value">{{ $schedule->skpd }}</span>
            </div>
        </div>

        <div class="important">
            <strong>⚠️ Penting:</strong>
            <ul>
                <li>Datang 15 menit sebelum jam pemeriksaan</li>
                <li>Bawa identitas diri (KTP/NRK)</li>
                <li>Puasa 8-10 jam sebelum pemeriksaan (jika diperlukan)</li>
                <li>Konfirmasi kehadiran melalui sistem</li>
            </ul>
        </div>

        @if($schedule->queue_number)
        <div class="info-box">
            <div class="info-row">
                <span class="label">🎫 Nomor Antrian:</span>
                <span class="value" style="font-size: 18px; font-weight: bold; color: #2563eb;">{{ $schedule->queue_number }}</span>
            </div>
        </div>
        @endif

        <p>Jika Anda berhalangan hadir, silakan hubungi admin atau gunakan fitur reschedule di sistem.</p>
        
        <p>Terima kasih atas perhatiannya.</p>
        
        <p>Salam,<br>
        <strong>Tim Medical Check Up</strong></p>
    </div>
    
    <div class="footer">
        <p>Email ini dikirim secara otomatis oleh Sistem Monitoring MCU</p>
        <p>Jika Anda tidak seharusnya menerima email ini, silakan abaikan.</p>
    </div>
</body>
</html>
