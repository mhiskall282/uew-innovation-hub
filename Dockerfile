# syntax=docker/dockerfile:1
#
# UEW Innovation Hub production image (Render, Railway, Fly.io, any Docker host).
# Three stages: front-end assets, PHP dependencies, Apache + PHP 8.4 runtime.

# ---- 1. Front-end assets (Tailwind CSS v4 + Alpine via Vite) -------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY app/Enums ./app/Enums
RUN npm run build

# ---- 2. PHP dependencies ---------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ---- 3. Runtime ------------------------------------------------------------------
FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev libicu-dev unzip curl \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_mysql zip intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Apache: serve public/, enable rewrites and headers, hide version details.
RUN a2enmod rewrite headers \
    && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-uew-hub.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/uew-entrypoint
RUN chmod +x /usr/local/bin/uew-entrypoint

ENV PORT=8080
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/up" || exit 1

ENTRYPOINT ["uew-entrypoint"]
CMD ["apache2-foreground"]
