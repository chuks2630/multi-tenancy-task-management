FROM php:8.3-fpm-alpine

# Install system dependencies + PHP extensions including intl
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        opcache \
        intl \
        zip

WORKDIR /var/www/html
COPY . .

# Install composer and dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction

# Copy nginx and startup configs
COPY .docker/nginx.conf /etc/nginx/nginx.conf
COPY .docker/start.sh /start.sh
RUN chmod +x /start.sh

# Correct permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080
CMD ["/start.sh"]