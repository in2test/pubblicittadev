#!/usr/bin/env bash
set -euo pipefail

BRANCH="${1:-production}"
REMOTE="${REMOTE:-origin}"

cd "$(dirname "$0")"

echo "==> Putting application into maintenance mode"
php artisan down

echo "==> Updating code from ${REMOTE}/${BRANCH}"
git pull "${REMOTE}" "${BRANCH}"

echo "==> Installing production dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Running migrations"
php artisan migrate --force

echo "==> Clearing cached files"
php artisan optimize:clear

echo "==> Rebuilding caches"
php artisan optimize

echo "==> Bringing application back up"
php artisan up

echo "==> Deployment complete"
