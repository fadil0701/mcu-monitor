# Migrasi MCU Monitor: MySQL → PostgreSQL (health-platform)

> **Panduan lengkap (semua fase):** `../health-platform/docs/deployment/INSTALL-DAN-MIGRASI.md`

Konvensi penamaan: lihat `../health-platform/docs/deployment/DATABASE-NAMING.md`

## Arsitektur

```
health-platform (ppkp-data)
  ppkp-postgres
    ├── alias sikerja-postgres      → dashboard-skrining  (sikerja_ppkp / sikerja)
    └── alias mcu-monitor-postgres  → mcu-monitor       (mcu_monitor / mcu_monitor)

mcu-monitor (monitoring-mcu)
  app / queue / scheduler  → ppkp-data + monitoring-mcu
  mysql (profile mysql-legacy)  → sumber migrasi
```

## `.env` penting (mcu-monitor)

```env
DB_CONNECTION=pgsql
PGSQL_HOST=mcu-monitor-postgres
PGSQL_DATABASE=mcu_monitor
PGSQL_USERNAME=mcu_monitor
PGSQL_PASSWORD=Ppkp-Dev-2026!   # = MCU_DB_PASSWORD di health-platform/.env

MYSQL_HOST=mysql
MYSQL_DATABASE=monitoring_mcu
```

**Jangan** pakai host `sikerja-postgres` — itu khusus dashboard-skrining.

## Setup dari awal (lokal Windows)

```powershell
cd E:\laragon\www\mcu-monitor
.\deploy\install-migrate-pgsql.ps1 -InitEnv   # opsional
.\deploy\install-migrate-pgsql.ps1
```

## Produksi VM (`10.15.101.117`)

```bash
cd /var/www/html/healty-platform && ./scripts/install-production.sh
cd /var/www/html/mcu-monitor
# Edit .env: PGSQL_* (= MCU_DB_PASSWORD), pertahankan APP_URL/APP_KEY
./deploy/migrate-mysql-to-pgsql.sh
./deploy/update-production.sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan ckg-bridge:verify
```

Panduan bridge: [`BRIDGE-AFTER-PG-MIGRATION.md`](BRIDGE-AFTER-PG-MIGRATION.md)

## Langkah manual (lokal)

```powershell
cd E:\laragon\www\health-platform
.\scripts\install-local.ps1

cd E:\laragon\www\mcu-monitor
docker compose --profile mysql-legacy up -d mysql
docker compose up -d --build app queue scheduler
docker compose exec app php artisan migrate --database=pgsql --force
docker compose exec app php artisan mcu:migrate-mysql-to-pgsql --fresh
docker compose exec app php artisan mcu:migrate-mysql-to-pgsql --verify
```

## Bridge CKG ↔ MCU (tetap aktif setelah migrasi)

Bridging memakai **HTTP API**, bukan shared database. Tabel yang harus ikut migrasi data:

- dashboard: `mcu_bridge_configs`, `mcu_bridge_access_logs`
- MCU: `ckg_bridge_configs`, `ckg_bridge_sync_logs`, `participants`

Panduan: `docs/BRIDGE-AFTER-PG-MIGRATION.md` (MCU) dan `dashboard-skrining/docs/BRIDGE-CKG-MCU.md`

```powershell
docker compose exec app php artisan ckg-bridge:verify
```
