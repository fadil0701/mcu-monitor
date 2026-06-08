# Panduan Operasi & Deploy — Monitoring MCU PPKP

> **Dokumen tunggal** untuk instalasi, keamanan, bridge CKG, proxy, dan troubleshooting.  
> Detail infrastruktur internal **hanya** di sini — jangan ulangi di komentar kode, view admin, atau pesan error publik.

---

## Ringkasan lingkungan produksi

| Item | Nilai |
|------|--------|
| VM IP | `10.15.101.117` |
| Port Docker MCU | `9003` |
| Path URL publik | `/mcuppkp/` |
| URL lengkap | `https://puspelkes.jakarta.go.id/mcuppkp/` |
| Database | `monitoring_mcu` |
| Portal CKG | port `9006`, path `/sikerja/` |
| Proxy korporat (jika dipakai) | `http://10.15.3.20:80` |

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

cp .env.production.example .env
nano .env

bash deploy/install.sh
```

**Kepemilikan folder:** jika `git clone` pernah pakai `sudo`, jalankan `sudo chown -R $USER:$USER /var/www/html/mcu-monitor`.

**Super admin:**

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env
```

## Tiga mode akses

| Mode | URL contoh | Script |
|------|------------|--------|
| Docker langsung | `http://<VM_IP>:9003/` | `LAN_IP=<VM_IP> ./deploy/set-lan-env.sh` |
| Portal + subpath | `http://<VM_IP>/mcuppkp/` | `./deploy/set-domain-env.sh` + snippet nginx |
| Domain HTTPS | `https://puspelkes.jakarta.go.id/mcuppkp/` | `./deploy/set-domain-env.sh` + SSL |

## Nginx host (path `/mcuppkp/`)

```bash
cd /var/www/html/mcu-monitor
bash deploy/install-nginx-snippet.sh
sudo bash deploy/patch-nginx-mcuppkp-include.sh
```

Vhost aktif di VM ini: `/etc/nginx/sites-enabled/puspelkes.jakarta.go.id`.

Sisipkan **sebelum** `location /`:

```nginx
include /etc/nginx/snippets/mcuppkp.conf;
```

```bash
sudo nginx -t && sudo systemctl reload nginx
curl -I http://127.0.0.1/mcuppkp/
```

Snippet mem-proxy ke `http://127.0.0.1:9003/` dengan strip prefix `/mcuppkp/`.

## Update produksi

```bash
cd /var/www/html/mcu-monitor
git pull origin main
bash deploy/update-production.sh
```

**Penting:** perubahan view/PHP ada di **image Docker**. `docker compose restart` saja **tidak cukup** — wajib `build app` (sudah ada di `update-production.sh`).

## Variabel `.env` penting

| Variabel | Produksi (domain) |
|----------|-------------------|
| `APP_URL` | `https://puspelkes.jakarta.go.id/mcuppkp` |
| `APP_PORT` | `9003` |
| `SESSION_PATH` | `/mcuppkp/` |
| `SESSION_SECURE_COOKIE` | `true` |
| `TRUSTED_PROXIES` | `*` |
| `DB_HOST` | `mysql` |
| `HTTP_PROXY` / `HTTPS_PROXY` | Isi jika VM wajib lewat proxy (lihat contoh di `.env.production.example`) |
| `NO_PROXY` | Harus mencakup `host.docker.internal`, `.docker.internal`, `172.16.0.0/12` |
| `CKG_API_BASE_URL` | Fallback `.env`; produksi aktif biasanya di **database** |
| `CKG_BRIDGE_INTERNAL_HOST` | IP VM untuk remap `127.0.0.1` dari form admin |
| `CKG_BRIDGE_INTERNAL_PORT` | `9006` |

## Proxy HTTP (build & runtime)

1. Isi `HTTP_PROXY` dan `HTTPS_PROXY` di `.env` aplikasi jika diperlukan.
2. Untuk Docker daemon: salin `deploy/docker-http-proxy.conf.example` ke `/etc/systemd/system/docker.service.d/http-proxy.conf`, sesuaikan host/port, lalu `systemctl daemon-reload && systemctl restart docker`.
3. Request bridge CKG internal memakai `CKG_BRIDGE_DISABLE_PROXY=true` agar tidak lewat FortiGate.

