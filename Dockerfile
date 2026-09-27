
FROM php:8.2-apache

# Install PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on port 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Copy complete project
COPY . /var/www/html/

# Set ownership
RUN chown -R www-data:www-data /var/www/html

# Allow PHP to write uploaded files
RUN chmod -R 775 /var/www/html/uploads

EXPOSE 8080