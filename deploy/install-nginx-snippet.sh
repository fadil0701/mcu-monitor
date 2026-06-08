#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/deploy/nginx-mcuppkp-portal-snippet.conf"
DEST="/etc/nginx/snippets/mcuppkp.conf"

if [ ! -f "$SRC" ]; then
    echo "ERROR: $SRC tidak ditemukan"
    exit 1
fi

echo "Menyalin $SRC -> $DEST"
sudo cp "$SRC" "$DEST"
sudo chmod 644 "$DEST"

echo ""
echo "Langkah berikutnya — EDIT file config nginx portal (bukan di terminal bash):"
echo "  sudo nano /etc/nginx/sites-available/default"
echo "  # atau file vhost puspelkes yang dipakai VM"
echo ""
echo "Di dalam blok server { ... }, SEBELUM baris 'location /', tambahkan:"
echo "  include /etc/nginx/snippets/mcuppkp.conf;"
echo ""
echo "Lalu uji dan reload:"
echo "  sudo nginx -t && sudo systemctl reload nginx"
echo "  curl -I http://127.0.0.1/mcuppkp/"
