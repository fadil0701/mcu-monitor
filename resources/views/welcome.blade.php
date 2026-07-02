<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Monitoring MCU - PPKP DKI Jakarta</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/icon-ppkp.png') }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-hero {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
        }
        
        .btn-primary-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .btn-outline-hero {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline-hero:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }
        
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            margin-bottom: 30px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 2rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
        }
        
        .stats-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .stat-item {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cta-section {
            padding: 80px 0;
            background: #333;
            color: white;
        }
        
        .footer {
            background: #222;
            color: white;
            padding: 40px 0;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 500;
            margin: 0 10px;
        }

        .navbar-brand-logo {
            display: block;
            max-height: 40px;
            width: auto;
            object-fit: contain;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Ensure Daftar MCU button is always visible */
        .navbar-nav .nav-item:last-child .nav-link {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Make sure button is visible on mobile */
        @media (max-width: 991.98px) {
            .navbar-nav .nav-item:last-child .nav-link {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                margin-top: 10px;
            }
        }
        
        .hero-section::before {
            pointer-events: none;
        }
            </style>
    </head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('assets/img/logo-ppkp.png') }}" alt="PPKP DKI Jakarta" class="navbar-brand-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3" href="{{ route('login') }}" style="background-color: #007bff !important; border-color: #007bff !important; color: white !important; text-decoration: none !important;">Login</a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3" href="{{ route('register') }}" style="background-color: #007bff !important; border-color: #007bff !important; color: white !important; text-decoration: none !important;">Daftar MCU</a>
                    </li> --}}
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="logo-section">
                        <div class="logo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="logo-placeholder">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                    <h1 class="hero-title">Sistem Monitoring MCU</h1>
                    <p class="hero-subtitle">Platform terpadu untuk monitoring dan penjadwalan Medical Check Up ASN DKI Jakarta</p>
                    <div class="hero-buttons">
                        <a href="{{ route('register') }}" class="btn-hero btn-primary-hero" style="text-decoration: none !important;">
                            <i class="fas fa-user-plus me-2"></i>Daftar MCU Sekarang
                        </a>
                        <a href="{{ route('peserta.aktivasi') }}" class="btn-hero btn-outline-hero" style="text-decoration: none !important;">
                            <i class="fas fa-user-check me-2"></i>Verifikasi Akun
                        </a>
                        {{-- <a href="{{ route('login') }}" class="btn-hero btn-outline-hero" style="text-decoration: none !important;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a> --}}
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-laptop-medical" style="font-size: 300px; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-4 fw-bold mb-3">Fitur Unggulan</h2>
                    <p class="lead text-muted">Sistem monitoring MCU yang lengkap dan terintegrasi</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="feature-title">Penjadwalan Otomatis</h3>
                        <p class="feature-description">Sistem penjadwalan MCU yang terintegrasi dan otomatis dengan validasi 3 tahun</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Notifikasi Real-time</h3>
                        <p class="feature-description">Pengingat MCU melalui email dan WhatsApp dengan template yang dapat disesuaikan</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Dashboard Monitoring</h3>
                        <p class="feature-description">Dashboard real-time untuk monitoring kesehatan ASN DKI Jakarta</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <h3 class="feature-title">Manajemen Hasil</h3>
                        <p class="feature-description">Upload dan download hasil MCU dengan tracking status download</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Role-based Access</h3>
                        <p class="feature-description">Sistem akses berbasis role untuk Super Admin, Admin, dan User</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Laporan Terpadu</h3>
                        <p class="feature-description">Laporan komprehensif dengan export ke Excel dan PDF</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-4 fw-bold mb-3">Statistik Sistem</h2>
                    <p class="lead opacity-75">Monitoring kesehatan ASN DKI Jakarta</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3">
                    <div class="stat-item">
                        <div class="stat-number">{{ \App\Models\Participant::count() }}+</div>
                        <div class="stat-label">Peserta Terdaftar</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="stat-item">
                        <div class="stat-number">{{ \App\Models\Schedule::count() }}+</div>
                        <div class="stat-label">Jadwal MCU</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="stat-item">
                        <div class="stat-number">{{ \App\Models\McuResult::count() }}+</div>
                        <div class="stat-label">Hasil MCU</div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Keamanan Data</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-4 fw-bold mb-4">Siap untuk Mendaftar MCU?</h2>
            <p class="lead mb-5">Bergabunglah dengan sistem monitoring MCU terpadu untuk ASN DKI Jakarta</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-success btn-lg px-5 py-3" style="text-decoration: none !important;">
                    <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-5 py-3" style="text-decoration: none !important;">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row  ">
                <div class="col-lg-6">
                    <h3 class="mb-3 text-white">
                        <i class="fas fa-heartbeat text-primary me-2"></i>
                        <b>Sistem Monitoring MCU PPKP DKI Jakarta</b>
                    </h3>
                    <p class="text-white">Platform terpadu Monitoring Medical Check Up ASN DKI Jakarta. Pendaftaran, Penjadwalan, Monitoring, Hasil dan Laporan MCU secara terintegrasi.</p>
                </div>
                <div class="col-lg-3">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#features" class="text-white text-decoration-none">Fitur</a></li>
                        <li><a href="{{ route('login') }}" class="text-white text-decoration-none">Login</a></li>
                        <li><a href="{{ route('register') }}" class="text-white text-decoration-none">Daftar</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="mb-3">Kontak</h6>
                    <ul class="list-unstyled te">
                        <li><i class="fas fa-envelope me-2"></i>puspelkes@jakarta.go.id</li>
                        <li><i class="fas fa-phone me-2"></i>(021) 382 3065</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>DKI Jakarta</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="te mb-0">&copy; 2025 Sistem Monitoring MCU PPKP DKI Jakarta. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>

