# syntax=docker/dockerfile:1.7

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build

FROM php:8.4-fpm-alpine

WORKDIR /app

RUN apk add --no-cache \
        bash \
        curl \
        fcgi \
        icu-dev \
        linux-headers \
        nginx \
        sqlite-dev \
        su-exec \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY docker-start.sh /usr/local/bin/docker-start.sh
COPY coolify-post-deploy.sh /usr/local/bin/coolify-post-deploy.sh

RUN chmod +x /usr/local/bin/docker-start.sh /usr/local/bin/coolify-post-deploy.sh \
    && mkdir -p /run/nginx /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

CMD ["docker-start.sh"]
