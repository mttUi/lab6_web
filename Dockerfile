FROM php:8.2-fpm-alpine

RUN apk update && apk add --no-cache \
    git \
    unzip \
    curl

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY ./www /var/www/html

RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]