# Panduan Operasi & Deploy — Monitoring MCU PPKP

> **Dokumen tunggal** untuk instalasi, keamanan, bridge CKG, proxy, branding, fitur aplikasi, dan troubleshooting.  
> Detail infrastruktur internal **hanya** di sini — jangan ulangi di komentar kode, view admin, atau pesan error publik.

## Kebijakan dokumentasi

**Setiap perubahan** (fitur, fix, konfigurasi deploy, aset UI, bridge CKG, `.env`) wajib dicatat di file ini:

1. Tambah atau perbarui bagian yang relevan (bukan hanya commit message).
2. Untuk perubahan kecil, tambah baris di [Riwayat perubahan](#riwayat-perubahan).
3. Jangan menulis IP, proxy, atau workaround sensitif di kode/view — cukup di sini.

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

| Mode | URL contoh | Script | `APP_USE_REQUEST_URL` |
|------|------------|--------|------------------------|
| Docker langsung | `http://<VM_IP>:9003/` | `LAN_IP=<VM_IP> ./deploy/set-lan-env.sh` | `true` |
| Portal + subpath | `http://<VM_IP>/mcuppkp/` | `./deploy/set-domain-env.sh` + snippet nginx | `false` |
| Domain HTTPS | `https://puspelkes.jakarta.go.id/mcuppkp/` | `./deploy/set-domain-env.sh` + SSL | `false` |

### URL aplikasi & link login

Laravel membangun semua `route()` (login, daftar, dll.) dari `AppServiceProvider`:

- **`APP_USE_REQUEST_URL=false`** (produksi domain): pakai `APP_URL` → link mengarah ke domain, mis. `https://puspelkes.jakarta.go.id/mcuppkp/login`.
- **`APP_USE_REQUEST_URL=true`** (mode LAN `:9003`): pakai host permintaan saat ini → jika dibuka lewat `http://10.15.101.117:9003`, link login ikut ke IP tersebut.

**Produksi publik wajib domain:**

```bash
bash deploy/set-domain-env.sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
```

Jangan pakai `set-lan-env.sh` untuk pengguna akhir — script itu hanya untuk debug/akses internal port `9003`.

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

**Penting:** perubahan view/PHP/CSS/aset gambar ada di **image Docker**. `docker compose restart` atau `git pull` saja **tidak cukup** — wajib `bash deploy/update-production.sh` (rebuild image).

Setelah deploy UI, hard refresh browser (`Ctrl+Shift+R`) atau tab incognito.

## Branding & aset UI

| Aset | Path | Dipakai di |
|------|------|------------|
| Favicon | `public/assets/img/icon-ppkp.png` | Semua layout (`app`, `auth`, `welcome`) |
| Ikon (persegi) | `public/assets/img/icon-ppkp.png` | **Sidebar** admin (`menu.blade.php`), tinggi 40px |
| Logo horizontal | `public/assets/img/logo-ppkp.png` | Navbar landing, form login/daftar/aktivasi |
| CSS logo | `public/assets/css/mcu-admin.css` | Admin + auth (via `auth.blade.php`) |

**Sidebar:** jangan pakai `logo-ppkp.png` (horizontal) — di lebar sidebar logo terpotong jadi garis warna. Pakai `icon-ppkp.png`.

**Form login / daftar / aktivasi:** `logo-ppkp.png` dibatasi `height: 48px` (inline style di `brand.blade.php` + style di `auth.blade.php`).

**Ganti logo/favicon:** ganti file PNG di path di atas, commit, lalu `bash deploy/update-production.sh`.

## Variabel `.env` penting

| Variabel | Produksi (domain) | Mode LAN (`:9003`) |
|----------|-------------------|---------------------|
| `APP_URL` | `https://puspelkes.jakarta.go.id/mcuppkp` | `http://<VM_IP>:9003` |
| `ASSET_URL` | sama dengan `APP_URL` | (kosong / sama `APP_URL`) |
| `APP_USE_REQUEST_URL` | `false` | `true` |
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
| Import gagal "NIK wajib diisi" padahal sudah diisi | Pastikan data di sheet **Data Peserta**, unduh ulang template; jangan import sheet Referensi |
| `APP_KEY` / `vendor` saat install | `bash deploy/install.sh` |
| Form/view/logo tidak berubah setelah `git pull` | `bash deploy/update-production.sh` (rebuild image), lalu hard refresh browser |
| CSS/UI admin masih tampilan lama setelah update | Lihat [CSS tidak ter-update](#css-tidak-ter-update-setelah-deploy) di bawah |
| `instansi_pemprov_dkis` tidak ada / error laporan & form SKPD | `php artisan migrate --force` lalu `php artisan db:seed --class=InstansiPemprovDkiSeeder --force` |
| Link login ke `http://<IP>:9003` bukan domain | `bash deploy/set-domain-env.sh`, `config:cache`; jangan pakai `set-lan-env.sh` di produksi |
| Logo form auth membesar / terpotong | Pastikan commit branding terbaru ter-deploy; cek `height:48px` di `brand.blade.php` di dalam container |
| Logo sidebar garis warna / terpotong | Sidebar harus pakai `icon-ppkp.png`, bukan `logo-ppkp.png`; rebuild image |
| Login redirect loop | `SESSION_PATH=/mcuppkp/` |
| 502 nginx | `docker compose ps`, `docker compose logs app` |
| Build gagal (proxy) | Isi `HTTP_PROXY`/`HTTPS_PROXY` di `.env` |
| `npm ci` gagal di image | Build Vite via `deploy/build-frontend.sh` di host |
| CKG timeout / DNS dari container | Pakai gateway compose + `NO_PROXY` + UFW port 9006 |
| Bridge gagal setelah update | `php artisan ckg-bridge:verify`, perbaiki dengan `ckg-bridge:configure` |
| WhatsApp template tidak berubah / pesan tidak sesuai | Lihat [`docs/API_WA.example.MD`](./API_WA.example.MD): provider `apico`, isi Nama Template WA, sync Meta |

### CSS tidak ter-update setelah deploy

`mcu-admin.css` dan aset Sneat lain di `public/assets/` **dibungkus ke image Docker** saat `docker compose build`. Hanya `git pull` atau `docker compose restart` **tidak** mengganti file di dalam container.

**Penyebab umum:**

1. **Tidak rebuild image** — wajib `bash deploy/update-production.sh` (bukan hanya pull/restart).
2. **Cache browser** — URL CSS sama setiap deploy; browser menyimpan file lama. Setelah deploy terbaru, layout memakai `versioned_asset()` (`?v=<timestamp file>`) agar browser memuat ulang.
3. **Docker layer cache** — jarang, tetapi jika ukuran CSS di container ≠ host, jalankan:
   ```bash
   DOCKER_BUILD_NO_CACHE=1 bash deploy/update-production.sh
   ```

**Verifikasi di server:**

```bash
# Ukuran file di host (setelah git pull)
stat -c%s public/assets/css/mcu-admin.css

# Ukuran file di dalam container (harus sama)
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app \
  stat -c%s /var/www/html/public/assets/css/mcu-admin.css

# Cek URL CSS di HTML (harus ada ?v=...)
curl -s http://127.0.0.1:9003/login | grep mcu-admin
```

Script `update-production.sh` membandingkan ukuran host vs container dan memperingatkan jika berbeda.

## Perintah berguna

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:clear
docker compose -f docker-compose.yml -f docker-compose.prod.yml down
```

Spesifikasi API bridge: `dashboard-skrining/docs/BRIDGE-CKG-MCU.md`.

## Import data peserta (admin)

Menu: **Admin → Data Peserta → Download Template**

| Item | Nilai |
|------|--------|
| File template | `template_import_peserta.xlsx` |
| Generator | `app/Exports/ParticipantsImportTemplateExport.php` |
| Route download | `GET /admin/participants/template/download` |
| Import handler | `app/Imports/ParticipantsImport.php` |

**Kolom wajib:** NIK (16 digit, format Teks), Nama. Header kolom wajib berwarna **merah muda** (bertanda `*`).

**Kolom opsional:** Jenis Kelamin (`L`/`P`), Tanggal Lahir (`YYYY-MM-DD`), NRK, Tempat Lahir, SKPD, UKPD, No Telp, Email, Status Pegawai, Pendidikan Terakhir, Status MCU, Tanggal MCU Terakhir, Catatan. Header **biru muda**; beberapa kolom punya dropdown ke sheet **Referensi**.

Template berisi 3 sheet: **Data Peserta** (isi import di sini), **Referensi**, **Petunjuk**. Sistem hanya membaca sheet **Data Peserta**.

Baris dengan NIK/NRK yang sudah ada akan **dilewati** (skip), tidak diperbarui dan tidak diduplikasi.

## Pengajuan jadwal MCU (portal peserta)

Menu: **Portal Peserta → Pendaftaran Ulang MCU**

| Item | Nilai |
|------|--------|
| Kuota harian | `MCU_DAILY_QUOTA` di `.env` (default `100`; `0` = tidak dibatasi) |
| Syarat CKG | Peserta wajib punya data skrining dari bridge CKG (`ckg_peserta_id` atau `ckg_registration_code`) |
| Info kuota | Form menampilkan sisa kuota per tanggal (`GET /client/schedule/quota?date=YYYY-MM-DD`) |
| Validasi server | Kuota penuh ditolak saat submit; interval MCU terakhir tetap berlaku |

Peserta yang belum tercatat di CKG PPKP tidak dapat mengajukan jadwal MCU sampai data skrining tersinkron (bridge CKG aktif).

## WhatsApp (Api.co.id Chat Gateway)

Integrasi WhatsApp produksi memakai **Api.co.id** — salin [`docs/API_WA.example.MD`](./API_WA.example.MD) ke `docs/API_WA.MD` (file ini **tidak** di-commit; berisi API Key) dan Postman [`docs/Api.co.id-Chat-Gateway.postman_collection.json`](./Api.co.id-Chat-Gateway.postman_collection.json).

**Konfigurasi singkat (Admin → Pengaturan → WhatsApp):**

| Field | Isi |
|-------|-----|
| Penyedia | Api.co.id Chat Gateway |
| API Token | API Key dari dashboard Api.co.id |
| Phone Number ID | `id` dari `GET /phone-numbers` (bukan User ID) |
| Nama Template WA (Undangan / Hasil) | Nama template **APPROVED** di Meta |
| Bahasa Template | `id` |
| Variabel template | Urutan koma sesuai `{{1}}`, `{{2}}`, … di Meta |

**Penting:** teks di **Admin → Template WhatsApp** tidak menggantikan template Meta jika **Nama Template WA** sudah diisi — edit template di Meta Business Manager, sync di Api.co.id, lalu pastikan nama & urutan variabel cocok.

## Backup database

Menu: **Super Admin → Backup Database** (`/admin/backup`)

| Item | Nilai |
|------|--------|
| Output | `storage/backups/database/` (volume `app_storage` di Docker) |
| Retensi | `BACKUP_RETENTION_DAYS` (default 14 hari) |
| Kompresi | `BACKUP_COMPRESS=true` (gzip) |
| Enkripsi | `BACKUP_ENCRYPT=true` + file `.backup-passphrase` (salin dari `deploy/backup-passphrase.example`) |
| Restore | **Hanya SSH** — `./deploy/restore-database.sh` (tidak tersedia di UI) |

**Backup manual (UI):** Super Admin → Backup Database → **Backup sekarang** (maks. 3× per 10 menit).

**Backup otomatis (cron / scheduler):**

```bash
# Via scheduler container (sudah terjadwal 03:00 WIB)
docker compose exec -T app php artisan mcu:backup-database

# Atau shell di host VM
./deploy/backup-database.sh
```

**Restore (SSH di server):**

```bash
./deploy/restore-database.sh --list
./deploy/restore-database.sh --verify storage/backups/database/backup-monitoring_mcu-YYYYMMDD-HHMMSS.sql.gz.gpg
./deploy/restore-database.sh storage/backups/database/backup-monitoring_mcu-YYYYMMDD-HHMMSS.sql.gz.gpg
```

**Env penting (Docker):** set `MYSQL_ROOT_PASSWORD` agar `mysqldump` dari container `app` dapat mengakses MySQL.

**Setup pertama kali enkripsi (wajib sebelum backup dengan `BACKUP_ENCRYPT=true`):**

```bash
cd /var/www/html/mcu-monitor

# Opsi A — file di folder proyek (cukup untuk script host)
cp deploy/backup-passphrase.example .backup-passphrase
chmod 600 .backup-passphrase
nano .backup-passphrase   # isi passphrase kuat (min. 20 karakter)

# Opsi B — produksi (disarankan, sama pola SIKERJA)
sudo mkdir -p /etc/mcuppkp
sudo cp deploy/backup-passphrase.example /etc/mcuppkp/backup.pass
sudo chmod 600 /etc/mcuppkp/backup.pass
sudo nano /etc/mcuppkp/backup.pass
# .env:
# BACKUP_GPG_PASSPHRASE_FILE=/etc/mcuppkp/backup.pass
```

Setelah file passphrase ada, jalankan ulang `./deploy/backup-database.sh` atau `docker compose exec -T app php artisan mcu:backup-database`.

**Catatan Docker:** mount passphrase ke container sudah diset di `docker-compose.prod.yml` (`/etc/mcuppkp/backup.pass`). Buat file di host **sebelum** `docker compose up` agar backup dari UI/scheduler di container juga berhasil.

## Riwayat perubahan

| Commit | Ringkasan |
|--------|-----------|
| *(pending)* | Backup database: UI Super Admin, artisan `mcu:backup-database`, script deploy |
| *(pending)* | UI: pagination per page 15/50/100 di Data Peserta |
| *(pending)* | Portal peserta: kuota harian MCU + syarat CKG sebelum ajukan jadwal |
| `3b1c5b6` | Fix import: hanya baca sheet Data Peserta (bukan Referensi) |
| `79a323a` | Template import: sheet Referensi, warna kolom wajib, Tanggal Lahir wajib |
| `87e624d` | Perbaiki template import peserta + kolom pendidikan terakhir |
| `941cf20` | Update riwayat perubahan sidebar logo |
| `804974d` | Sidebar admin: ganti ke `icon-ppkp.png` (logo horizontal terpotong) |
| `df7c728` | Kebijakan dokumentasi wajib + riwayat perubahan di DEPLOY.md |
| `564d58d` | Fix logo auth: `height:48px` inline + style di `auth.blade.php` |
| `2f8a441` | Muat `mcu-admin.css` di layout auth |
| `55b2e62` | Branding: favicon `icon-ppkp.png`, logo `logo-ppkp.png` |
| `5568cf9` | Konsolidasi catatan operasi/keamanan ke `docs/DEPLOY.md` |
| `71ecf7d` | Field pendidikan terakhir di form daftar MCU; email placeholder kosong |
| `d4d9546` | Lindungi konfigurasi bridge CKG saat `update-production.sh` |
| `0a6250b` | Form aktivasi peserta lengkap + pendidikan terakhir |

> Tambahkan baris baru di tabel ini setiap ada perubahan yang di-deploy.
