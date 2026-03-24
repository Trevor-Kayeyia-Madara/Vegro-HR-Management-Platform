#!/bin/sh
set -e

cd /var/www/html

export COMPOSER_CACHE_DIR=/tmp/composer-cache

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force
fi

if [ "${DB_CONNECTION}" = "mysql" ]; then
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
  until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Throwable $e) { exit(1); }"; do
    sleep 2
  done
fi

php artisan storage:link >/dev/null 2>&1 || true

exec "$@"
