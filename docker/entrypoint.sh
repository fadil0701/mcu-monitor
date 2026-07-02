#!/bin/sh
set -e

cd /var/www/html

fix_permissions() {
    mkdir -p storage/backups/database storage/logs 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
}

wait_for_database() {
    echo "Waiting for database (DB_CONNECTION=${DB_CONNECTION:-mysql})..."
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
        if [ "${VIEW_CLEAR_ON_BOOT:-1}" = "1" ]; then
            rm -f storage/framework/views/*.php 2>/dev/null || true
            php artisan view:clear --no-interaction 2>/dev/null || true
        else
            php artisan view:cache --no-interaction
        fi
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
