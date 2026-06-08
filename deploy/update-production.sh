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
# shellcheck disable=SC2046
docker compose $(compose_prod_args) build app
# shellcheck disable=SC2046
docker compose $(compose_prod_args) up -d
# shellcheck disable=SC2046
docker compose $(compose_prod_args) restart app queue scheduler

echo "==> Laravel migrate & cache"
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan migrate --force
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan optimize:clear
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan config:cache
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan route:cache
# shellcheck disable=SC2046
docker compose $(compose_prod_args) exec -T app php artisan view:cache

echo ""
echo "==> Selesai. Verifikasi:"
echo "    bash deploy/verify.sh"
echo ""
echo "Bootstrap admin (sekali, jika belum ada):"
echo "  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan user:create-admin --from-env"
