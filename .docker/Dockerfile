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
    nodejs \
    npm \
    $PHPIZE_DEPS \
 && node -v \
 && npm -v
RUN npm install -g less
RUN docker-php-ext-install zip pdo pdo_mysql intl

WORKDIR /var/www/html
COPY app/ .
COPY app/wait-for-it.sh ./wait-for-it.sh
RUN mkdir -p public/assets && chmod -R 777 public/assets
RUN npm install
RUN npm run assets:build

RUN adduser -D appuser
RUN chmod +x ./wait-for-it.sh
RUN chown -R appuser:appuser /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

USER appuser
RUN composer install --no-interaction --optimize-autoloader \
    && php bin/console cache:clear \
    && php bin/console assets:install
USER root

EXPOSE 8080