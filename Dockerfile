# Dockerfile for Laravel + SQLite on Koyeb
# -------------------------------------------------------------------
# This image installs required PHP extensions (pdo, pdo_sqlite, sqlite3, zip)
# and serves the app via Apache with /public as the document root.
# Suitable for staging / low-traffic. For higher scale, consider
# separate DB (MySQL/Postgres) and maybe a robust web server config.
# -------------------------------------------------------------------

###############################
# 1) Frontend build stage
###############################
FROM node:20-alpine AS frontend-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --omit=dev=false
COPY resources ./resources
COPY tsconfig.json vite.config.ts tailwind.config.js ./
COPY components.json ./components.json
# (No postcss.config.js needed; plugins declared inline in vite.config.ts)
RUN npm run build

###############################
# 2) PHP runtime stage
###############################
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

# Also copy minimal framework bootstrap (artisan + bootstrap folder) so that
# composer post-autoload-dump scripts that invoke artisan won't fail.
COPY artisan artisan
COPY bootstrap ./bootstrap

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# Install PHP dependencies (no dev for staging/prod) WITHOUT running scripts yet
# We delay artisan-related scripts until after full source is copied to avoid
# missing route/config file errors.
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

# Copy rest of application source
COPY . .

# Copy built frontend assets from build stage
COPY --from=frontend-build /app/public/build ./public/build

# Now that the full application tree (routes/, config/, app/, etc.) exists, run
# the previously skipped composer scripts (package discovery, etc.). We guard
# with `|| true` so a non-critical script failure won't break image builds.
RUN composer run-script post-autoload-dump || true

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

# Koyeb deployment definition expects container to serve on port 8000.
# Adjust Apache to listen on 8000 (default image listens on 80).
RUN sed -ri 's/^Listen 80$/Listen 8000/' /etc/apache2/ports.conf \
 && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:8000>!' /etc/apache2/sites-available/000-default.conf

EXPOSE 8000

# Launch script will run migrations at runtime (see docker-start.sh)
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

CMD ["/usr/local/bin/docker-start.sh"]
