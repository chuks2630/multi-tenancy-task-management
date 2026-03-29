#!/bin/sh
set -e

echo "Testing DB connection..."
php artisan migrate:status --no-interaction 2>&1 | head -5 || true

echo "Running migrations..."
php artisan migrate --force
php artisan db:seed --class=PlanSeeder --force

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting php-fpm..."
php-fpm -D

echo "Starting nginx..."
exec nginx -g "daemon off;"
```