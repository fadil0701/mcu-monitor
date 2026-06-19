#!/usr/bin/env bash
# Helper GPG untuk backup/restore database (symmetric / passphrase).

backup_gpg_enabled() {
    local flag="${BACKUP_ENCRYPT:-}"
    if [ -z "$flag" ] && [ -f "${ROOT:-.}/.env" ]; then
        flag="$(grep -E '^BACKUP_ENCRYPT=' "${ROOT}/.env" | tail -1 | cut -d= -f2- | sed 's/\r$//' | tr -d ' \"'"'"'' || true)"
    fi
    [ "$flag" = "1" ] || [ "$flag" = "true" ] || [ "$flag" = "yes" ]
}

backup_gpg_passphrase_file() {
    local from_env="${BACKUP_GPG_PASSPHRASE_FILE:-}"
    if [ -z "$from_env" ] && [ -f "${ROOT:-.}/.env" ]; then
        from_env="$(grep -E '^BACKUP_GPG_PASSPHRASE_FILE=' "${ROOT}/.env" | tail -1 | cut -d= -f2- | sed 's/\r$//' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")"
    fi

    if [ -n "$from_env" ]; then
        if [ ! -f "$from_env" ]; then
            echo "ERROR: BACKUP_GPG_PASSPHRASE_FILE tidak ditemukan: $from_env" >&2
            return 1
        fi
        echo "$from_env"
        return 0
    fi

    if [ -f "${ROOT:-.}/.backup-passphrase" ]; then
        echo "${ROOT}/.backup-passphrase"
        return 0
    fi

    echo "ERROR: Passphrase backup belum diset." >&2
    echo "       Salin deploy/backup-passphrase.example ke .backup-passphrase" >&2
    echo "       atau set BACKUP_GPG_PASSPHRASE_FILE di .env" >&2
    return 1
}

backup_gpg_require_tools() {
    if ! command -v gpg >/dev/null 2>&1; then
        echo "ERROR: gpg belum terpasang." >&2
        echo "       Windows: install Gpg4win (https://www.gpg4win.org/)" >&2
        echo "       Linux:   sudo apt install gnupg" >&2
        return 1
    fi
}

backup_gpg_encrypt() {
    local input="$1"
    local passphrase_file
    passphrase_file="$(backup_gpg_passphrase_file)" || return 1
    backup_gpg_require_tools || return 1

    local output="${input}.gpg"
    echo "==> Enkripsi GPG (AES256)" >&2
    gpg --batch --yes --pinentry-mode loopback \
        --passphrase-file "$passphrase_file" \
        --symmetric --cipher-algo AES256 \
        --output "$output" \
        "$input"

    if [ ! -s "$output" ]; then
        echo "ERROR: Enkripsi GPG gagal — file output kosong." >&2
        rm -f "$output"
        return 1
    fi

    echo "$output"
}

backup_gpg_decrypt_to_stdout() {
    local input="$1"
    local passphrase_file
    passphrase_file="$(backup_gpg_passphrase_file)" || return 1
    backup_gpg_require_tools || return 1

    gpg --batch --yes --pinentry-mode loopback \
        --passphrase-file "$passphrase_file" \
        --decrypt "$input"
}

backup_gpg_stream_plain() {
    local input="$1"

    case "$input" in
        *.sql.gz.gpg|*.gz.gpg)
            backup_gpg_decrypt_to_stdout "$input" | gunzip -c
            ;;
        *.gpg)
            backup_gpg_decrypt_to_stdout "$input"
            ;;
        *.gz)
            gunzip -c "$input"
            ;;
        *.sql)
            cat "$input"
            ;;
        *)
            echo "ERROR: Format backup tidak dikenali (harus .gpg, .gz, atau .sql): $input" >&2
            return 1
            ;;
    esac
}
