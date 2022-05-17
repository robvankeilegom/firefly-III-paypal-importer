FROM php:8.1.3-apache

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    cron

# Change the document root to /var/www/html/public
RUN sed -i -e "s/html/html\/public/g" /etc/apache2/sites-enabled/000-default.conf

RUN a2enmod rewrite

# Copy Composer binary from the Composer official Docker image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set up data folder
RUN mkdir -p /data

# Define volumes
VOLUME /data
VOLUME /var/www/html

# Set the working directory
WORKDIR /var/www/html

# Copy source files
COPY . .

# Make sure the scheduler works
RUN echo "* * * * * root php /var/www/html/artisan schedule:run >> /var/log/cron.log 2>&1" >> /etc/crontab

# Set up the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod u+x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]

# Expose port 80
EXPOSE 80

# Start apache
CMD ["apache2-foreground"]

