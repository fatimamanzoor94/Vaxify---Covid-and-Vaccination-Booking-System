FROM php:8.2-apache

# Install PHP MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Disable conflicting Apache MPM modules
RUN a2dismod mpm_event mpm_worker || true

# Enable the required Apache modules
RUN a2enmod mpm_prefork rewrite

# Configure Apache to listen on port 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 8080