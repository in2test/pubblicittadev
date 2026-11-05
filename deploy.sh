#!/usr/bin/env bash
set -euo pipefail

BRANCH="${1:-production}"
REMOTE="${REMOTE:-origin}"

cd "$(dirname "$0")"

echo "==> Putting application into maintenance mode"
php artisan down --retry=120 --refresh=120

echo "==> Updating code from ${REMOTE}/${BRANCH}"
git pull "${REMOTE}" "${BRANCH}"

echo "==> Preparing the maintenance page"
php artisan down --render='errors::503' --retry=120 --refresh=120

echo "==> Installing production dependencies"
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
else
    rm -rf vendor
    php -d error_reporting=0 composer.phar install --no-dev --optimize-autoloader
    php -d error_reporting=0 composer.phar dump-autoload --optimize
fi

echo "==> Running migrations"
php artisan migrate --force

echo "==> Clearing cached files"
php artisan optimize:clear

echo "==> Rebuilding caches"
php artisan optimize

echo "==> Bringing application back up"
php artisan up

echo "==> Deployment complete"
