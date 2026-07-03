#!/usr/bin/env bash
# Wrapper bash untuk dev Docker lokal (butuh pwsh).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v pwsh >/dev/null 2>&1; then
  echo "pwsh tidak ditemukan. Install PowerShell atau pakai install-local-pgsql.ps1."
  exit 1
fi

FLAGS=()
if [ "${INIT_ENV:-}" = "1" ]; then FLAGS+=("-InitEnv"); fi
if [ "${SKIP_BUILD:-}" = "1" ]; then FLAGS+=("-SkipBuild"); fi
if [ "${FRESH_ONLY:-}" = "1" ]; then FLAGS+=("-FreshOnly"); fi
if [ "${VERIFY_ONLY:-}" = "1" ]; then FLAGS+=("-VerifyOnly"); fi

pwsh -NoProfile -ExecutionPolicy Bypass -File "./deploy/install-migrate-pgsql.ps1" "${FLAGS[@]}"

