# Image development lokal. Produksi memakai PHP-FPM + Nginx (Architecture §1.2).
FROM php:8.5-cli

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && install-php-extensions pdo_pgsql pgsql bcmath intl zip pcntl opcache redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:26-slim /usr/local/ /usr/local/

WORKDIR /var/www/html
