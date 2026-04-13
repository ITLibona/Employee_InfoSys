FROM php:8.2-cli

# Install PDO MySQL driver required by the application.
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app
COPY . /app/

# Ensure uploads directory exists and is writable.
RUN mkdir -p /app/uploads/photos \
    && chmod -R 775 /app/uploads

EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
