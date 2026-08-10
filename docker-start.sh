#!/usr/bin/env bash

set -euo pipefail

cd /app

mkdir -p /run/nginx
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R ug+rwx /app/storage /app/bootstrap/cache

if [ ! -L /app/public/storage ]; then
    su-exec www-data php artisan storage:link >/dev/null 2>&1 || true
fi

pids=()

php-fpm -F &
pids+=("$!")

if [ "${ENABLE_QUEUE_WORKER:-true}" = "true" ]; then
    su-exec www-data php artisan queue:work --sleep=3 --tries=3 --max-time=3600 &
    pids+=("$!")
fi

if [ "${ENABLE_SCHEDULER:-true}" = "true" ]; then
    su-exec www-data php artisan schedule:work --no-interaction &
    pids+=("$!")
fi

nginx -g 'daemon off;' &
pids+=("$!")

shutdown() {
    for pid in "${pids[@]}"; do
        kill -TERM "$pid" 2>/dev/null || true
    done

    wait || true
}

trap shutdown INT TERM

wait -n "${pids[@]}"
status=$?

shutdown

exit "$status"
