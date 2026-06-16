#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
chmod +x deploy/*.sh 2>/dev/null || true
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"

load_proxy_from_env .env

BRANCH="${DEPLOY_BRANCH:-main}"

echo "==> Monitoring MCU PPKP — update production"
echo "    Branch: $BRANCH"
echo "    Path:   $ROOT"

if [ -d .git ]; then
    echo "==> git pull"
    if [ -n "$(git status --porcelain deploy/ 2>/dev/null)" ]; then
        echo "    Membuang perubahan lokal di deploy/ (sinkron dengan repo)…"
        git checkout -- deploy/ 2>/dev/null || git restore --source=HEAD --staged --worktree deploy/ 2>/dev/null || true
    fi
    git fetch origin
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
fi

echo "==> Build frontend (Vite)"
chmod +x deploy/build-frontend.sh
./deploy/build-frontend.sh

echo "==> Docker build & up"
export DOCKER_BUILDKIT=1
export COMPOSE_PARALLEL_LIMIT=1
BUILD_ARGS=()
if [ "${DOCKER_BUILD_NO_CACHE:-}" = "1" ]; then
    BUILD_ARGS+=(--no-cache)
    echo "    (DOCKER_BUILD_NO_CACHE=1 — rebuild penuh tanpa cache layer)"
fi
# shellcheck disable=SC2046
docker compose $(compose_prod_args) build "${BUILD_ARGS[@]}" app
# shellcheck disable=SC2046
docker compose $(compose_prod_args) up -d
# shellcheck disable=SC2046
docker compose $(compose_prod_args) restart app queue scheduler

echo "==> Laravel migrate & cache"
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan migrate --force
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan db:seed --class=InstansiPemprovDkiSeeder --force
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan optimize:clear
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan config:cache
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan route:cache
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan view:cache

echo ""
echo "==> Verifikasi aset CSS di container"
# shellcheck disable=SC2046
CSS_BYTES=$(docker compose $(compose_prod_args) exec -T app stat -c%s /var/www/html/public/assets/css/mcu-admin.css 2>/dev/null || echo "0")
echo "    public/assets/css/mcu-admin.css = ${CSS_BYTES} bytes (di dalam image app)"
if [ "${CSS_BYTES}" = "0" ] || [ ! -f public/assets/css/mcu-admin.css ]; then
    echo "    PERINGATAN: file CSS tidak ditemukan — cek git pull dan rebuild."
else
    HOST_BYTES=$(stat -c%s public/assets/css/mcu-admin.css 2>/dev/null || stat -f%z public/assets/css/mcu-admin.css 2>/dev/null || echo "?")
    echo "    di host setelah pull = ${HOST_BYTES} bytes"
    if [ "${HOST_BYTES}" != "?" ] && [ "${CSS_BYTES}" != "${HOST_BYTES}" ]; then
        echo "    PERINGATAN: ukuran CSS host vs container berbeda — jalankan ulang dengan DOCKER_BUILD_NO_CACHE=1 bash deploy/update-production.sh"
    fi
fi

echo ""
echo "==> bridge CKG (read-only)"
# shellcheck disable=SC2046
if ! docker compose $(compose_prod_args) exec -T app php artisan ckg-bridge:verify --warn-only; then
    echo ""
    echo "PERINGATAN: Bridge CKG gagal setelah update."
    echo "  - Jangan timpa .env dari .env.production.example (setting bridge ada di DB + .env)."
    echo "  - Perbaiki: docker compose $(compose_prod_args) exec app php artisan ckg-bridge:configure --base-url=http://<gateway>:9006 --api-key=... --activate --test"
fi

echo ""
echo "==> Selesai. Verifikasi:"
echo "    bash deploy/verify.sh"
echo ""
echo "Bootstrap admin (sekali, jika belum ada):"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env"
