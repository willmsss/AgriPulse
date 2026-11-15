FROM php:8.2-apache

# Install system dependencies and PHP PDO MySQL & PostgreSQL extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Copy project files into Apache's public directory
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Copy and configure the startup script for Render's dynamic port
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
