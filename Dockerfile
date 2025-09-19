FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar composer.json y artisan
COPY composer.json composer.lock artisan /var/www/

# Copiar bootstrap (para app.php) y config (para package:discover)
COPY bootstrap /var/www/bootstrap
COPY config /var/www/config

# Copy artisan para que exista cuando composer lo necesite
COPY artisan /var/www/

# Crear carpetas necesarias
RUN mkdir -p bootstrap/cache storage/framework/{sessions,views,cache} storage/logs

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Ahora sí copia el resto del proyecto
COPY . .

# Ajustar permisos para Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Entrypoint que corre migraciones, crea enlace simbólico y arranca el servidor
RUN echo '#!/bin/bash\n\
set -e\n\
php artisan package:discover --ansi || true\n\
php artisan config:clear\n\
php artisan migrate --force || true\n\
php artisan storage:link || true\n\
php artisan config:cache\n\
exec php artisan serve --host=0.0.0.0 --port=8000' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

EXPOSE 8000

# Config PHP (subidas, tiempo de ejecución, etc.)
RUN echo "upload_max_filesize=100M\n\
post_max_size=100M\n\
max_file_uploads=20\n\
max_execution_time=300" > /usr/local/etc/php/conf.d/uploads.ini

CMD ["/entrypoint.sh"]
