FROM composer:2.1.14

COPY --chown=www-data:www-data . /srv/app
WORKDIR /srv/app

RUN apk add php postgresql-dev
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql
RUN composer update
RUN composer update --dev
ADD php.ini /etc/php7/php.ini
RUN php bin/console lexik:jwt:generate-keypair

USER www-data

CMD [ "php", "bin/console", "server:run" , "0.0.0.0:8080"]