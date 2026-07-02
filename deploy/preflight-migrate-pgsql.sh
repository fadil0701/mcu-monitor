#!/usr/bin/env bash
# Cek prasyarat migrasi MySQL → PostgreSQL (MCU Monitor).
# Jalankan dari /var/www/html/mcu-monitor sebelum ./deploy/migrate-mysql-to-pgsql.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"

COMPOSE=(docker compose $(compose_prod_args))
HEALTH_ROOT="${HEALTH_PLATFORM_ROOT:-/var/www/html/healty-platform}"
FAIL=0

warn() { echo "  [!] $*"; FAIL=1; }
ok() { echo "  [OK] $*"; }
info() { echo "  [..] $*"; }

echo "==> MCU Monitor — preflight migrasi PostgreSQL"
echo "    Path: $ROOT"
echo ""

echo "==> 1. Infra health-platform"
if docker network inspect ppkp-data >/dev/null 2>&1; then
    ok "network ppkp-data ada"
else
    warn "network ppkp-data belum ada — jalankan: cd $HEALTH_ROOT && ./scripts/install-production.sh"
fi

if docker ps --format '{{.Names}}' | grep -qx 'ppkp-postgres'; then
  if docker inspect -f '{{.State.Health.Status}}' ppkp-postgres 2>/dev/null | grep -qx healthy; then
      ok "ppkp-postgres healthy"
  else
      info "ppkp-postgres jalan (health belum healthy — tunggu beberapa detik)"
  fi
else
    warn "container ppkp-postgres tidak jalan"
fi

if [ -f "$HEALTH_ROOT/.env" ]; then
    MCU_HP_PASS="$(grep -E '^MCU_DB_PASSWORD=' "$HEALTH_ROOT/.env" | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [ -n "$MCU_HP_PASS" ] && [ "$MCU_HP_PASS" != "GANTI_*" ]; then
        ok "MCU_DB_PASSWORD ditemukan di health-platform/.env"
    else
        warn "MCU_DB_PASSWORD belum diisi di $HEALTH_ROOT/.env"
    fi
else
    info "health-platform/.env tidak ditemukan di $HEALTH_ROOT (lewati cek password)"
    MCU_HP_PASS=""
fi

echo ""
echo "==> 2. File .env MCU"
if [ ! -f .env ]; then
    warn ".env tidak ada — cp .env.production.example .env lalu edit"
else
    ok ".env ada"

    DB_CONN="$(grep -E '^DB_CONNECTION=' .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [ "$DB_CONN" = "pgsql" ]; then
        ok "DB_CONNECTION=pgsql"
    else
        warn "DB_CONNECTION=$DB_CONN (seharusnya pgsql sebelum cutover)"
    fi

  for key in PGSQL_HOST PGSQL_DATABASE PGSQL_USERNAME PGSQL_PASSWORD; do
        val="$(grep -E "^${key}=" .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
        if [ -z "$val" ] || [[ "$val" == GANTI* ]]; then
            warn "$key belum diisi"
        else
            ok "$key terisi"
        fi
    done

    PGSQL_HOST_VAL="$(grep -E '^PGSQL_HOST=' .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [ "$PGSQL_HOST_VAL" = "mcu-monitor-postgres" ]; then
        ok "PGSQL_HOST=mcu-monitor-postgres"
    else
        warn "PGSQL_HOST=$PGSQL_HOST_VAL (harus mcu-monitor-postgres, bukan sikerja-postgres)"
    fi

    if [ -n "${MCU_HP_PASS:-}" ]; then
        MCU_APP_PASS="$(grep -E '^PGSQL_PASSWORD=' .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
        if [ "$MCU_APP_PASS" = "$MCU_HP_PASS" ]; then
            ok "PGSQL_PASSWORD = MCU_DB_PASSWORD (health-platform)"
        else
            warn "PGSQL_PASSWORD MCU ≠ MCU_DB_PASSWORD health-platform"
        fi
    fi

    APP_KEY_VAL="$(grep '^APP_KEY=' .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [ -n "$APP_KEY_VAL" ] && [[ "$APP_KEY_VAL" == base64:* ]]; then
        ok "APP_KEY sudah ada (pertahankan — jangan ganti saat migrasi)"
    else
        warn "APP_KEY kosong/invalid"
    fi

    if ! grep -qE '^MYSQL_(HOST|DATABASE)=' .env; then
        info "MYSQL_* tidak di .env — migrate-mysql-to-pgsql.sh akan pakai default container mysql"
    fi
fi

echo ""
echo "==> 3. Stack MCU Monitor"
if "${COMPOSE[@]}" ps --status running 2>/dev/null | grep -q monitoring-mcu-app; then
    ok "container monitoring-mcu-app jalan"
else
    warn "container app belum jalan — ./deploy/install.sh atau ./deploy/update-production.sh"
fi

echo ""
echo "==> 4. Backup (disarankan)"
STAMP="$(date +%F)"
if [ -f "$HOME/backup-env-mcu-${STAMP}" ] || ls "$HOME"/backup-env-mcu-* >/dev/null 2>&1; then
    ok "backup .env MCU sudah ada di home"
else
    info "belum ada backup .env — jalankan: cp .env ~/backup-env-mcu-${STAMP}"
fi

if [ -x "$HEALTH_ROOT/infrastructure/backup/backup-pre-cutover.sh" ]; then
    info "backup DB lengkap: cd $HEALTH_ROOT && MCU_ROOT=$ROOT ./infrastructure/backup/backup-pre-cutover.sh"
fi

echo ""
if [ "$FAIL" -eq 0 ]; then
    echo "==> Siap migrasi. Langkah berikutnya:"
    echo "    ./deploy/migrate-mysql-to-pgsql.sh"
    echo "    ./deploy/update-production.sh"
    echo "    bash deploy/verify.sh"
    echo "    Panduan: docs/MIGRATE-MYSQL-TO-POSTGRESQL.md"
    exit 0
fi

echo "==> Ada prasyarat yang belum terpenuhi. Perbaiki item [!] di atas lalu ulangi preflight."
exit 1
