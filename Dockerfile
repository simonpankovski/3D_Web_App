FROM composer:2.1.14

USER www-data
COPY --chown=www-data:www-data . /srv/app
WORKDIR /srv/app
RUN chmod +r /srv/app/.env
RUN chown root:root .env

USER root
RUN apk add php postgresql-dev postgresql-client
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql
RUN composer install
ADD php.ini /etc/php7/php.ini
RUN php bin/console lexik:jwt:generate-keypair
RUN chown www-data:www-data .env
USER www-data

CMD [ "php", "bin/console", "server:run" , "0.0.0.0:8080"]