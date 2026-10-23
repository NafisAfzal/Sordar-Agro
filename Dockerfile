# syntax=docker/dockerfile:1.7

FROM php:8.4-cli

# Runtime tools + PHP extensions. `gosu` lets the boot script drop privileges
# before running migrations/caches; `supervisor` owns the web and queue workers.
RUN apt-get update && apt-get install -y --no-install-recommends \
        bcmath \
        curl \
        git \
        gosu \
        libzip-dev \
        supervisor \
        unzip \
    && docker-php-ext-install pdo pdo_mysql zip opcache bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Harden the PHP runtime and keep the built-in server usable in containers.
RUN printf '%s\n' \
        'expose_php=0' \
        'memory_limit=256M' \
        'opcache.enable=1' \
        'opcache.enable_cli=1' \
        'opcache.memory_consumption=128' \
        'opcache.interned_strings_buffer=16' \
        'opcache.max_accelerated_files=20000' \
        'opcache.validate_timestamps=1' \
        'opcache.revalidate_freq=2' \
    > /usr/local/etc/php/conf.d/sa-runtime.ini

# The PHP built-in server is single-threaded by default; a small worker pool
# keeps concurrent storefront requests from queueing behind each other.
ENV PHP_CLI_SERVER_WORKERS=4 \
    PORT=8080

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy the source before installing dependencies so Laravel's package
# discovery can run during the Composer autoload dump.
COPY . .

# A Railway volume mounted at storage/app/public shadows the git-tracked seed
# images baked into this layer, so keep a copy outside the mount point and
# restore it at boot (see docker/start.sh) without clobbering uploads.
COPY docker/start.sh /usr/local/bin/docker-start
COPY docker/supervisord.conf /etc/supervisor/conf.d/sordar.conf

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan storage:link \
    && mkdir -p /app/seed-storage \
    && cp -r storage/app/public/. /app/seed-storage/ \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/docker-start

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/up" >/dev/null || exit 1

STOPSIGNAL SIGTERM

ENTRYPOINT ["docker-start"]
