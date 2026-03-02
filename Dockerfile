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

# Copy application files
COPY . .

# Download Tailwind CSS standalone CLI based on architecture
RUN ARCH=$(uname -m) && \
    if [ "$ARCH" = "x86_64" ]; then \
        TAILWIND_ARCH="x64"; \
    elif [ "$ARCH" = "aarch64" ]; then \
        TAILWIND_ARCH="arm64"; \
    else \
        echo "Unsupported architecture: $ARCH" && exit 1; \
    fi && \
    curl -sLo /usr/local/bin/tailwindcss \
    https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-linux-$TAILWIND_ARCH && \
    chmod +x /usr/local/bin/tailwindcss

# Compile Tailwind CSS
RUN tailwindcss -i assets/input.css -o assets/style.css --minify

# Ensure content dir exists and is writable
RUN mkdir -p content && chown -R www-data:www-data content

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
