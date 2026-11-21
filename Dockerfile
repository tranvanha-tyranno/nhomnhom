# syntax=docker/dockerfile:1

FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

RUN { \
      echo 'expose_php = Off'; \
      echo 'output_buffering = 4096'; \
      echo 'date.timezone = Asia/Ho_Chi_Minh'; \
      echo 'session.cookie_httponly = 1'; \
      echo 'session.use_strict_mode = 1'; \
      echo 'session.save_path = "/var/lib/php/sessions"'; \
    } > /usr/local/etc/php/conf.d/nhomnhom.ini \
    && mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && chmod 1733 /var/lib/php/sessions \
    && printf '<Directory /var/www/html>\n    Options -Indexes +FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
       > /etc/apache2/conf-available/nhomnhom.conf \
    && a2enconf nhomnhom

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
