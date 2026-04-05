FROM php:8.2-apache

# Install mysqli extension for MySQL
RUN docker-php-ext-install mysqli

# Copy project files into Apache web root
COPY . /var/www/html/

# Enable Apache rewrite module (optional, for clean URLs)
RUN a2enmod rewrite