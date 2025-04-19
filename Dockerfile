# Dockerfile

# ─── Base image with PHP and Node ────────────────────────────────
FROM php:8.2-fpm-alpine

# 1) System deps + PHP ext’s + Node.js & npm
RUN apk add --no-cache \
      git \
      oniguruma-dev \
      libzip-dev \
      zip \
      nodejs \
      npm \
      netcat-openbsd \
  && docker-php-ext-install pdo_mysql mbstring zip \
  && rm -rf /var/cache/apk/*

# 2) Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3) Set working directory
WORKDIR /var/www/html

# 4) Copy entire app so artisan is present
COPY . .

# 5) Install PHP dependencies (artisan exists now so scripts won’t break)
RUN composer install

# 6) Install JS deps & build assets at image‑build time
RUN npm ci \
  && npm run build

# 7) Laravel optimizations
RUN php artisan key:generate \
  && php artisan config:cache \
  && php artisan route:cache \
  && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]

