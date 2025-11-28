FROM wordpress:php7.4-apache

RUN apt-get update && apt-get install -y \
    build-essential \
    autoconf \
    wget

RUN cd /usr/local/src/ && \
    wget https://xdebug.org/files/xdebug-3.1.6.tgz && \
    tar -xvzf xdebug-3.1.6.tgz && \
    cd xdebug-3.1.6 && \
    phpize && \
    ./configure --enable-xdebug && \
    make && \
    make install

RUN touch /tmp/xdebug.log && \
    chmod 777 /tmp/xdebug.log

EXPOSE 9003