# ─── Stage 1: Composer dependencies ─────────────────────────────────────────
FROM composer:2.7 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ─── Stage 2: Node.js (frontend assets) ──────────────────────────────────────
FROM node:20-alpine AS node

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build

# ─── Stage 3: Production image ───────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        zip \
        gd \
        intl \
        mbstring \
        bcmath \
        opcache \
        pcntl

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps autoconf g++ make && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del .build-deps

WORKDIR /var/www/html

# Copy composer dependencies
COPY --from=composer /app/vendor ./vendor
COPY --from=composer /app .

# Copy compiled frontend assets
COPY --from=node /app/public/build ./public/build

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/hiresphere.ini

# Set permissions
RUN mkdir -p /var/log/supervisor && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
