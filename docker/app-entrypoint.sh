#!/bin/sh
set -eu

cd /var/www/html

git config --global --add safe.directory /var/www/html

if [ ! -f artisan ]; then
    echo "artisan not found in /var/www/html" >&2
    exit 1
fi

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "${1:-}" = "php" ] && [ "${2:-}" = "artisan" ] && [ "${3:-}" = "serve" ]; then
    composer_hash="$(sha256sum composer.lock | awk '{print $1}')"
    installed_hash="$(cat vendor/.composer-lock.sha256 2>/dev/null || true)"

    if [ ! -f vendor/autoload.php ] || [ "$composer_hash" != "$installed_hash" ]; then
        echo "Synchronizing Composer dependencies..."
        composer install --no-interaction --prefer-dist
        printf '%s' "$composer_hash" > vendor/.composer-lock.sha256
    fi

    rm -f bootstrap/cache/*.php storage/framework/views/*.php
    php artisan migrate --force
    php artisan optimize:clear
    php artisan storage:link --force
fi

exec "$@"
