#!/usr/bin/env bash
# Deteksi & jalankan mysqldump/mysql: Docker (VM) atau Laragon/lokal (tanpa Docker).

backup_mysql_read_dotenv() {
    local key="$1"
    local default="${2:-}"

    if [ ! -f "${ROOT:-.}/.env" ]; then
        echo "$default"
        return
    fi

    local val
    val="$(grep -E "^${key}=" "${ROOT}/.env" | tail -1 | cut -d= -f2- | sed 's/\r$//' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")"
    echo "${val:-$default}"
}

backup_mysql_docker_running() {
    command -v docker >/dev/null 2>&1 \
        && docker compose ps --status running --services 2>/dev/null | grep -qx mysql
}

backup_mysql_detect_mode() {
    local forced="${BACKUP_MODE:-$(backup_mysql_read_dotenv BACKUP_MODE auto)}"
    forced="$(echo "$forced" | tr '[:upper:]' '[:lower:]')"

    case "$forced" in
        docker)
            if backup_mysql_docker_running; then
                echo docker
                return 0
            fi
            echo "ERROR: BACKUP_MODE=docker tetapi container mysql tidak berjalan." >&2
            return 1
            ;;
        local|laragon)
            echo local
            return 0
            ;;
        auto)
            if backup_mysql_docker_running; then
                echo docker
                return 0
            fi
            echo local
            return 0
            ;;
        *)
            echo "ERROR: BACKUP_MODE tidak valid: $forced (gunakan auto, docker, atau local)" >&2
            return 1
            ;;
    esac
}

backup_mysql_find_bin() {
    local name="$1"
    local candidate

    if command -v "$name" >/dev/null 2>&1; then
        command -v "$name"
        return 0
    fi

    local laragon_root="${LARAGON_ROOT:-$(backup_mysql_read_dotenv LARAGON_ROOT "")}"
    if [ -z "$laragon_root" ] && [ -d "/c/laragon" ]; then
        laragon_root="/c/laragon"
    fi
    if [ -z "$laragon_root" ] && [ -d "C:/laragon" ]; then
        laragon_root="C:/laragon"
    fi
    if [ -z "$laragon_root" ] && [ -d "D:/laragon" ]; then
        laragon_root="D:/laragon"
    fi

    if [ -n "$laragon_root" ] && [ -d "$laragon_root/bin/mysql" ]; then
        candidate="$(find "$laragon_root/bin/mysql" -type f \( -name "${name}.exe" -o -name "$name" \) 2>/dev/null | head -1)"
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then
            echo "$candidate"
            return 0
        fi
    fi

    if [ -n "${BACKUP_MYSQL_BIN_DIR:-}" ]; then
        if [ -x "${BACKUP_MYSQL_BIN_DIR}/${name}.exe" ]; then
            echo "${BACKUP_MYSQL_BIN_DIR}/${name}.exe"
            return 0
        fi
        if [ -x "${BACKUP_MYSQL_BIN_DIR}/${name}" ]; then
            echo "${BACKUP_MYSQL_BIN_DIR}/${name}"
            return 0
        fi
    fi

    echo "ERROR: Binary '$name' tidak ditemukan." >&2
    echo "       Pasang MySQL client atau set LARAGON_ROOT / BACKUP_MYSQL_BIN_DIR." >&2
    return 1
}

backup_mysql_load_local_config() {
    BACKUP_DB_CONNECTION="$(backup_mysql_read_dotenv DB_CONNECTION mysql)"
    BACKUP_DB_HOST="$(backup_mysql_read_dotenv DB_HOST 127.0.0.1)"
    BACKUP_DB_PORT="$(backup_mysql_read_dotenv DB_PORT 3306)"
    BACKUP_DB_DATABASE="$(backup_mysql_read_dotenv DB_DATABASE monitoring_mcu)"
    BACKUP_DB_USER="$(backup_mysql_read_dotenv DB_USERNAME root)"
    BACKUP_DB_PASS="$(backup_mysql_read_dotenv DB_PASSWORD "")"

    if [ "$BACKUP_DB_CONNECTION" != "mysql" ]; then
        echo "ERROR: Mode local membutuhkan DB_CONNECTION=mysql di .env (saat ini: ${BACKUP_DB_CONNECTION})." >&2
        return 1
    fi

    if [ -z "$BACKUP_DB_DATABASE" ]; then
        echo "ERROR: DB_DATABASE kosong di .env" >&2
        return 1
    fi
}

