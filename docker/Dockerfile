FROM php:8.3-apache
RUN docker-php-ext-install mysqli
COPY php.ini /usr/local/etc/php/conf.d/local.ini
COPY prepend.php /usr/local/etc/php/prepend.php
