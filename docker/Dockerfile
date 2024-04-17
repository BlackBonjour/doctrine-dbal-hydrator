FROM php:8.3-bookworm as base
LABEL authors="Erick Dyck"

# Update base system
RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get clean -y \
    && rm -rf /var/cache/apt /var/lib/apt/lists/*

# Install PHP packages
RUN docker-php-ext-install pdo_mysql

# Install latest composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Set working directory
WORKDIR /app
