<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 - Session Expired</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            text-align: center;
            color: #333;
            max-width: 600px;
            padding: 40px;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: #5a67d8;
        }
        
        .error-title {
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 40px;
            color: #666;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: #5a67d8;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background: #4c51bf;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">419</div>
        <h1 class="error-title">Sesi Kedaluwarsa</h1>
        <p class="error-message">
            Sesi Anda telah kedaluwarsa karena tidak aktif terlalu lama.
            Silakan refresh halaman atau coba lagi.
        </p>
        <div class="error-actions">
            <button onclick="location.reload()" class="btn">Refresh Halaman</button>
            <a href="/" class="btn">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>

