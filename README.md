# Monitoring MCU

Sistem monitoring Medical Check Up (MCU) berbasis **Laravel 13** dengan template admin **Sneat 1.0.0** (Bootstrap 5). Port logic dari `monitoring-mcu-ppkp` **tanpa Filament**.

## Stack

- Laravel 13
- Sneat Bootstrap 5 (assets di `public/assets/`)
- Spatie Laravel Permission
- Maatwebsite Excel, DomPDF, Intervention Image
- Auth Laravel native (controller + route, tanpa Breeze/Filament)

## Deploy VM produksi (Docker)

Panduan lengkap: **`docs/DEPLOY.md`**

```bash
cp .env.production.example .env
nano .env
chmod +x deploy/install.sh
./deploy/install.sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env
```

- Port produksi: **9003**
- Path publik: **`/mcuppkp/`**
- Update: `./deploy/update-production.sh`

## Deploy dengan Docker (lokal / dev)

Prasyarat: [Docker Desktop](https://www.docker.com/products/docker-desktop/) atau Docker Engine + Compose v2.

```bash
cp .env.docker.example .env

# Generate APP_KEY (wajib — dipakai semua container)
php artisan key:generate --show
# Salin output ke APP_KEY= di .env

# Edit .env: APP_URL, DB_PASSWORD, SUPER_ADMIN_*

docker compose build
docker compose up -d
```

Production (tanpa expose port MySQL ke host):

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Aplikasi tersedia di **http://localhost:8080** (ubah lewat `APP_PORT` di `.env`).

Setelah container berjalan, buat super admin:

```bash
docker compose exec app php artisan user:create-admin --from-env
```

Perintah berguna:

```bash
docker compose ps                    # status service
docker compose logs -f app           # log aplikasi
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose down                  # stop semua service
docker compose down -v               # stop + hapus volume database
```

### Service Docker

| Service | Fungsi |
|---------|--------|
| `app` | Nginx + PHP-FPM (web) |
| `mysql` | Database MySQL 8.4 |
| `queue` | Worker antrian (`QUEUE_CONNECTION=database`) |
| `scheduler` | Menjalankan `schedule:run` setiap menit |

Volume persisten: data MySQL, file upload (`storage`), cache bootstrap.

## Setup (tanpa Docker)

```bash
cd monitoring-mcu
composer install
cp .env.example .env
php artisan key:generate
```

Atur database MySQL di `.env`, lalu:

```bash
php artisan migrate
php artisan db:seed
```

Buat super admin:

```bash
# Isi SUPER_ADMIN_* di .env terlebih dahulu
php artisan user:create-admin --from-env
```

Jalankan development server:

```bash
php artisan serve
# atau: composer run dev
```

## URL Utama

| Route | Keterangan |
|-------|------------|
| `/login` | Login admin & peserta |
| `/dashboard` | Dashboard (admin / peserta) |
| `/admin/*` | Modul admin (participants, schedules, mcu-results, dll.) |
| `/client/*` | Portal peserta |
| `/peserta/aktivasi-akun` | Aktivasi akun peserta |

## Perbedaan dengan monitoring-mcu-ppkp

- **Tidak ada Filament** — semua modul admin memakai controller + Blade custom
- **UI Sneat** — layout di `resources/views/layouts/sneat/`
- **Laravel 13** — versi framework terbaru

## Struktur Penting

```
app/
  Http/Controllers/Admin/   # CRUD admin
  Models/                   # Domain models
  Services/                 # Email, WhatsApp, PDF, dll.
  Helpers/MenuHelper.php    # Sidebar Sneat
resources/views/
  layouts/sneat/            # Master layout Sneat
  admin/                    # Halaman admin
  client/                   # Portal peserta
public/assets/              # Asset Sneat template
templates/sneat-1.0.0/      # Referensi HTML asli
```

## Artisan Commands

- `php artisan user:create-admin --from-env` — bootstrap super admin
- `php artisan mcu:send-invitations` — kirim undangan MCU
- `php artisan mcu:send-reminders` — pengingat MCU
- `php artisan smtp:configure --interactive` — konfigurasi SMTP

## Catatan Migrasi View

Halaman admin yang di-port dari ppkp masih menggunakan class Tailwind di beberapa file. Layout utama sudah Sneat; konversi penuh ke Bootstrap Sneat dilakukan bertahap (contoh selesai: `admin/participants/index`).
