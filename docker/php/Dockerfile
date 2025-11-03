FROM php:8.1-fpm

COPY php.ini /usr/local/etc/php/

# OSパッケージ + MeCab + ビルドに必要なライブラリ
RUN apt-get update \
  && apt-get install -y \
  default-mysql-client \
  zlib1g-dev \
  libzip-dev \
  unzip \
  mecab \
  libmecab-dev \
  mecab-ipadic-utf8 \
  && docker-php-ext-install pdo_mysql zip \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 15M" >> /usr/local/etc/php/php.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/php.ini && \
    echo "max_input_vars = 3000" >> /usr/local/etc/php/php.ini

WORKDIR /var/www
