# syntax=docker/dockerfile:1

##########################################################
# Stage 1 - Composer dependencies
##########################################################
FROM composer:2 AS vendor

WORKDIR /app

# Copied alone first so this layer is cached on the lockfile, not on every
# source edit. A Blade tweak then costs nothing here.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative


##########################################################
# Stage 2 - Frontend assets (Vite / Tailwind v4)
##########################################################
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

# Tailwind v4 scans Blade sources for class names. Vendor packages ship Blade
# too, so without this some utility classes are absent from the production
# stylesheet - the classic "works with `npm run dev`, broken once built".
COPY --from=vendor /app/vendor ./vendor

RUN npm run build


##########################################################
# Stage 3 - Runtime: nginx + php-fpm under supervisor
##########################################################
#
# PHP 8.2, matching composer.json's ^8.2 and the version the whole test suite
# runs on. 8.3 would very probably work; "very probably" is the wrong standard
# for a defence, and the version costs nothing to match.
FROM php:8.2-fpm-alpine AS runtime

# gd and exif are here for App\Support\Thumbnail, which resizes every uploaded
# photo once at upload time. Without them the thumbnail step is skipped and the
# grids fall back to streaming full-size originals - which is what they did
# before, and which costs the farm's mobile data on every page.
#
# The -dev packages are BUILD-ONLY and are removed with .build-deps below; the
# bare libjpeg-turbo/libpng/libwebp/freetype lines are the runtime halves that
# must stay, or gd loads and then fails on every actual image.
RUN apk add --no-cache \
        nginx \
        supervisor \
        libpq \
        libzip \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libwebp \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        zip \
        intl \
        opcache \
        gd \
        exif \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/supervisord.conf  /etc/supervisord.conf
COPY docker/php.ini           /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/www.conf          /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/entrypoint.sh     /usr/local/bin/entrypoint

# chmod here as well as in git: the image must boot even if someone clones
# without the executable bit surviving.
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/cache/data \
               storage/framework/sessions \
               storage/framework/views \
               storage/logs \
               bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm -rf /var/www/html/.env /var/www/html/.git

EXPOSE 10000

ENTRYPOINT ["entrypoint"]
