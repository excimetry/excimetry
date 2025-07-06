FROM php:8.2-cli

# Установим необходимые пакеты
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
    ext-excimer\

    && rm -rf /var/lib/apt/lists/*

# Установим Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установим ext-excimer из исходников
RUN git clone https://github.com/wikimedia/mediawiki-php-excimer.git /usr/src/ext-excimer \
    && cd /usr/src/ext-excimer \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && echo "extension=excimer.so" > /usr/local/etc/php/conf.d/excimer.ini

# Копируем файлы проекта (предполагается, что рядом есть composer.json, src/, tests/)
WORKDIR /app
COPY . /app

# Установим зависимости проекта
RUN composer install --no-interaction --prefer-dist

# Запуск по умолчанию — тесты
CMD ["vendor/bin/phpunit"]