FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
