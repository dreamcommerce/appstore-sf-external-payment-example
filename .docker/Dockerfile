ARG PHP_VERSION=8.2-fpm-alpine3.16
FROM php:${PHP_VERSION}

RUN apk update && apk add --no-cache \
    git \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    mysql-client \
    bash \
    $PHPIZE_DEPS

RUN docker-php-ext-install zip pdo pdo_mysql intl

WORKDIR /var/www/html
COPY app/ .
COPY app/wait-for-it.sh ./wait-for-it.sh

RUN chmod +x ./wait-for-it.sh

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction --optimize-autoloader

EXPOSE 8080