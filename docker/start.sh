#!/bin/sh

set -eu

cd /app

# Railway may mount a persistent volume at storage/app/public. Create the
# framework directories first, then merge the baked-in seed media without
# overwriting anything already on the volume.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ -d /app/seed-storage ]; then
    cp -rn /app/seed-storage/. storage/app/public/ 2>/dev/null || true
fi

# Mounted volumes can be root-owned. Give the runtime user write access before
# migrations/caches run as www-data.
chown -R www-data:www-data storage bootstrap/cache

# Build production caches as the runtime user so the generated files stay
# writable by the web process.
# Migrations can be disabled (e.g. when a migration runs out-of-band as a
# release step) by setting RUN_MIGRATIONS=false in the container environment.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    gosu www-data php artisan migrate --force
fi
gosu www-data php artisan config:cache
gosu www-data php artisan route:cache
gosu www-data php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/sordar.conf
