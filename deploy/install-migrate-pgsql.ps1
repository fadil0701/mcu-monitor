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

function Read-EnvValue([string]$Key, [string]$File = ".env") {
    if (-not (Test-Path $File)) { return "" }

    $line = Select-String -Path $File -Pattern "^$([regex]::Escape($Key))=" -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if (-not $line) { return "" }

    return $line.Line.Substring($Key.Length + 1).Trim().Trim('"').Trim("'")
}

function Set-EnvValue([string]$Key, [string]$Value, [string]$File = ".env") {
    if (-not (Test-Path $File)) {
        throw "File tidak ditemukan: $File"
    }

    $content = Get-Content $File -Raw
    $pattern = "(?m)^$([regex]::Escape($Key))=.*$"
    $escaped = [string]$Value
    $replacement = "$Key=$escaped"

    if ($content -match $pattern) {
        $content = [regex]::Replace($content, $pattern, $replacement)
    } else {
        if (-not $content.EndsWith("`n")) { $content += "`n" }
        $content += $replacement + "`n"
    }

    [System.IO.File]::WriteAllText((Resolve-Path $File), $content)
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

function Sync-PgsqlPasswordFromHealthPlatform {
    $hpEnv = Join-Path $HealthPlatform ".env"
    if (-not (Test-Path $hpEnv)) {
        Write-Host "PERINGATAN: health-platform/.env tidak ditemukan — pastikan PGSQL_PASSWORD benar."
        return
    }

    $mcuPass = Read-EnvValue -Key "MCU_DB_PASSWORD" -File $hpEnv
    if ([string]::IsNullOrWhiteSpace($mcuPass)) {
        Write-Host "PERINGATAN: MCU_DB_PASSWORD kosong di health-platform/.env — lewati sync password."
        return
    }

    Set-EnvValue -Key "PGSQL_PASSWORD" -Value $mcuPass -File ".env"
    Write-Host "PGSQL_PASSWORD diselaraskan dengan MCU_DB_PASSWORD (health-platform)."
}

function Ensure-AppKey {
    $appKey = Read-EnvValue -Key "APP_KEY" -File ".env"
    $isValid = -not [string]::IsNullOrWhiteSpace($appKey) -and $appKey.StartsWith("base64:")

    if ($isValid) { return }

    Write-Host "Menghasilkan APP_KEY..."
    $newKey = docker run --rm `
        -v "${Root}:/app" -w /app `
        php:8.3-cli `
        php -r "echo 'base64:'.base64_encode(random_bytes(32));" | Select-Object -First 1

    if ([string]::IsNullOrWhiteSpace($newKey)) {
        throw "Gagal menghasilkan APP_KEY."
    }

    Set-EnvValue -Key "APP_KEY" -Value $newKey -File ".env"
    Write-Host "APP_KEY sudah diisi (generated)."
}

function Ensure-FrontendBuild {
    if (Test-Path "public/build/manifest.json") { return }

    Write-Host "Build frontend (Vite) karena public/build belum ada..."
    $httpProxy = Read-EnvValue -Key "HTTP_PROXY" -File ".env"
    $httpsProxy = Read-EnvValue -Key "HTTPS_PROXY" -File ".env"
    $noProxy = Read-EnvValue -Key "NO_PROXY" -File ".env"

    $dockerArgs = @()
    if (-not [string]::IsNullOrWhiteSpace($httpProxy)) {
        $dockerArgs += @("-e", "HTTP_PROXY=$httpProxy", "-e", "http_proxy=$httpProxy")
    }
    if (-not [string]::IsNullOrWhiteSpace($httpsProxy)) {
        $dockerArgs += @("-e", "HTTPS_PROXY=$httpsProxy", "-e", "https_proxy=$httpsProxy")
    } elseif ($dockerArgs.Count -eq 0 -and -not [string]::IsNullOrWhiteSpace($httpProxy)) {
        # Fallback: kalau HTTPS_PROXY tidak ada, pakai HTTP_PROXY.
        $dockerArgs += @("-e", "HTTPS_PROXY=$httpProxy", "-e", "https_proxy=$httpProxy")
    }
    if (-not [string]::IsNullOrWhiteSpace($noProxy)) {
        $dockerArgs += @("-e", "NO_PROXY=$noProxy", "-e", "no_proxy=$noProxy")
    }

    docker run --rm @dockerArgs `
        -v "${Root}:/app" -w /app `
        node:22-bookworm-slim `
        bash -c "set -e; npm ci --no-audit --no-fund; npm run build; test -f public/build/manifest.json"
}

Require-Docker
Ensure-EnvFile
Ensure-HealthPlatform
Sync-PgsqlPasswordFromHealthPlatform
Ensure-AppKey

Write-Host ""
Write-Host "==> 1/5 MySQL legacy (sumber data)"
docker compose --profile mysql-legacy up -d mysql
docker compose --profile mysql-legacy ps

Write-Host ""
Write-Host "==> 2/5 Build & start aplikasi (PostgreSQL aktif)"
if (-not $SkipBuild) {
    Ensure-FrontendBuild
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
docker compose exec app php artisan mcu:prepare-pgsql-schema

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
