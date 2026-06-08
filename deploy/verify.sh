#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

APP_PORT="${APP_PORT:-9003}"
if [ -f .env ] && grep -q '^APP_PORT=' .env; then
    APP_PORT="$(grep '^APP_PORT=' .env | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi

echo "==> docker compose ps"
# shellcheck disable=SC2046
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps

echo ""
echo "==> health http://127.0.0.1:${APP_PORT}/up"
if curl -fsS "http://127.0.0.1:${APP_PORT}/up" >/dev/null; then
    echo "OK — container MCU merespons."
else
    echo "GAGAL — cek: docker compose logs -f app"
    exit 1
fi

if [ -f .env ] && grep -q '^APP_URL=https://puspelkes' .env; then
    echo ""
    echo "==> health lewat nginx (opsional)"
    curl -fsS -H "Host: puspelkes.jakarta.go.id" "http://127.0.0.1/mcuppkp/up" >/dev/null \
        && echo "OK — /mcuppkp/up via nginx host" \
        || echo "LEWATI — snippet nginx belum dipasang atau path berbeda"
fi

echo ""
echo "==> bridge CKG (read-only)"
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan ckg-bridge:verify --warn-only 2>/dev/null \
    || echo "LEWATI — container app tidak jalan atau bridge belum dikonfigurasi"
