ARG PHP_VERSION=8.2-fpm-alpine
FROM php:${PHP_VERSION}

RUN apk update && apk add --no-cache \
    git \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    mysql-client \
    $PHPIZE_DEPS

RUN docker-php-ext-install zip pdo pdo_mysql intl

WORKDIR /var/www/html
COPY app/ .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 8081