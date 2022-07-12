FROM ubuntu:22.04

WORKDIR /code

RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo UTC > /etc/timezone

RUN apt update && apt install -y ca-certificates sqlite3

RUN apt install -y php8.1-fpm php8.1-cli \
    php8.1-sqlite3 php8.1-gd php8.1-curl \
    php8.1-imap php8.1-mbstring \
    php8.1-xml php8.1-zip php8.1-bcmath php8.1-soap \
    php8.1-intl php8.1-readline \
    php8.1-ldap \
    php8.1-msgpack php8.1-igbinary \
    && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENTRYPOINT ["bash"]