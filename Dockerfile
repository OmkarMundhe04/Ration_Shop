# ==========================================================
# Online Ration Shop - Production Dockerfile
# Optimized for Render, Railway, Fly.io, Cloud Run & Docker
# ==========================================================
FROM php:8.2-apache

# Install system dependencies, MySQL, PostgreSQL, and SQLite extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql mysqli opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Production PHP settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'upload_max_filesize=32M'; \
    echo 'post_max_size=32M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=60'; \
    echo 'date.timezone=UTC'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Configure Apache to allow .htaccess and respect dynamic $PORT
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!Directory /var/www/!Directory /var/www/html!g' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy application source code
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure proper permissions for web server and SQLite fallback storage
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/data

# Expose standard port 80 (Dynamic PORT support via start script if needed)
EXPOSE 80

# Run Apache in Foreground
CMD ["apache2-foreground"]
