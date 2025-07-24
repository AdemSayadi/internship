FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html
COPY . .
RUN composer install --optimize-autoloader --no-dev
CMD ["php-fpm"]
