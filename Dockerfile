# Dockerfile

FROM php:8.2-fpm-alpine

# 1) System dependencies & PHP extensions
RUN apk add --no-cache \
      git \
      oniguruma-dev \
      libzip-dev \
      libxml2-dev \
      zlib-dev \
      libpng-dev \
      libjpeg-turbo-dev \
      freetype-dev \
      icu-dev \
      g++ \
      make \
      autoconf \
      npm \
      nodejs \
      netcat-openbsd \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
      pdo_mysql \
      mbstring \
      zip \
      xml \
      gd \
      simplexml \
      xmlreader \
      dom \
      intl \
  && rm -rf /var/cache/apk/*

# 2) Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3) Set working directory
WORKDIR /var/www/html

# 4) Copy source code
COPY . .

# 5) Install PHP dependencies
RUN composer install

# 6) Install frontend dependencies & build assets
RUN npm ci && npm run build

# 7) Laravel optimizations
RUN php artisan key:generate \
  && php artisan config:cache \
  && php artisan route:cache \
  && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
