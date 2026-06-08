# Deploy Monitoring MCU PPKP (Docker VM)

Target server (satu VM dengan Dashboard CKG):

| Item | Nilai |
|------|--------|
| VM IP | `10.15.101.117` |
| Port Docker MCU | `9003` |
| Path URL | `/mcuppkp/` |
| URL lengkap | `https://puspelkes.jakarta.go.id/mcuppkp/` |
| Database | `monitoring_mcu` |
| Dashboard CKG (referensi) | `:9006` path `/sikerja/` |

## Prasyarat VM

- Docker Engine 24+ dan Compose v2
- Git
- Port `9003` bebas
- Nginx host untuk reverse proxy path `/mcuppkp/`

## Instalasi pertama

```bash
cd /var/www/html
git clone https://github.com/fadil0701/mcu-monitor.git mcu-monitor
cd mcu-monitor

# JANGAN sudo git clone — jika sudah terlanjur:
# sudo chown -R $USER:$USER /var/www/html/mcu-monitor

cp .env.production.example .env
nano .env   # DB_PASSWORD, MYSQL_ROOT_PASSWORD, SUPER_ADMIN_*

bash deploy/install.sh
```

> **Permission denied** pada `chmod` atau `cp .env`? Folder dimiliki `root` karena `sudo git clone`.
> Perbaiki: `sudo chown -R $USER:$USER /var/www/html/mcu-monitor`
> Skrip bisa dijalankan tanpa `chmod`: `bash deploy/install.sh`

Buat super admin:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env
```

## Tiga mode akses

| Mode | URL | Script env |
|------|-----|------------|
| **Docker langsung** | `http://10.15.101.117:9003/` | `./deploy/set-lan-env.sh` |
| **Portal + subpath** | `http://10.15.101.117/mcuppkp/` | `./deploy/set-domain-env.sh` + snippet nginx |
| **Domain HTTPS** | `https://puspelkes.jakarta.go.id/mcuppkp/` | `./deploy/set-domain-env.sh` + BigIP/SSL |

## Nginx host (path `/mcuppkp/`)

Tambahkan **sebelum** `location /` pada config nginx portal:

```bash
# Salin atau include:
# deploy/nginx-mcuppkp-portal-snippet.conf
sudo nginx -t && sudo systemctl reload nginx
```

Isi snippet mem-proxy ke `http://127.0.0.1:9003/` dengan strip prefix `/mcuppkp/`.

## Update produksi

```bash
cd /var/www/html/mcu-monitor
git pull origin main
./deploy/update-production.sh
```

## Variabel `.env` penting

| Variabel | Produksi (domain) |
|----------|-------------------|
| `APP_URL` | `https://puspelkes.jakarta.go.id/mcuppkp` |
| `APP_PORT` | `9003` |
| `SESSION_PATH` | `/mcuppkp/` |
| `SESSION_SECURE_COOKIE` | `true` |
| `TRUSTED_PROXIES` | `*` |
| `DB_HOST` | `mysql` (nama service compose) |

## Verifikasi

```bash
./deploy/verify.sh
curl -fsS http://127.0.0.1:9003/up
curl -fsS https://puspelkes.jakarta.go.id/mcuppkp/up
```

## Service Docker

| Service | Fungsi |
|---------|--------|
| `app` | Nginx + PHP-FPM (web) |
| `mysql` | MySQL 8.4 |
| `queue` | Worker antrian |
| `scheduler` | `schedule:run` tiap menit |

Production memakai:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Override `docker-compose.prod.yml` menonaktifkan expose port MySQL ke host.

## Bridge CKG (rencana)

Setelah API bridge tersedia di dashboard-skrining, isi di `.env` MCU:

```env
CKG_API_BASE_URL=http://127.0.0.1:9006
CKG_API_KEY=<sama dengan MCU_API_KEY di CKG>
```

Spesifikasi: `dashboard-skrining/docs/BRIDGE-CKG-MCU.md`.

## Troubleshooting

| Gejala | Tindakan |
|--------|----------|
| `APP_KEY` / `vendor/autoload.php` saat install | `bash deploy/install.sh` (generate key tanpa composer di host). Manual: `docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));"` |
| Login redirect loop | Cek `SESSION_PATH` = `/mcuppkp/` saat pakai subpath |
| 502 dari nginx | `docker compose ps`, `docker compose logs app` |
| Build gagal (proxy) | Hapus placeholder `PROXY_HOST:PORT` di `.env` atau isi proxy asli, mis. `HTTP_PROXY=http://10.15.3.20:80`. Kosongkan jika tidak pakai proxy. |

## Perintah berguna

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.prod.yml down
```
