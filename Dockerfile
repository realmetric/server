FROM php:8.2.2-fpm as base

RUN apt-get update \
    && apt-get install -y unzip git htop --no-install-recommends \
    && docker-php-ext-install pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && rm -rf /var/lib/apt

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony  \
    && symfony -V

RUN echo '#!/bin/bash' > /usr/local/bin/console  \
    && echo 'cd /var/www && ./bin/console $@' >> /usr/local/bin/console  \
    && chmod +x /usr/local/bin/console

WORKDIR /var/www
