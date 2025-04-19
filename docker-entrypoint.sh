#!/bin/sh
set -e

# 1) Ensure write perms
chmod -R 0777 storage bootstrap/cache

# 2) Wait for MySQL to accept connections
echo "⏳ Waiting for MySQL at $DB_HOST:$DB_PORT …"
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done
echo "✅ MySQL is up — continuing…"

# 3) Run your migrations
php artisan migrate --force

# 4) (Optional) Install & migrate Telescope
php artisan telescope:install
php artisan vendor:publish --tag=telescope-migrations --force
php artisan migrate --force

# 5) Finally launch PHP‑FPM
exec php-fpm
