load_proxy_from_env() {
    local env_file="${1:-.env}"
    [ -f "$env_file" ] || return 0

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
}

proxy_is_set() {
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
