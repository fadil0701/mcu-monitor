#!/usr/bin/env bash
# Migrasi satu kali MySQL → PostgreSQL (health-platform / ppkp-postgres).
# Prasyarat: health-platform jalan, network ppkp-data, PGSQL_* di .env sudah benar.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"

SKIP_MYSQL=false
SKIP_DATA=false
for arg in "$@"; do
    case "$arg" in
        --skip-mysql) SKIP_MYSQL=true ;;
        --schema-only) SKIP_DATA=true ;;
    esac
done

load_proxy_from_env .env
ensure_ppkp_data_network

COMPOSE=(docker compose $(compose_prod_args))

echo "==> MCU Monitor — migrasi MySQL → PostgreSQL"
echo "    PGSQL_PASSWORD harus = MCU_DB_PASSWORD di health-platform/.env"
echo "    Mapping: health-platform/docs/deployment/APP-ENV.md"
echo ""

if ! $SKIP_MYSQL; then
    echo "==> 1/6 MySQL legacy (sumber data)"
    "${COMPOSE[@]}" --profile mysql-legacy up -d mysql
    "${COMPOSE[@]}" --profile mysql-legacy ps
    echo ""
fi

echo "==> 2/6 Schema PostgreSQL"
"${COMPOSE[@]}" exec -T app php artisan config:clear
"${COMPOSE[@]}" exec -T app php artisan migrate --database=pgsql --force
"${COMPOSE[@]}" exec -T app php artisan mcu:prepare-pgsql-schema

if ! $SKIP_DATA; then
    echo ""
    echo "==> 3/6 Salin data MySQL → PostgreSQL"
    "${COMPOSE[@]}" exec -T app php artisan mcu:migrate-mysql-to-pgsql --fresh --verify
    echo ""
    echo "==> 4/6 Perbaiki sequence PostgreSQL"
    "${COMPOSE[@]}" exec -T app php artisan mcu:fix-pgsql-sequences
else
    echo ""
    echo "==> 3/6 Verifikasi (tanpa salin data)"
    "${COMPOSE[@]}" exec -T app php artisan mcu:migrate-mysql-to-pgsql --verify
fi

echo ""
echo "==> 5/6 Verifikasi bridge CKG"
"${COMPOSE[@]}" exec -T app php artisan ckg-bridge:verify --warn-only || true

echo ""
echo "==> 6/6 Cutover"
echo "    Pastikan .env: DB_CONNECTION=pgsql (pertahankan APP_URL, APP_KEY, SESSION_PATH)"
echo "    Bridge API key: UI CKG → Bridging MCU, lalu MCU → Integrasi CKG"
echo "    Lalu: ./deploy/update-production.sh"
echo "    Panduan: docs/BRIDGE-AFTER-PG-MIGRATION.md"
