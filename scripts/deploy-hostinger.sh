#!/usr/bin/env bash
set -euo pipefail

DOMAIN_DIR="$HOME/domains/chachaprime.com"
APP_DIR="$DOMAIN_DIR/laravel_app"
PUBLIC_DIR="$DOMAIN_DIR/public_html"
REPO_URL="https://github.com/chachaprimeofficial-max/c-p.git"

mkdir -p "$DOMAIN_DIR" "$PUBLIC_DIR"

if [ ! -d "$APP_DIR/.git" ]; then
    if [ -e "$APP_DIR" ]; then
        echo "ERROR: $APP_DIR exists but is not a Git checkout. Move it aside after checking its contents."
        exit 1
    fi
    git clone "$REPO_URL" "$APP_DIR"
else
    git -C "$APP_DIR" pull --ff-only origin main
fi

cd "$APP_DIR"
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created $APP_DIR/.env from .env.example."
    echo "Edit .env with your rotated database, SMTP, admin and API credentials, then run this script again."
    exit 2
fi

if ! grep -Eq '^DB_DATABASE=.+$' .env || ! grep -Eq '^DB_USERNAME=.+$' .env; then
    echo "ERROR: Configure DB_DATABASE and DB_USERNAME in $APP_DIR/.env before deployment."
    exit 3
fi

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if grep -Eq '^APP_KEY=$' .env; then
    php artisan key:generate --force
fi

mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

find "$PUBLIC_DIR" -mindepth 1 -maxdepth 1 ! -name '.well-known' -exec rm -rf {} +
cp -a "$APP_DIR/public/." "$PUBLIC_DIR/"
cat > "$PUBLIC_DIR/index.php" <<'PHP'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../laravel_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../laravel_app/vendor/autoload.php';

$app = require_once __DIR__.'/../laravel_app/bootstrap/app.php';

$app->handleRequest(Request::capture());
PHP

rm -f "$PUBLIC_DIR/storage"
ln -s "$APP_DIR/storage/app/public" "$PUBLIC_DIR/storage"

chmod -R ug+rwX storage bootstrap/cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment files are installed."
echo "Check https://chachaprime.com and https://chachaprime.com/admin/login."
echo "Confirm PHP 8.2+, PDO MySQL, fileinfo, mbstring and GD are enabled in Hostinger."
