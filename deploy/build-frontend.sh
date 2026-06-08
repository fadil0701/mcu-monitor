#!/usr/bin/env bash
# Build Vite di host (lewat container Node) — fallback jika npm ci di Dockerfile gagal.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"

load_proxy_from_env .env

echo "Build frontend MCU (host/container Node)..."

mkdir -p public/build

# shellcheck disable=SC2046
docker run --rm \
    $(docker_proxy_env_args) \
    -v "$ROOT":/app \
    -w /app \
    node:22-bookworm-slim \
    bash -c '
        set -e
        npm ci --no-audit --no-fund
        npm run build
        ls -la public/build/manifest.json
    '

echo "Selesai. Lanjutkan: docker compose -f docker-compose.yml -f docker-compose.prod.yml build app"
