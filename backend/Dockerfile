FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql opcache

WORKDIR /var/www/html
COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

COPY .docker/nginx.conf /etc/nginx/nginx.conf
COPY .docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]