FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libicu-dev \
    g++ \
    libzip-dev \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-jpeg --with-freetype
RUN docker-php-ext-install -j$(nproc) \
    gd \
    mbstring \
    pdo_mysql \
    zip \
    intl \
    bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js y npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY . /app

# Instalar dependencias de PHP
RUN composer install --optimize-autoloader --no-dev

# Instalar dependencias de Node.js
RUN npm install && npm run build

# Crear directorios necesarios
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/bootstrap/cache

# Cambiar propietario
RUN chown -R www-data:www-data /app && \
    chmod -R 755 /app && \
    chmod -R 777 /app/storage && \
    chmod -R 777 /app/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
