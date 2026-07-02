FROM php:8.2-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cambiar DocumentRoot a la carpeta web
RUN sed -i 's|/var/www/html|/var/www/html/web|g' /etc/apache2/sites-available/000-default.conf

# Copiar el codigo fuente
COPY . /var/www/html/

# Instalar dependencias PHP con Composer
WORKDIR /var/www/html/web
RUN composer install --no-interaction --no-dev
