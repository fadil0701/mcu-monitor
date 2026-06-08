#!/usr/bin/env bash
set -euo pipefail

SNIPPET_INCLUDE='include /etc/nginx/snippets/mcuppkp.conf;'
SNIPPET_FILE='/etc/nginx/snippets/mcuppkp.conf'

if [ ! -f "$SNIPPET_FILE" ]; then
    echo "ERROR: $SNIPPET_FILE tidak ada. Jalankan dulu:"
    echo "  bash deploy/install-nginx-snippet.sh"
    exit 1
fi

if grep -rq 'mcuppkp\.conf' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null; then
    echo "Sudah ada include mcuppkp di config aktif:"
    grep -rn 'mcuppkp' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null || true
    exit 0
fi

mapfile -t CANDIDATES < <(
    grep -rl 'sikerja\.conf' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null \
        || grep -rl 'puspelkes\.jakarta\.go\.id' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null \
        || true
)

if [ "${#CANDIDATES[@]}" -eq 0 ]; then
    echo "Tidak menemukan vhost puspelkes di sites-enabled/conf.d."
    echo "Cari manual:"
    echo "  sudo nginx -T 2>/dev/null | grep -n 'server_name.*puspelkes' | head -10"
    echo "  ls -la /etc/nginx/sites-enabled/"
    exit 1
fi

for TARGET in "${CANDIDATES[@]}"; do
    echo "Patch: $TARGET"
    sudo cp "$TARGET" "${TARGET}.bak-$(date +%Y%m%d%H%M%S)"

    sudo awk -v inc="    ${SNIPPET_INCLUDE}" '
        /include[[:space:]]+\/etc\/nginx\/snippets\/sikerja\.conf;/ && !done {
            print
            print inc
            done=1
            next
        }
        { print }
    ' "$TARGET" | sudo tee "${TARGET}.tmp" >/dev/null
    sudo mv "${TARGET}.tmp" "$TARGET"
done

echo ""
echo "Ditambahkan: $SNIPPET_INCLUDE"
grep -rn 'mcuppkp\|sikerja' /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null | head -15

sudo nginx -t
sudo systemctl reload nginx

echo ""
echo "Uji:"
echo "  curl -sI http://127.0.0.1/mcuppkp/ | head -5"
echo "  bash deploy/verify.sh"
