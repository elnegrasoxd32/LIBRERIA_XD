FROM php:8.1-apache

# Instalar bibliotecas necesarias para PostgreSQL y activar las extensiones pdo_pgsql y pgsql
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

COPY . /var/www/html/

EXPOSE 80