FROM php:5.6-alpine

ARG UID=1000
ARG GID=1000

RUN docker-php-ext-install mbstring \
    && docker-php-ext-install sockets \
    && docker-php-ext-install ctype \
    && addgroup -g ${GID} -S docker \
    && adduser -u ${UID} -S docker -G docker

USER docker
WORKDIR /home/docker

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar composer \
    && chmod +x composer \
    && mkdir phpsyslognet
