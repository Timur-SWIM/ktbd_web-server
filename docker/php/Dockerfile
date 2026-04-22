FROM php:8.2-apache

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_13
ENV ORACLE_HOME=/opt/oracle/instantclient_21_13

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libaio1 \
        unzip \
        wget \
        git \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /opt/oracle \
    && cd /opt/oracle \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-basiclite-linux.x64-21.13.0.0.0dbru.zip \
    && wget -q https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-sdk-linux.x64-21.13.0.0.0dbru.zip \
    && unzip -q instantclient-basiclite-linux.x64-21.13.0.0.0dbru.zip \
    && unzip -q instantclient-sdk-linux.x64-21.13.0.0.0dbru.zip \
    && rm -f *.zip \
    && echo /opt/oracle/instantclient_21_13 > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

RUN docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/opt/oracle/instantclient_21_13,21.13 \
    && docker-php-ext-install pdo pdo_oci \
    && echo "instantclient,/opt/oracle/instantclient_21_13" | pecl install oci8 \
    && docker-php-ext-enable oci8

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh

RUN a2enmod rewrite headers \
    && chmod +x /usr/local/bin/app-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
