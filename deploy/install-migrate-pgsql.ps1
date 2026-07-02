# Migrasi MySQL -> PostgreSQL (MCU Monitor)
# Prasyarat: health-platform sudah jalan (network ppkp-data, mcu_monitor user).

param(
    [switch]$InitEnv,
    [switch]$SkipBuild,
    [switch]$FreshOnly,
    [switch]$VerifyOnly
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$HealthPlatform = Join-Path (Split-Path -Parent $Root) "health-platform"
Set-Location $Root

function Require-Docker {
    docker info *> $null
    if ($LASTEXITCODE -ne 0) {
        throw "Docker tidak siap. Buka Docker Desktop lalu coba lagi."
    }
}

function Ensure-EnvFile {
    if ($InitEnv -or -not (Test-Path ".env")) {
        if ((Test-Path ".env") -and $InitEnv) {
            $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
            Copy-Item ".env" ".env.backup-$stamp"
        }
        Copy-Item ".env.docker.example" ".env"
        Write-Host "File .env dibuat dari .env.docker.example - sesuaikan APP_KEY dan password."
    }
}

function Ensure-HealthPlatform {
    if (-not (Test-Path $HealthPlatform)) {
        throw "Folder health-platform tidak ditemukan di: $HealthPlatform"
    }

    $network = docker network ls --format "{{.Name}}" | Select-String -Pattern "^ppkp-data$"
    if (-not $network) {
        Write-Host "Network ppkp-data belum ada. Menjalankan health-platform..."
        Push-Location $HealthPlatform
        if (-not (Test-Path ".env")) {
            Copy-Item ".env.example" ".env"
            throw "Isi health-platform/.env dulu, lalu jalankan ulang skrip ini."
        }
        & ".\scripts\install-local.ps1"
        Pop-Location
    }
}

Require-Docker
Ensure-EnvFile
Ensure-HealthPlatform

Write-Host ""
Write-Host "==> 1/5 MySQL legacy (sumber data)"
docker compose --profile mysql-legacy up -d mysql
docker compose --profile mysql-legacy ps

Write-Host ""
Write-Host "==> 2/5 Build dan start aplikasi (PostgreSQL aktif)"
if (-not $SkipBuild) {
    docker compose build app
}
docker compose up -d --force-recreate app queue scheduler

Write-Host "Menunggu container app..."
Start-Sleep -Seconds 8

if ($VerifyOnly) {
    docker compose exec app php artisan mcu:migrate-mysql-to-pgsql --verify
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "==> 3/5 Schema PostgreSQL"
docker compose exec app php artisan config:clear
docker compose exec app php artisan migrate --database=pgsql --force

if (-not $FreshOnly) {
    Write-Host ""
    Write-Host "==> 4/5 Salin data MySQL ke PostgreSQL"
    docker compose exec app php artisan mcu:migrate-mysql-to-pgsql --fresh
}

Write-Host ""
Write-Host "==> 5/5 Verifikasi"
docker compose exec app php artisan mcu:migrate-mysql-to-pgsql --verify

Write-Host ""
Write-Host "==> 6/6 Verifikasi bridge CKG (HTTP ke dashboard-skrining)"
docker compose exec app php artisan ckg-bridge:verify --warn-only
if ($LASTEXITCODE -ne 0) {
    Write-Host "PERINGATAN: Bridge belum OK - generate API key di CKG (Bridging MCU) lalu tempel di MCU (Integrasi CKG)"
    Write-Host "Panduan: docs/BRIDGE-AFTER-PG-MIGRATION.md"
}

$appPort = "9003"
$portLine = Select-String -Path ".env" -Pattern "^APP_PORT=" | Select-Object -First 1
if ($portLine) {
    $appPort = $portLine.Line.Split("=", 2)[1].Trim()
}

Write-Host ""
Write-Host "Selesai. Aplikasi: http://127.0.0.1:$appPort"
Write-Host "Bridge: docs/BRIDGE-AFTER-PG-MIGRATION.md"
Write-Host "pgAdmin: mcu_monitor / mcu_monitor (MCU_DB_PASSWORD di health-platform/.env)"
