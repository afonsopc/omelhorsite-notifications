FROM php:8.3.1-apache

WORKDIR /var/www/html

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy a custom Apache configuration file that reads the environment variables
COPY notifications/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy the PHP files
COPY notifications/src/ /var/www/html/

# Enable Apache modules and restart Apache
RUN a2enmod rewrite

# Set PHP to production mode
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT' >> "$PHP_INI_DIR/php.ini" && \
    echo 'display_errors = Off' >> "$PHP_INI_DIR/php.ini" && \
    echo 'log_errors = On' >> "$PHP_INI_DIR/php.ini" && \
    echo 'error_log = /var/log/php/error.log' >> "$PHP_INI_DIR/php.ini" && \
    echo 'variables_order = "EGPCS"' >> "$PHP_INI_DIR/php.ini"

CMD ["apache2-foreground"]
