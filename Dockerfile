# Build stage for Tailwind CSS
FROM node:22-alpine AS builder

WORKDIR /build

RUN npm install -g tailwindcss@3.4.19

COPY assets/input.css ./assets/input.css
COPY tailwind.config.js ./
COPY templates ./templates
COPY src ./src
COPY index.php ./

RUN npx tailwindcss -i assets/input.css -o style.css --minify

# Final stage
FROM php:8.3-apache

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module + install system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Configure Apache: AllowOverride for .htaccess
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files (except assets - handled separately)
COPY . .

# Copy compiled Tailwind CSS from builder
COPY --from=builder /build/style.css ./assets/style.css

# Ensure content dir exists and is writable
RUN mkdir -p content && chown -R www-data:www-data content

# Entrypoint: initialize custom icons config in mounted folder
COPY docker-entrypoint.sh /usr/local/bin/directory-listing-entrypoint
RUN chmod +x /usr/local/bin/directory-listing-entrypoint

ENTRYPOINT ["directory-listing-entrypoint"]
CMD ["apache2-foreground"]

EXPOSE 80
