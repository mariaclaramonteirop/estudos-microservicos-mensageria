FROM php:8.3-cli

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json .

RUN composer install --no-interaction

COPY . .