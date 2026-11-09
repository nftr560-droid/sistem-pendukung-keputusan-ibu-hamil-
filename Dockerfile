FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app
COPY . /app

# Run PHP built-in web server on Railway's dynamic $PORT
EXPOSE 80
CMD sh -c "php -S 0.0.0.0:\${PORT:-80} -t /app"