backup_mysql_dump() {
    local output_file="$1"
    local mode
    mode="$(backup_mysql_detect_mode)" || return 1

    case "$mode" in
        docker)
            local root_pass
            root_pass="$(backup_mysql_read_dotenv MYSQL_ROOT_PASSWORD)"
            local db_name
            db_name="$(backup_mysql_read_dotenv DB_DATABASE monitoring_mcu)"
            if [ -z "$root_pass" ]; then
                echo "ERROR: MYSQL_ROOT_PASSWORD kosong di .env (mode docker)." >&2
                return 1
            fi
            echo "==> mysqldump (Docker container mysql)"
            docker compose exec -T mysql mysqldump \
                --single-transaction \
                --routines \
                --triggers \
                --events \
                --default-character-set=utf8mb4 \
                -u root \
                -p"${root_pass}" \
                "${db_name}" > "$output_file"
            ;;
        local)
            backup_mysql_load_local_config || return 1
            local mysqldump_bin
            mysqldump_bin="$(backup_mysql_find_bin mysqldump)" || return 1
            echo "==> mysqldump (Laragon / MySQL lokal)"
            echo "    Host: ${BACKUP_DB_HOST}:${BACKUP_DB_PORT} · DB: ${BACKUP_DB_DATABASE} · User: ${BACKUP_DB_USER}"
            if [ -n "$BACKUP_DB_PASS" ]; then
                MYSQL_PWD="$BACKUP_DB_PASS" "$mysqldump_bin" \
                    --host="$BACKUP_DB_HOST" \
                    --port="$BACKUP_DB_PORT" \
                    --user="$BACKUP_DB_USER" \
                    --single-transaction \
                    --routines \
                    --triggers \
                    --events \
                    --default-character-set=utf8mb4 \
                    "$BACKUP_DB_DATABASE" > "$output_file"
            else
                "$mysqldump_bin" \
                    --host="$BACKUP_DB_HOST" \
                    --port="$BACKUP_DB_PORT" \
                    --user="$BACKUP_DB_USER" \
                    --single-transaction \
                    --routines \
                    --triggers \
                    --events \
                    --default-character-set=utf8mb4 \
                    "$BACKUP_DB_DATABASE" > "$output_file"
            fi
            ;;
    esac
}

backup_mysql_restore_stdin() {
    local mode
    mode="$(backup_mysql_detect_mode)" || return 1

    case "$mode" in
        docker)
            local root_pass
            root_pass="$(backup_mysql_read_dotenv MYSQL_ROOT_PASSWORD)"
            local db_name
            db_name="$(backup_mysql_read_dotenv DB_DATABASE monitoring_mcu)"
            if [ -z "$root_pass" ]; then
                echo "ERROR: MYSQL_ROOT_PASSWORD kosong di .env (mode docker)." >&2
                return 1
            fi
            docker compose exec -T mysql mysql \
                -u root \
                -p"${root_pass}" \
                "${db_name}"
            ;;
        local)
            backup_mysql_load_local_config || return 1
            local mysql_bin
            mysql_bin="$(backup_mysql_find_bin mysql)" || return 1
            if [ -n "$BACKUP_DB_PASS" ]; then
                MYSQL_PWD="$BACKUP_DB_PASS" "$mysql_bin" \
                    --host="$BACKUP_DB_HOST" \
                    --port="$BACKUP_DB_PORT" \
                    --user="$BACKUP_DB_USER" \
                    "$BACKUP_DB_DATABASE"
            else
                "$mysql_bin" \
                    --host="$BACKUP_DB_HOST" \
                    --port="$BACKUP_DB_PORT" \
                    --user="$BACKUP_DB_USER" \
                    "$BACKUP_DB_DATABASE"
            fi
            ;;
    esac
}

backup_mysql_mode_label() {
    local mode
    mode="$(backup_mysql_detect_mode)" || return 1
    case "$mode" in
        docker) echo "Docker (container mysql)" ;;
        local)
            backup_mysql_load_local_config || return 1
            echo "Laragon / MySQL lokal (${BACKUP_DB_HOST}:${BACKUP_DB_PORT})"
            ;;
    esac
}
