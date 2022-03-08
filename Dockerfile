FROM php:8.1.3-apache

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

# Change the document root to /var/www/html/public
RUN sed -i -e "s/html/html\/public/g" /etc/apache2/sites-enabled/000-default.conf

RUN a2enmod rewrite

# Copy Composer binary from the Composer official Docker image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source files
COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN php artisan key:generate

RUN php artisan migrate --force



