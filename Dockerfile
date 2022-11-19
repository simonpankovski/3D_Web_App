FROM composer:2.2.18

COPY . /srv/app
WORKDIR /srv/app

RUN composer update

RUN chown -R www-data:www-data .
USER www-data

CMD [ "php", "bin/console", "server:run" , "0.0.0.0:80"]