FROM wordpress:php7.4-fpm

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && php wp-cli.phar --info \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp 

# Install system dependencies for mysqli + zip + Xdebug
# RUN apt-get update && apt-get install -y \
#         libzip-dev \
#         zip \
#         unzip \
#         libonig-dev \
#         libpng-dev \
#         libjpeg-dev \
#         libfreetype6-dev \
#         libxml2-dev \
#         default-mysql-client \
#         libmariadb-dev-compat \
#         libmariadb-dev 
        
# RUN docker-php-ext-install mysqli pdo pdo_mysql zip \
#     && pecl install xdebug \
#     && docker-php-ext-enable xdebug \
#     && rm -rf /var/lib/apt/lists/*

# Copy your Xdebug config
# COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# EXPOSE 9003

