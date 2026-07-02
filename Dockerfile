FROM php:8.2-apache

# Actualizar repositorios e instalar librerias necesarias de Postgres
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copiar el codigo fuente
COPY . /var/www/html/