## Bridge CKG

### Konfigurasi

- Disimpan di tabel `ckg_bridge_configs` saat **Aktifkan konfigurasi database** = on.
- Deploy **tidak** menimpa baris database.
- Base URL: **hanya** `http://` (bukan `https://`), tanpa path `/api/...` atau `/sikerja`.

### URL dari container MCU

| Target | Hasil |
|--------|--------|
| `http://10.15.101.117:9006` dari container | Gagal (hairpin NAT) |
| `http://host.docker.internal:9006` tanpa `NO_PROXY` | Gagal (lewat proxy → DNS error) |
| `http://<gateway_compose>:9006` | **Benar** (mis. `172.22.0.1`) |

Cek gateway:

```bash
docker compose exec app ip route show default | awk '{print $3}'
```

### Firewall UFW (container → host port 9006)

```bash
sudo ufw allow from 172.16.0.0/12 to any port 9006 proto tcp comment 'Docker to CKG bridge'
sudo ufw reload
```

Rule `iptables DOCKER-USER` saja **tidak cukup** — traffic masuk chain `INPUT` + UFW.

### Konfigurasi & verifikasi

```bash
GW=$(docker compose exec -T app ip route show default | awk '{print $3}')

docker compose exec app php artisan ckg-bridge:configure \
  --base-url="http://${GW}:9006" \
  --api-key=<KEY_DARI_CKG> \
  --activate --test

docker compose exec app php artisan ckg-bridge:verify
docker compose exec app php artisan mcu:sync-participants-from-ckg
```

### Jika form admin ditolak WAF

Atur lewat SSH (CLI tidak melalui form web):

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app \
  php artisan ckg-bridge:configure \
  --base-url="http://${GW}:9006" \
  --api-key=<KEY> \
  --activate --test
```

### Update tanpa merusak bridge

| Aman | Hindari |
|------|---------|
| `bash deploy/update-production.sh` | Menimpa `.env` dari `.env.production.example` |
| `php artisan migrate --force` | `migrate:fresh` / `db:wipe` |
| `php artisan ckg-bridge:verify` | Mengubah URL di `.env` tanpa cek DB |

Setelah update, `update-production.sh` menjalankan `ckg-bridge:verify --warn-only` (read-only).

## Session & akses IP

Middleware `ForceSessionConfig` menonaktifkan `session.secure` bila host adalah IP atau localhost, agar cookie login tidak gagal saat akses LAN langsung.

## Service Docker

| Service | Fungsi |
|---------|--------|
| `app` | Nginx + PHP-FPM |
| `mysql` | MySQL 8.4 |
| `queue` | Worker antrian |
| `scheduler` | `schedule:run` tiap menit |

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

`docker-compose.prod.yml`: nonaktifkan expose MySQL, `extra_hosts: host.docker.internal:host-gateway`.

## Verifikasi

```bash
bash deploy/verify.sh
curl -fsS http://127.0.0.1:9003/up
curl -fsS https://puspelkes.jakarta.go.id/mcuppkp/up
```

## Troubleshooting

| Gejala | Tindakan |
|--------|----------|
| `APP_KEY` / `vendor` saat install | `bash deploy/install.sh` |
| Form/view tidak berubah setelah `git pull` | `bash deploy/update-production.sh` (rebuild image) |
| Login redirect loop | `SESSION_PATH=/mcuppkp/` |
| 502 nginx | `docker compose ps`, `docker compose logs app` |
| Build gagal (proxy) | Isi `HTTP_PROXY`/`HTTPS_PROXY` di `.env` |
| `npm ci` gagal di image | Build Vite via `deploy/build-frontend.sh` di host |
| CKG timeout / DNS dari container | Pakai gateway compose + `NO_PROXY` + UFW port 9006 |
| Bridge gagal setelah update | `php artisan ckg-bridge:verify`, perbaiki dengan `ckg-bridge:configure` |

## Perintah berguna

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear
docker compose -f docker-compose.yml -f docker-compose.prod.yml down
```

Spesifikasi API bridge: `dashboard-skrining/docs/BRIDGE-CKG-MCU.md`.
