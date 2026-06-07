#!/bin/sh
set -e

cd /var/www/html

fix_permissions() {
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
}

wait_for_database() {
    if [ -z "$DB_HOST" ]; then
        return 0
    fi

    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    i=0
    while [ "$i" -lt 60 ]; do
        if php artisan db:monitor --no-interaction >/dev/null 2>&1; then
            echo "Database is ready."
            return 0
        fi
        i=$((i + 1))
        sleep 2
    done

    echo "Database connection timed out." >&2
    exit 1
}

ensure_app_key() {
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
        echo "ERROR: APP_KEY belum diisi di file .env" >&2
        echo "Jalankan di mesin lokal: php artisan key:generate --show" >&2
        echo "Lalu salin nilainya ke APP_KEY= di .env sebelum docker compose up." >&2
        exit 1
    fi
}

run_setup() {
    ensure_app_key

    php artisan package:discover --ansi --no-interaction 2>/dev/null || true

    if [ "$RUN_MIGRATIONS" = "true" ]; then
        php artisan migrate --force --no-interaction
    fi

    php artisan storage:link --force --no-interaction 2>/dev/null || true

    if [ "$APP_ENV" = "production" ]; then
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
    fi

    fix_permissions
}

wait_for_database

case "$1" in
    /usr/bin/supervisord|php-fpm)
        run_setup
        ;;
esac

exec "$@"
