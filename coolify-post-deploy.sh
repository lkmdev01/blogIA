#!/usr/bin/env bash

set -euo pipefail

cd /app

php artisan package:discover --ansi
php artisan migrate --force --graceful --ansi
php artisan optimize:clear --ansi
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize --ansi
