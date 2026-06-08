#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
ENV_FILE="${1:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE tidak ditemukan"
    exit 1
fi

if [ -z "${LAN_IP:-}" ]; then
    echo "ERROR: Set LAN_IP terlebih dahulu (lihat docs/DEPLOY.md)"
    exit 1
fi

APP_PORT="${APP_PORT:-9003}"
if grep -q '^APP_PORT=' "$ENV_FILE"; then
    APP_PORT="$(grep '^APP_PORT=' "$ENV_FILE" | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi
LAN_URL="http://${LAN_IP}:${APP_PORT}"

set_var() {
    local key="$1"
    local val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
}

set_var APP_URL "$LAN_URL"
set_var APP_USE_REQUEST_URL true
set_var SESSION_PATH /
set_var SESSION_SECURE_COOKIE false

echo "Diperbarui $ENV_FILE untuk LAN:"
grep -E '^(APP_URL|APP_USE_REQUEST_URL|SESSION_PATH|SESSION_SECURE_COOKIE|APP_PORT)=' "$ENV_FILE"
echo ""
echo "Jalankan:"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache"
