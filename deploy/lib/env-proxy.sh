fix_placeholder_proxy_in_env() {
    local env_file="${1:-.env}"
    local changed=0

    [ -f "$env_file" ] || return 0

    if grep -qE '^HTTP_PROXY=.*(PROXY_HOST|:PORT|PROXY|example\.com)' "$env_file" 2>/dev/null; then
        sed -i 's|^HTTP_PROXY=.*|HTTP_PROXY=|' "$env_file"
        changed=1
    fi
    if grep -qE '^HTTPS_PROXY=.*(PROXY_HOST|:PORT|PROXY|example\.com)' "$env_file" 2>/dev/null; then
        sed -i 's|^HTTPS_PROXY=.*|HTTPS_PROXY=|' "$env_file"
        changed=1
    fi

    if [ "$changed" -eq 1 ]; then
        echo "PERINGATAN: placeholder proxy di ${env_file} dikosongkan."
        echo "Jika VM wajib proxy, isi manual di .env — lihat docs/DEPLOY.md"
    fi
}

load_proxy_from_env() {
    local env_file="${1:-.env}"
    [ -f "$env_file" ] || return 0

    fix_placeholder_proxy_in_env "$env_file"

    while IFS= read -r line || [ -n "$line" ]; do
        case "$line" in
            HTTP_PROXY=*|HTTPS_PROXY=*|NO_PROXY=*)
                line="${line%%#*}"
                line="$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
                [ -n "$line" ] || continue
                export "$line"
                ;;
        esac
    done < "$env_file"

    [ -n "${HTTP_PROXY:-}" ] && export http_proxy="$HTTP_PROXY"
    [ -n "${HTTPS_PROXY:-}" ] && export https_proxy="$HTTPS_PROXY"
    [ -n "${NO_PROXY:-}" ] && export no_proxy="$NO_PROXY"

    sanitize_proxy_env
}

sanitize_proxy_env() {
    local proxy="${HTTPS_PROXY:-${HTTP_PROXY:-}}"

    if [ -z "$proxy" ]; then
        return 0
    fi

    case "$proxy" in
        *PROXY_HOST*|*PROXY*|*":PORT"*|*example.com*|*CHANGE_ME*)
            unset HTTP_PROXY HTTPS_PROXY http_proxy https_proxy
            ;;
    esac
}

proxy_is_set() {
    sanitize_proxy_env
    [ -n "${HTTPS_PROXY:-${HTTP_PROXY:-}}" ]
}

docker_proxy_env_args() {
    if ! proxy_is_set; then
        return 0
    fi
    local https="${HTTPS_PROXY:-$HTTP_PROXY}"
    printf '%s ' \
        -e "HTTP_PROXY=${HTTP_PROXY}" \
        -e "HTTPS_PROXY=${https}" \
        -e "NO_PROXY=${NO_PROXY:-}" \
        -e "http_proxy=${HTTP_PROXY}" \
        -e "https_proxy=${https}" \
        -e "no_proxy=${NO_PROXY:-}"
}

compose_prod_args() {
    printf '%s' "-f docker-compose.yml -f docker-compose.prod.yml"
}

generate_app_key_into_env() {
    local env_file="${1:-.env}"
    local key

    key=$(docker run --rm $(docker_proxy_env_args) php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    if grep -q '^APP_KEY=' "$env_file"; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" "$env_file"
    else
        echo "APP_KEY=${key}" >>"$env_file"
    fi
    echo "APP_KEY dibuat (tanpa composer/artisan di host)."
}

app_key_is_set() {
    local env_file="${1:-.env}"
    local value

    value=$(grep '^APP_KEY=' "$env_file" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")
    [ -n "$value" ] && [ "$value" != "base64:" ] && [[ "$value" == base64:* ]]
}
