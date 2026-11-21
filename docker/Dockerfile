FROM php:8.3-apache
RUN docker-php-ext-install mysqli
COPY php.ini /usr/local/etc/php/conf.d/local.ini
COPY prepend.php /usr/local/etc/php/prepend.php
# z5: zezwolenie na .htaccess (blokada bezpośredniego dostępu do z5/dysk, jak na hostingu)
RUN printf '<Directory /var/www/html>\n\tAllowOverride All\n</Directory>\n' > /etc/apache2/conf-enabled/z-allow-override.conf
