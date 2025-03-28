ARG PHP_VERSION=8.4-fpm-alpine
FROM php:${PHP_VERSION}

RUN apk update && apk add --no-cache \
    git \
    zip \
    unzip \
    libzip-dev

RUN docker-php-ext-install zip

WORKDIR /var/www/html
COPY app/ .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction --optimize-autoloader
RUN php bin/console cache:clear --no-warmup

EXPOSE 8000