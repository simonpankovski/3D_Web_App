FROM composer:2.1.14

COPY . /srv/app
WORKDIR /srv/app

RUN apk add php postgresql-dev
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql
RUN composer update
ADD php.ini /etc/php7/php.ini

RUN chown -R www-data:www-data .
USER www-data

CMD [ "php", "bin/console", "server:run" , "0.0.0.0:8080"]