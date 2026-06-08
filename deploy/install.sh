#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker belum terpasang. Pasang Docker Engine + Compose plugin terlebih dahulu."
    exit 1
fi

if [ ! -f .env ]; then
    cp .env.production.example .env
    echo "File .env dibuat dari .env.production.example — edit DB_PASSWORD, MYSQL_ROOT_PASSWORD, APP_KEY, SUPER_ADMIN_*."
fi

fix_placeholder_proxy_in_env .env
load_proxy_from_env .env

if ! proxy_is_set; then
    unset HTTP_PROXY HTTPS_PROXY http_proxy https_proxy 2>/dev/null || true
fi

if ! app_key_is_set .env; then
    echo "Menghasilkan APP_KEY..."
    generate_app_key_into_env .env
fi

echo "Memeriksa jaringan untuk build Docker..."
if proxy_is_set; then
    echo "Proxy aktif: HTTPS_PROXY=${HTTPS_PROXY:-$HTTP_PROXY}"
else
    echo "Proxy tidak dipakai (HTTP_PROXY/HTTPS_PROXY kosong atau placeholder diabaikan)."
    echo "Jika VM wajib lewat proxy, isi nilai asli di .env — contoh: HTTP_PROXY=http://10.15.3.20:80"
fi

if command -v curl >/dev/null 2>&1; then
    if ! curl -fsS --connect-timeout 20 -o /dev/null https://deb.debian.org; then
        echo "PERINGATAN: tidak bisa akses https://deb.debian.org (build mungkin gagal tanpa proxy)."
    fi
fi

echo "Membangun image dan menjalankan stack produksi..."
export DOCKER_BUILDKIT=1
export COMPOSE_PARALLEL_LIMIT=1
# shellcheck disable=SC2046
docker compose $(compose_prod_args) build app
# shellcheck disable=SC2046
docker compose $(compose_prod_args) up -d

echo ""
echo "Selesai. Cek status:"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml ps"
echo ""
APP_PORT="$(grep '^APP_PORT=' .env 2>/dev/null | head -1 | cut -d= -f2- || echo 9003)"
echo "Aplikasi (LAN): http://$(hostname -I 2>/dev/null | awk '{print $1}' || echo 127.0.0.1):${APP_PORT}/"
echo ""
echo "Buat super admin (sekali):"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env"
echo ""
echo "Verifikasi:"
echo "  ./deploy/verify.sh"
