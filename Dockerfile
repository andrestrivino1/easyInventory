# Usa PHP 8.0 con Apache
FROM php:8.0-apache

# Instala extensiones necesarias para Laravel
RUN apt-get update && \
    apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Habilita mod_rewrite de Apache (necesario para Laravel)
RUN a2enmod rewrite

# Establece el directorio de trabajo en /var/www
WORKDIR /var/www

# Copia tus archivos al contenedor y crea symlink para servir public
COPY . /var/www
RUN rm -rf /var/www/html && ln -s /var/www/public /var/www/html

# Instala Composer (gestor de dependencias de PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permisos para Laravel (opcional pero recomendable)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expone el puerto 80
EXPOSE 80
