FROM php:7.2

RUN apt-get update
RUN apt-get install unzip -y
RUN docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG PUID
ARG PGID

WORKDIR /app
