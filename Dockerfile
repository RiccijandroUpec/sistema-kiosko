FROM php:8.2-fpm

# System dependencies (keep minimal to speed build)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    g++ \
    libzip-dev \
    wget \
    supervisor \
    nginx \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype && \
    docker-php-ext-install -j$(nproc) gd mbstring pdo_pgsql pgsql zip intl bcmath

# Composer binary (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js (LTS 18)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Optimize: copy metadata first to leverage Docker cache for deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

# Node dependencies cached layer
COPY package.json package-lock.json ./
RUN npm ci --silent || npm install --silent

# Copy application files after installing deps
COPY . .

# Build frontend assets (if present)
RUN if [ -f package.json ]; then npm run build --if-present || true; fi

# nginx sirve public/ y reenvia .php a php-fpm (en vez de "php artisan serve",
# que es de un solo hilo y se satura con trafico real).
RUN rm -f /etc/nginx/sites-enabled/default && \
    cp /app/docker/nginx.conf /etc/nginx/conf.d/app.conf

# Create required directories and set permissions
RUN mkdir -p /app/storage/logs /app/storage/app/public /app/bootstrap/cache && \
    chown -R www-data:www-data /app && \
    chmod -R 755 /app && \
    chmod -R 775 /app/storage /app/bootstrap/cache && \
    chmod +x /app/docker/entrypoint.sh /app/docker/run-scheduler.sh

EXPOSE 8080

# Un solo contenedor corre nginx + php-fpm + worker + scheduler vía supervisord
CMD ["/app/docker/entrypoint.sh"]
