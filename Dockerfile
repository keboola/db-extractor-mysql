FROM php:5.6
MAINTAINER Erik Zigo <erik.zigo@keboola.com>

RUN apt-get update -q \
  && apt-get install unzip git ssh -y --no-install-recommends

RUN docker-php-ext-install pdo_mysql

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

COPY ./docker/app/php.ini /usr/local/etc/php/php.ini
COPY . /code

WORKDIR /code

RUN composer install --prefer-dist --no-interaction

RUN curl --location --silent --show-error --fail \
        https://github.com/Barzahlen/waitforservices/releases/download/v0.3/waitforservices \
        > /usr/local/bin/waitforservices && \
    chmod +x /usr/local/bin/waitforservices

ENTRYPOINT php ./src/run.php --data=/data
