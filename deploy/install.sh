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

load_proxy_from_env .env

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "Menghasilkan APP_KEY..."
    # shellcheck disable=SC2046
    docker run --rm $(docker_proxy_env_args) -v "$ROOT":/app -w /app php:8.3-cli php artisan key:generate --force 2>/dev/null \
        || php artisan key:generate --force
fi

echo "Memeriksa jaringan untuk build Docker..."
if proxy_is_set; then
    echo "Proxy aktif: HTTPS_PROXY=${HTTPS_PROXY:-$HTTP_PROXY}"
else
    echo "PERINGATAN: HTTP_PROXY/HTTPS_PROXY belum diset di .env."
    echo "Jika VM wajib lewat proxy, isi dulu lalu jalankan ulang install.sh"
fi

if command -v curl >/dev/null 2>&1; then
    if ! curl -fsS --connect-timeout 20 -o /dev/null https://deb.debian.org; then
        echo "PERINGATAN: tidak bisa akses https://deb.debian.org (build mungkin gagal tanpa proxy)."
    fi
fi

echo "Membangun image dan menjalankan stack produksi..."
export DOCKER_BUILDKIT=1
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
