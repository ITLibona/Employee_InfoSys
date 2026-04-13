FROM php:8.2-apache

# Install PDO MySQL driver required by the application.
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite support.
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure uploads directory exists and is writable by the web server user.
RUN mkdir -p /var/www/html/uploads/photos \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]
