# =============================================================================
# STAGE 1: Build Frontend Assets
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js* postcss.config.js* ./
COPY public/ ./public/

RUN npm run build

# =============================================================================
# STAGE 2: Install PHP Dependencies
# =============================================================================
FROM composer:2.8 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# =============================================================================
# STAGE 3: Production Image
# =============================================================================
FROM php:8.4-fpm-alpine AS production

LABEL maintainer="CBT Ujian Online"
LABEL description="Secure Docker image for CBT Ujian Online"

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# Install dependencies & PHP extensions
RUN apk add --no-cache \
    freetype freetype-dev \
    libjpeg-turbo libjpeg-turbo-dev \
    libpng libpng-dev \
    libwebp libwebp-dev \
    icu-libs icu-dev \
    libzip libzip-dev \
    libxml2 libxml2-dev \
    linux-headers \
    fcgi \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) bcmath exif gd intl opcache pcntl pdo_mysql zip \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev icu-dev libzip-dev libxml2-dev linux-headers \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Create non-root user
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www -s /sbin/nologin

WORKDIR /var/www/html

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Copy application
COPY --from=composer --chown=www:www /app/vendor ./vendor
COPY --chown=www:www . .
COPY --from=frontend --chown=www:www /app/public/build ./public/build

# Cleanup unnecessary files (keep docker/php for secrets bootstrap)
RUN rm -rf .git .github .env.example tests phpunit.xml node_modules \
    storage/logs/*.log storage/framework/cache/data/* \
    storage/framework/sessions/* storage/framework/views/* \
    docker/nginx docker/mysql docker/secrets docker/*.sh \
    && mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www
EXPOSE 9000

# PHP-FPM healthcheck using fcgi
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

CMD ["php-fpm"]

# =============================================================================
# STAGE 4: Nginx
# =============================================================================
FROM nginx:1.27-alpine AS nginx

LABEL maintainer="CBT Ujian Online"

# Install wget for healthcheck
RUN apk add --no-cache wget

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Copy nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy static files
COPY --from=production /var/www/html/public /var/www/html/public

# Setup non-root user
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www -s /sbin/nologin \
    && chown -R www:www /var/www/html/public /var/cache/nginx /var/log/nginx \
    && touch /var/run/nginx.pid && chown www:www /var/run/nginx.pid

USER www
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD wget -q --spider http://127.0.0.1/health || exit 1

CMD ["nginx", "-g", "daemon off;"]
