FROM php:7.0

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git \
    unzip

RUN curl -sSk https://getcomposer.org/installer | php -- --version=1.10.16 && \
	mv composer.phar /usr/local/bin/composer

RUN pecl install xdebug-2.5.0 && docker-php-ext-enable xdebug