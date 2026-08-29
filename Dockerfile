FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git \
    && docker-php-ext-install pdo pdo_mysql zip opcache

# The cli image ships opcache disabled and `php artisan serve` runs on the
# CLI SAPI, so enable it explicitly for both SAPIs. Timestamp validation
# stays on (safe default for volume-mounted deployments).
RUN printf 'opcache.enable=1\nopcache.enable_cli=1\nopcache.memory_consumption=128\nopcache.interned_strings_buffer=16\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=1\nopcache.revalidate_freq=2\n' \
    > /usr/local/etc/php/conf.d/sa-opcache.ini

# The PHP built-in server is single-threaded by default; a small worker pool
# keeps concurrent storefront requests from queueing behind each other.
ENV PHP_CLI_SERVER_WORKERS=4

WORKDIR /app
COPY . .

RUN mkdir -p storage/framework/{cache,cache/data,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# A Railway volume mounted at storage/app/public shadows the git-tracked seed
# images baked into this layer, so keep a copy outside the mount point and
# restore it at boot (see CMD) without clobbering anything already on the volume.
RUN mkdir -p /app/seed-storage && cp -r storage/app/public/. /app/seed-storage/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --optimize-autoloader --no-dev --no-interaction
RUN php artisan storage:link

EXPOSE 8080
CMD mkdir -p storage/app/public && cp -rn /app/seed-storage/. storage/app/public/ && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
