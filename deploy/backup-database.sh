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

DB_CONN="$(grep -E '^DB_CONNECTION=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d ' \"'"'"'')"
if [ "$DB_CONN" = "pgsql" ]; then
    echo "==> Monitoring MCU — backup PostgreSQL (pg_dump via artisan)"
    if command -v docker >/dev/null 2>&1 && docker compose $(compose_prod_args) ps --status running -q app 2>/dev/null | grep -q .; then
        docker compose $(compose_prod_args) exec -T app php artisan mcu:backup-database
        exit $?
    fi
    php artisan mcu:backup-database
    exit $?
fi

BACKUP_DIR="${BACKUP_DIR:-$ROOT/storage/backups/database}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
COMPRESS="${BACKUP_COMPRESS:-1}"
KEEP_PLAIN="${BACKUP_KEEP_PLAIN:-0}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

DB_DATABASE="$(backup_mysql_read_dotenv DB_DATABASE monitoring_mcu)"
ENCRYPT="$(backup_gpg_enabled && echo 1 || echo 0)"
MYSQL_MODE="$(backup_mysql_detect_mode)"
MYSQL_MODE_LABEL="$(backup_mysql_mode_label)"

echo "==> Monitoring MCU — backup database MySQL"
echo "    Path:     $ROOT"
echo "    Database: $DB_DATABASE"
echo "    Sumber:   $MYSQL_MODE_LABEL"
echo "    Output:   $BACKUP_DIR"
echo "    Retensi:  ${RETENTION_DAYS} hari"
echo "    Enkripsi: $([ "$ENCRYPT" = "1" ] && echo 'GPG AES256 (aktif)' || echo 'nonaktif (set BACKUP_ENCRYPT=1)')"

if [ ! -f .env ]; then
    echo "ERROR: File .env tidak ditemukan."
    exit 1
fi

if [ "$ENCRYPT" = "1" ]; then
    backup_gpg_passphrase_file >/dev/null || exit 1
    backup_gpg_require_tools || exit 1
fi

mkdir -p "$BACKUP_DIR" 2>/dev/null || true
if [ ! -d "$BACKUP_DIR" ] || [ ! -w "$BACKUP_DIR" ]; then
    if [ "$MYSQL_MODE" = "docker" ] && command -v docker >/dev/null 2>&1; then
        echo "PERINGATAN: Tidak bisa menulis ke ${BACKUP_DIR} (user: $(whoami)) — backup via container app..." >&2
        docker compose $(compose_prod_args) exec -T app php artisan mcu:backup-database
        exit $?
    fi

    echo "ERROR: Folder backup tidak bisa ditulis: ${BACKUP_DIR}" >&2
    echo "       Perbaiki izin di server:" >&2
    echo "         sudo mkdir -p ${BACKUP_DIR}" >&2
    echo "         sudo chown -R \$(whoami):\$(id -gn) storage/backups" >&2
    echo "       Atau jalankan backup lewat container:" >&2
    echo "         docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan mcu:backup-database" >&2
    exit 1
fi

BASE_NAME="backup-${DB_DATABASE}-${TIMESTAMP}"
SQL_PATH="${BACKUP_DIR}/${BASE_NAME}.sql"
FINAL_PATH="$SQL_PATH"

backup_mysql_dump "$SQL_PATH"

if [ ! -s "$SQL_PATH" ]; then
    echo "ERROR: File backup kosong — dump gagal."
    rm -f "$SQL_PATH"
    exit 1
fi

BYTES="$(wc -c < "$SQL_PATH" | tr -d ' ')"
echo "    Dump SQL: $(numfmt --to=iec-i --suffix=B "$BYTES" 2>/dev/null || echo "${BYTES} bytes")"

if [ "$COMPRESS" = "1" ]; then
    echo "==> Kompresi gzip"
    gzip -f "$SQL_PATH"
    FINAL_PATH="${SQL_PATH}.gz"
    BYTES="$(wc -c < "$FINAL_PATH" | tr -d ' ')"
    echo "    Arsip: $(numfmt --to=iec-i --suffix=B "$BYTES" 2>/dev/null || echo "${BYTES} bytes")"
fi

if [ "$ENCRYPT" = "1" ]; then
    PLAIN_BEFORE_ENCRYPT="$FINAL_PATH"
    FINAL_PATH="$(backup_gpg_encrypt "$FINAL_PATH")"
    BYTES="$(wc -c < "$FINAL_PATH" | tr -d ' ')"
    echo "    Terenkripsi: $(numfmt --to=iec-i --suffix=B "$BYTES" 2>/dev/null || echo "${BYTES} bytes")"
    if [ "$KEEP_PLAIN" != "1" ]; then
        rm -f "$PLAIN_BEFORE_ENCRYPT"
        echo "    File plain dihapus (BACKUP_KEEP_PLAIN=1 untuk menyimpan keduanya)"
    fi
fi

if [ "$RETENTION_DAYS" -gt 0 ] 2>/dev/null; then
    echo "==> Hapus backup lebih lama dari ${RETENTION_DAYS} hari"
    find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'backup-*.sql' -o -name 'backup-*.sql.gz' -o -name 'backup-*.sql.gz.gpg' -o -name 'backup-*.gpg' \) -mtime +"${RETENTION_DAYS}" -print -delete || true
fi

echo ""
echo "==> Selesai."
echo "    File: $FINAL_PATH"
echo ""
echo "Verifikasi decrypt (uji lokal):"
echo "    ./deploy/restore-database.sh --verify \"$FINAL_PATH\""
echo ""
echo "Restore penuh:"
echo "    ./deploy/restore-database.sh \"$FINAL_PATH\""
echo ""
echo "Backup terbaru:"
ls -lt "$BACKUP_DIR"/backup-* 2>/dev/null | head -5 || true
