
FROM php:8.2-apache

# Install PHP MySQL extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set upload directory permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Render uses the PORT environment variable
EXPOSE 10000

CMD ["bash", "-c", "PORT=${PORT:-10000}; sed -i 's/^Listen 80$/Listen '${PORT}'/' /etc/apache2/ports.conf; sed -i 's/<VirtualHost \\*:80>/<VirtualHost *:'${PORT}'>/' /etc/apache2/sites-available/000-default.conf; apache2-foreground"]
