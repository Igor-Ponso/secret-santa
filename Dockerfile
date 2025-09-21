# Dockerfile for Laravel + SQLite on Koyeb
# -------------------------------------------------------------------
# This image installs required PHP extensions (pdo, pdo_sqlite, sqlite3, zip)
# and serves the app via Apache with /public as the document root.
# Suitable for staging / low-traffic. For higher scale, consider
# separate DB (MySQL/Postgres) and maybe a robust web server config.
# -------------------------------------------------------------------

FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update \
     && apt-get install -y --no-install-recommends \
         git unzip libzip-dev libsqlite3-dev sqlite3 libonig-dev \
     # pdo is built-in; we only need pdo_sqlite and zip
     && docker-php-ext-install pdo_sqlite zip \
     && a2enmod rewrite \
     && rm -rf /var/lib/apt/lists/*

# Set Apache Document Root to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy composer manifests first for layer caching
COPY composer.json composer.lock* ./

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# Install PHP dependencies (no dev for staging/prod)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Copy rest of application source
COPY . .

# Ensure SQLite file exists and proper permissions
RUN mkdir -p database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data database storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod 664 database/database.sqlite || true

# Optimize (ignore failures so container still boots)
RUN php artisan config:cache || true \
 && php artisan route:cache || true \
 && php artisan view:cache || true

EXPOSE 80

# Launch script will run migrations at runtime (see docker-start.sh)
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

CMD ["/usr/local/bin/docker-start.sh"]
