FROM node:22-alpine AS frontend

WORKDIR /repo/frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN mkdir -p /repo/backend/laravel-app/public \
    && npm run build

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpq-dev libxml2-dev unzip \
    && docker-php-ext-install -j"$(nproc)" dom mbstring pdo_pgsql simplexml xml xmlwriter \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/laravel-app/ ./
COPY --from=frontend /repo/backend/laravel-app/public/spa ./public/spa
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /usr/local/bin/start-game
COPY docker/seed-game-if-empty.php /usr/local/bin/seed-game-if-empty.php

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/start-game

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/start-game"]
