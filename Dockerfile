# The live site on Render (render.yaml). PHP + Apache serving public/.
# No Node step: no page uses @vite (welcome.blade.php is unused).
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Report photos may be up to 5 MB (ReportController's 'max:5120'); PHP's default is 2 MB.
RUN printf 'upload_max_filesize=6M\npost_max_size=8M\n' > /usr/local/etc/php/conf.d/uploads.ini

# Serve public/, let its .htaccess do the clean URLs, and listen on Render's $PORT.
ENV PORT=10000
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover \
    && chown -R www-data:www-data storage bootstrap/cache

# Caches are built at start, when Render's environment variables exist.
CMD ["sh", "-c", "php artisan optimize && apache2-foreground"]
