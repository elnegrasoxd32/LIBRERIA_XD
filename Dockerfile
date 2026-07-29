FROM php:8.1-apache

# Instalar dependencias del sistema y compilar extensiones de PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar el código del proyecto al directorio web
COPY . /var/www/html/

EXPOSE 80
