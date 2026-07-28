# Usamos una imagen oficial de PHP con Apache (similar a XAMPP)
FROM php:8.2-apache

# Instalamos las librerías necesarias para que PHP se conecte a PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Habilitamos la reescritura de URLs de Apache
RUN a2enmod rewrite

# Copiamos todo el frontend, backend y archivos a la carpeta pública del servidor
COPY . /var/www/html/

# Ajustamos los permisos básicos
RUN chown -R www-data:www-data /var/www/html/