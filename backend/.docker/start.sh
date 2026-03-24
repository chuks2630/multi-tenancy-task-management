#!/bin/sh
set -e

echo "Testing DB connection..."
php artisan db:show --no-interaction || {
    echo "❌ DB connection failed. Check your env vars."
    exit 1
}

echo "Running migrations..."
php artisan migrate:fresh --force

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting php-fpm..."
php-fpm -D

echo "Starting nginx..."
exec nginx -g "daemon off;"
```