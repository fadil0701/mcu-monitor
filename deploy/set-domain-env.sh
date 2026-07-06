#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
ENV_FILE="${1:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE tidak ditemukan"
    exit 1
fi

BASE="${DOMAIN_BASE_URL:-https://<DOMAIN_PRODUKSI>/mcuppkp}"
BASE="${BASE%/}"

set_var() {
    local key="$1"
    local val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
}

set_var APP_URL "$BASE"
set_var ASSET_URL "$BASE"
set_var APP_USE_REQUEST_URL false
set_var SESSION_PATH /mcuppkp/
set_var SESSION_SECURE_COOKIE true
set_var TRUSTED_PROXIES '*'

echo "Diperbarui $ENV_FILE untuk domain produksi:"
grep -E '^(APP_URL|ASSET_URL|APP_USE_REQUEST_URL|SESSION_PATH|SESSION_SECURE_COOKIE|TRUSTED_PROXIES)=' "$ENV_FILE"
echo ""
echo "Restart setelah perubahan:"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache"
echo "  curl -fsS \"${BASE}/up\" | head -c 200"
