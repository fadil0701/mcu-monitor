#!/usr/bin/env bash
# Helper GPG untuk backup/restore database (symmetric / passphrase).

backup_gpg_resolve_path() {
    local path="$1"

    if [ -z "$path" ]; then
        return 1
    fi

    case "$path" in
        /*)
            echo "$path"
            ;;
        [A-Za-z]:*|[A-Za-z]:/*)
            echo "$path"
            ;;
        *)
            echo "${ROOT:-.}/${path#./}"
            ;;
    esac
}

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

    local candidates=()
    local configured=""

    if [ -n "$from_env" ]; then
        configured="$(backup_gpg_resolve_path "$from_env")"
        candidates+=("$configured")
    fi
    candidates+=("${ROOT}/.backup-passphrase")
    candidates+=("/etc/mcuppkp/backup.pass")

    local path seen=""
    for path in "${candidates[@]}"; do
        [ -z "$path" ] && continue
        case " $seen " in
            *" $path "*) continue ;;
        esac
        seen="$seen $path"
        if [ -f "$path" ]; then
            if [ -n "$configured" ] && [ "$path" != "$configured" ]; then
                echo "PERINGATAN: BACKUP_GPG_PASSPHRASE_FILE=${configured} tidak ada, memakai ${path}" >&2
            fi
            echo "$path"
            return 0
        fi
    done

    echo "ERROR: File passphrase backup tidak ditemukan." >&2
    if [ -n "$configured" ]; then
        echo "       Dikonfigurasi: ${configured}" >&2
    fi
    echo "       Buat salah satu:" >&2
    echo "         ${ROOT}/.backup-passphrase  (cp deploy/backup-passphrase.example .backup-passphrase && chmod 600 .backup-passphrase)" >&2
    echo "         /etc/mcuppkp/backup.pass   (sudo mkdir -p /etc/mcuppkp && sudo cp deploy/backup-passphrase.example /etc/mcuppkp/backup.pass)" >&2
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
