FROM php:latest

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Compressors
RUN apt-get update && \
    apt-get install zip unzip && \
    rm -rf /var/lib/apt/lists/*

# Copy project
COPY . /var/www/project
COPY ./data /data

WORKDIR /var/www/project

RUN composer install --optimize-autoloader --no-dev

CMD [ "php", "-S", "0.0.0.0:80", "-t", "." ]