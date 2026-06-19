#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
# shellcheck source=lib/env-proxy.sh
source "$ROOT/deploy/lib/env-proxy.sh"
# shellcheck source=lib/backup-gpg.sh
source "$ROOT/deploy/lib/backup-gpg.sh"
# shellcheck source=lib/backup-mysql.sh
source "$ROOT/deploy/lib/backup-mysql.sh"

load_proxy_from_env .env

usage() {
    cat <<'EOF'
Monitoring MCU — restore / verifikasi backup database

Usage:
  ./deploy/restore-database.sh --verify <file-backup>
  ./deploy/restore-database.sh <file-backup>

  --verify   Hanya uji decrypt/decompress (tidak menulis ke MySQL)
  --list     Tampilkan 10 backup terbaru

Contoh:
  ./deploy/restore-database.sh --verify storage/backups/database/backup-monitoring_mcu-20260524.sql.gz.gpg
  ./deploy/restore-database.sh storage/backups/database/backup-monitoring_mcu-20260524.sql.gz

Passphrase: .backup-passphrase atau BACKUP_GPG_PASSPHRASE_FILE di .env
EOF
}

DB_DATABASE="$(backup_mysql_read_dotenv DB_DATABASE monitoring_mcu)"
BACKUP_DIR="${BACKUP_DIR:-$ROOT/storage/backups/database}"

VERIFY=0
BACKUP_FILE=""

while [ $# -gt 0 ]; do
    case "$1" in
        -h|--help)
            usage
            exit 0
            ;;
        --verify)
            VERIFY=1
            shift
            ;;
        --list)
            ls -lt "$BACKUP_DIR"/backup-* 2>/dev/null | head -10 || echo "Belum ada backup di $BACKUP_DIR"
            exit 0
            ;;
        *)
            BACKUP_FILE="$1"
            shift
            ;;
    esac
done

if [ -z "$BACKUP_FILE" ]; then
    usage
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: File tidak ditemukan: $BACKUP_FILE"
    exit 1
fi

echo "==> Monitoring MCU — restore database"
echo "    File:     $BACKUP_FILE"
echo "    Database: $DB_DATABASE"
echo "    Sumber:   $(backup_mysql_mode_label)"
echo "    Mode:     $([ "$VERIFY" = "1" ] && echo 'verify only' || echo 'restore ke MySQL')"

if [ "$VERIFY" = "1" ]; then
    echo "==> Verifikasi decrypt/decompress…"
    sample="$(backup_gpg_stream_plain "$BACKUP_FILE" | head -n 5 || true)"
    if printf '%s\n' "$sample" | grep -qE '^(-- |/\*|CREATE|INSERT|DROP)'; then
        echo "OK — isi terlihat seperti dump SQL MySQL."
    else
        echo "PERINGATAN: decrypt berhasil tetapi baris awal tidak seperti dump SQL. Periksa passphrase/file."
        exit 1
    fi
    echo "    (5 baris pertama dump sudah diperiksa)"
    exit 0
fi

if [ "${BACKUP_RESTORE_YES:-}" != "1" ]; then
    echo ""
    echo "PERINGATAN: Restore akan MENIMPA data di database '${DB_DATABASE}'."
    read -r -p "Ketik ya untuk lanjut: " confirm
    if [ "$confirm" != "ya" ]; then
        echo "Dibatalkan."
        exit 0
    fi
fi

echo "==> Restore ke MySQL…"
backup_gpg_stream_plain "$BACKUP_FILE" | backup_mysql_restore_stdin

echo "==> Selesai restore."
