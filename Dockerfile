FROM node:22-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-fpm-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/^;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker-php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize --no-interaction

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:stable-alpine AS web
COPY --from=app /var/www/html/public /var/www/html/public
COPY nginx.conf /etc/nginx/conf.d/default.conf
