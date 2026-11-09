FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Adjust Apache port dynamically for Railway (Railway supplies $PORT environment variable)
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

