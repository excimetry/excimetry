FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libtool \
    autoconf \
    make \
    pkg-config \
    gcc \
    g++ \
    curl \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN git clone https://github.com/wikimedia/mediawiki-php-excimer.git /usr/src/ext-excimer \
    && cd /usr/src/ext-excimer \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && echo "extension=excimer.so" > /usr/local/etc/php/conf.d/ext-excimer.ini

WORKDIR /app

COPY . /app

RUN composer install --no-interaction --prefer-dist