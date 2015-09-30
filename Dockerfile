FROM phusion/baseimage:latest
MAINTAINER Matthew Baggett <matthew@baggett.me>

CMD ["/sbin/my_init"]

ENV DEBIAN_FRONTEND noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN true

# Install base packages
RUN apt-get update && \
    apt-get -yq install \
        wget \
        curl \
        nano \
	php5-cli \
        php5-mysql \
        php5-gd \
        php5-curl \
        php-pear \
        php5-dev \
        nmap \
        redis-server \
        apt-utils \
        sudo \
        net-tools \
        telnet \
        jq \
        netcat-openbsd \
        iputils-ping \
        unzip \
        pwgen \
        bc \
        php-apc && \
    rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add our crontab file
ADD crons.conf /root/crons.conf

# Use the crontab file
RUN crontab /root/crons.conf

ENV TZ "Europe/London"
RUN echo $TZ | tee /etc/timezone \
  && dpkg-reconfigure --frontend noninteractive tzdata

ADD . /app

# Run Composer
RUN cd /app && composer install

WORKDIR /app


