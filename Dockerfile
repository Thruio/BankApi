FROM ubuntu:trusty
MAINTAINER Matthew Baggett <matthew@baggett.me>

ENV DEBIAN_FRONTEND noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN true

# Install base packages
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && \
    apt-get -yq install \
        wget \
        curl \
        git \
        apache2 \
        nodejs npm \
        nano \
        libapache2-mod-php5 \
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
RUN sed -i "s/variables_order.*/variables_order = \"EGPCS\"/g" /etc/php5/apache2/php.ini
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /tmp/.X11-unix /tmp/.ICE-unix \
      && chmod 1777 /tmp/.X11-unix /tmp/.ICE-unix \
      && mkdir -p /var/log/sele

ENV LANG_WHICH en
ENV LANG_WHERE US
ENV ENCODING UTF-8
ENV LANGUAGE ${LANG_WHICH}_${LANG_WHERE}.${ENCODING}
ENV LANG ${LANGUAGE}
RUN locale-gen ${LANGUAGE} \
    && dpkg-reconfigure --frontend noninteractive locales \
    && apt-get update -qqy \
    && apt-get -qqy install \
      language-pack-en \
    && rm -rf /var/lib/apt/lists/*

ENV TZ "Europe/London"
RUN echo $TZ | tee /etc/timezone \
  && dpkg-reconfigure --frontend noninteractive tzdata


# Add image configuration and scripts
ADD run.sh /run.sh
RUN chmod 755 /*.sh

# Configure /app folder with sample app
RUN mkdir -p /app && rm -fr /var/www/html && ln -s /app /var/www/html
ADD . /app
#ADD .htaccess /app/.htaccess
ADD ApacheConfig.conf /etc/apache2/sites-enabled/000-default.conf

# Run Composer
RUN cd /app && composer update

# Run NPM install
#RUN cd /app && npm install

# Enable mod_rewrite
RUN a2enmod rewrite && /etc/init.d/apache2 restart

RUN apt-get update -qqy \
  && apt-get -qqy install \
    software-properties-common \
  && echo debconf shared/accepted-oracle-license-v1-1 \
      select true | debconf-set-selections \
  && echo debconf shared/accepted-oracle-license-v1-1 \
      seen true | debconf-set-selections \
  && add-apt-repository ppa:webupd8team/java \
  && apt-get update -qqy \
  && apt-get -qqy install \
    oracle-java8-installer \
  && sed -i 's/securerandom.source=file:\/dev\/urandom/securerandom.source=file:\/dev\/.\/urandom/g' \
       /usr/lib/jvm/java-8-oracle/jre/lib/security/java.security \
  && sed -i 's/securerandom.source=file:\/dev\/random/securerandom.source=file:\/dev\/.\/urandom/g' \
       /usr/lib/jvm/java-8-oracle/jre/lib/security/java.security \
  && rm -rf /var/lib/apt/lists/*

#=======
# Fonts
#=======
RUN apt-get update -qqy \
  && apt-get -qqy install \
    fonts-ipafont-gothic \
    xfonts-100dpi \
    xfonts-75dpi \
    xfonts-cyrillic \
    xfonts-scalable \
    ttf-ubuntu-font-family \
    libfreetype6 \
    libfontconfig \
  && rm -rf /var/lib/apt/lists/*

#==========
# Selenium
#==========
ENV SELENIUM_MAJOR_MINOR_VERSION 2.46
ENV SELENIUM_PATCH_LEVEL_VERSION 0
RUN  mkdir -p /opt/selenium \
  && wget --no-verbose http://selenium-release.storage.googleapis.com/$SELENIUM_MAJOR_MINOR_VERSION/selenium-server-standalone-$SELENIUM_MAJOR_MINOR_VERSION.$SELENIUM_PATCH_LEVEL_VERSION.jar -O /opt/selenium/selenium-server-standalone.jar

#==================
# Chrome webdriver
#==================
# How to get cpu arch dynamically: $(lscpu | grep Architecture | sed "s/^.*_//")
ENV CPU_ARCH 64
ENV CHROME_DRIVER_FILE "chromedriver_linux${CPU_ARCH}.zip"
ENV CHROME_DRIVER_BASE chromedriver.storage.googleapis.com
# Gets latest chrome driver version. Or you can hard-code it, e.g. 2.15
RUN cd /tmp \
  # && CHROME_DRIVER_VERSION=2.15 \
  && CHROME_DRIVER_VERSION=$(curl 'http://chromedriver.storage.googleapis.com/LATEST_RELEASE' 2> /dev/null) \
  && CHROME_DRIVER_URL="${CHROME_DRIVER_BASE}/${CHROME_DRIVER_VERSION}/${CHROME_DRIVER_FILE}" \
  && wget --no-verbose -O chromedriver_linux${CPU_ARCH}.zip ${CHROME_DRIVER_URL} \
  && cd /opt/selenium \
  && rm -rf chromedriver \
  && unzip /tmp/chromedriver_linux${CPU_ARCH}.zip \
  && rm /tmp/chromedriver_linux${CPU_ARCH}.zip \
  && mv /opt/selenium/chromedriver /opt/selenium/chromedriver-$CHROME_DRIVER_VERSION \
  && chmod 755 /opt/selenium/chromedriver-$CHROME_DRIVER_VERSION \
  && ln -fs /opt/selenium/chromedriver-$CHROME_DRIVER_VERSION /usr/bin/chromedriver

#=========
# Openbox
# A lightweight window manager using freedesktop standards
#=========
RUN apt-get update -qqy \
  && apt-get -qqy install \
    openbox obconf menu \
  && rm -rf /var/lib/apt/lists/*

#==========================
# Google Chrome - Latest
#==========================
# If you have issue "Failed to move to new PID namespace" on OpenVZ (AWS ECS)
#  https://bugs.launchpad.net/chromium-browser/+bug/577919
# - try to pass chrome switch --no-sandbox see: http://peter.sh/experiments/chromium-command-line-switches/#no-sandbox
# - try /dev/shm? see: https://github.com/travis-ci/travis-ci/issues/938#issuecomment-16345102
# - try xserver-xephyr see: https://github.com/enkidulan/hangout_api/blob/master/.travis.yml#L5
# - try /opt/google/chrome/chrome-sandbox see: https://github.com/web-animations/web-animations-js/blob/master/.travis-setup.sh#L66
# Package libnss3-1d might help with issue 20
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
  && echo "deb http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
  && apt-get update -qqy \
  && apt-get -qqy install \
    google-chrome-stable \
    libnss3-1d \
  && sudo chmod 1777 /dev/shm \
  && rm -rf /var/lib/apt/lists/* \
  && rm /etc/apt/sources.list.d/google-chrome.list

#==========================
# Mozilla Firefox - Latest
#==========================
# dbus-x11 is needed to avoid http://askubuntu.com/q/237893/134645
RUN apt-get update -qqy \
    && apt-get -qqy install \
      firefox \
      dbus-x11 \
    && rm -rf /var/lib/apt/lists/*

#===================
# VNC, Xvfb, Xdummy
#===================
# xvfb: Xvfb or X virtual framebuffer is a display server
#  + implements the X11 display server protocol
#  + performs all graphical operations in memory
#
# Xdummy: Is like Xvfb but uses an LD_PRELOAD hack to run a stock X server
#  - uses a "dummy" video driver
#  - with Xpra allows to use extensions like Randr, Composite, Damage
RUN apt-get update -qqy \
  && apt-get -qqy install \
    x11vnc \
    xvfb \
    xorg \
    xserver-xorg-video-dummy \
  && rm -rf /var/lib/apt/lists/*

#======================
# OpenSSH server (sshd)
#======================
# http://linux.die.net/man/5/sshd_config
# http://www.openbsd.org/cgi-bin/man.cgi/OpenBSD-current/man5/sshd_config.5
RUN apt-get update -qqy \
  && apt-get -qqy install \
    openssh-server \
  && mkdir -p /var/run/sshd \
  && chmod 744 /var/run/sshd \
  && echo "PidFile /tmp/run_sshd.pid" >> /etc/ssh/sshd_config \
  && echo "X11Forwarding yes" >> /etc/ssh/sshd_config \
  && echo "GatewayPorts yes"  >> /etc/ssh/sshd_config \
  && rm -rf /var/lib/apt/lists/*

#========================
# Guacamole dependencies
#========================
RUN apt-get update -qqy \
  && apt-get -qqy install \
    gcc make \
    libcairo2-dev libpng12-dev libossp-uuid-dev \
    libssh2-1 libssh-dev libssh2-1-dev \
    libssl-dev libssl0.9.8 \
    libpango1.0-dev \
    autoconf libvncserver-dev \
  && rm -rf /var/lib/apt/lists/*

#========================================
# Add normal user with passwordless sudo
#========================================
ENV NORMAL_USER application
ENV NORMAL_GROUP ${NORMAL_USER}
ENV NORMAL_USER_UID 999
ENV NORMAL_USER_GID 998
RUN groupadd -g ${NORMAL_USER_GID} ${NORMAL_GROUP} \
  && useradd ${NORMAL_USER} --uid ${NORMAL_USER_UID} \
         --shell /bin/bash  --gid ${NORMAL_USER_GID} \
         --create-home \
  && usermod -a -G sudo ${NORMAL_USER} \
  && gpasswd -a ${NORMAL_USER} video \
  && echo 'ALL ALL = (ALL) NOPASSWD: ALL' >> /etc/sudoers

#===================
# DNS & hosts stuff
#===================
COPY ./etc/hosts /tmp/hosts

#==================
# User & ssh stuff
#==================
USER ${NORMAL_USER}
ENV USER ${NORMAL_USER}
ENV HOME /home/${USER}
RUN mkdir -p ~/.ssh \
  && touch ~/.ssh/authorized_keys \
  && chmod 700 ~/.ssh \
  && chmod 600 ~/.ssh/authorized_keys \
  && mkdir -p ${HOME}/.vnc \
  && sudo chown ${NORMAL_USER}:${NORMAL_GROUP} /var/log/sele
ENV VNC_STORE_PWD_FILE ${HOME}/.vnc/passwd

#===============================
# Run docker from inside docker
#===============================
# Usage: docker run -v /var/run/docker.sock:/var/run/docker.sock
#                   -v $(which docker):$(which docker)
ENV DOCKER_SOCK "/var/run/docker.sock"

#======================
# Tomcat for Guacamole
#======================
ENV TOMCAT_MAJOR 8
ENV TOMCAT_VERSION 8.0.23
ENV TOMCAT_TGZ_URL https://www.apache.org/dist/tomcat/tomcat-$TOMCAT_MAJOR/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz
# ENV CATALINA_HOME /usr/local/tomcat
ENV CATALINA_HOME ${HOME}/tomcat
# WORKDIR ${CATALINA_HOME}
# see https://www.apache.org/dist/tomcat/tomcat-8/KEYS
RUN mkdir -p ${CATALINA_HOME} \
  && cd ${CATALINA_HOME} \
  && gpg --keyserver pool.sks-keyservers.net --recv-keys \
       05AB33110949707C93A279E3D3EFE6B686867BA6 \
       07E48665A34DCAFAE522E5E6266191C37C037D42 \
       47309207D818FFD8DCD3F83F1931D684307A10A5 \
       541FBE7D8F78B25E055DDEE13C370389288584E7 \
       61B832AC2F1C5A90F0F9B00A1C506407564C17A3 \
       79F7026C690BAA50B92CD8B66A3AD3F4F22C4FED \
       9BA44C2621385CB966EBA586F72C284D731FABEE \
       A27677289986DB50844682F8ACB77FC2E86E29AC \
       A9C5DF4D22E99998D9875A5110C01C5A2F6059E7 \
       DCFD35E0BF8CA7344752DE8B6FB21E8933C60243 \
       F3A04C595DB5B6A5F1ECA43E3B7BBB100D811BBE \
       F7DA48BB64BCB84ECBA7EE6935CD23C10D498E23 \
  && wget --no-verbose "$TOMCAT_TGZ_URL" -O tomcat.tar.gz \
  && wget --no-verbose "$TOMCAT_TGZ_URL.asc" -O tomcat.tar.gz.asc \
  && gpg --verify tomcat.tar.gz.asc \
  && tar -xvf tomcat.tar.gz --strip-components=1 > /dev/null \
  && rm bin/*.bat \
  && rm tomcat.tar.gz*

#===================
# Guacamole web-app
#===================
# https://github.com/glyptodon/guacamole-server/releases
ENV GUACAMOLE_VERSION 0.9.6
ENV GUACAMOLE_WAR_SHA1 cfe41c7b2c6229db7bd10ae96f3844d9da19f8e4
ENV GUACAMOLE_HOME ${HOME}/guacamole
RUN mkdir -p ${GUACAMOLE_HOME}
# http://guac-dev.org/doc/gug/configuring-guacamole.html
COPY guacamole_home/* ${GUACAMOLE_HOME}/
# Disable Tomcat's manager application.
# e.g. to customize JVM's max heap size 256MB: -e JAVA_OPTS="-Xmx256m"
RUN cd ${CATALINA_HOME} && rm -rf webapps/* \
  && echo "${GUACAMOLE_WAR_SHA1}  ROOT.war" > webapps/ROOT.war.sha1 \
  && wget --no-verbose -O webapps/ROOT.war "http://sourceforge.net/projects/guacamole/files/current/binary/guacamole-${GUACAMOLE_VERSION}.war/download" \
  && cd webapps && sha1sum -c --quiet ROOT.war.sha1 && cd .. \
  && echo "export CATALINA_OPTS=\"${JAVA_OPTS}\"" >> bin/setenv.sh
#========================
# Guacamole server guacd
#========================
ENV GUACAMOLE_SERVER_SHA1 46d3a541129fb7cad744e4e319be1404781458de
RUN cd /tmp \
  && echo ${GUACAMOLE_SERVER_SHA1}  guacamole-server.tar.gz > guacamole-server.tar.gz.sha1 \
  && wget --no-verbose -O guacamole-server.tar.gz "http://sourceforge.net/projects/guacamole/files/current/source/guacamole-server-${GUACAMOLE_VERSION}.tar.gz/download" \
  && sha1sum -c --quiet guacamole-server.tar.gz.sha1 \
  && tar xzf guacamole-server.tar.gz \
  && rm guacamole-server.tar.gz* \
  && cd guacamole-server-${GUACAMOLE_VERSION} \
  && ./configure \
  && make \
  && sudo make install \
  && sudo ldconfig

#========================================================================
# Some configuration options that can be customized at container runtime
#========================================================================
ENV BIN_UTILS /bin-utils
ENV PATH ${PATH}:${BIN_UTILS}:${CATALINA_HOME}/bin
# Security requirements might prevent using sudo in the running container
ENV SUDO_ALLOWED true
ENV WITH_GUACAMOLE false
ENV WITH_SSH true
# JVM uses only 1/4 of system memory by default
ENV MEM_JAVA_PERCENT 80
ENV RETRY_START_SLEEP_SECS 0.1
ENV MAX_WAIT_RETRY_ATTEMPTS 9
ENV SCREEN_WIDTH 1900
ENV SCREEN_HEIGHT 1480
ENV SCREEN_MAIN_DEPTH 24
ENV SCREEN_DEPTH ${SCREEN_MAIN_DEPTH}+32
ENV DISPLAY_NUM 10
ENV DISPLAY :$DISPLAY_NUM
ENV XEPHYR_DISPLAY_NUM 11
ENV XEPHYR_DISPLAY :$DISPLAY_NUM
ENV SCREEN_NUM 0
# Even though you can change them below, don't worry too much about container
# internal ports since you can map them to the host via `docker run -p`
ENV SELENIUM_PORT 4444
ENV VNC_PORT 5900
# You can set the VNC password or leave null so a random password is generated:
# ENV VNC_PASSWORD topsecret
ENV SSHD_PORT 2222
ENV GUACAMOLE_SERVER_PORT 4822
# All tomcat ports can be customized if necessary
ENV TOMCAT_PORT 8484
ENV TOMCAT_SHUTDOWN_PORT 8485
ENV TOMCAT_AJP_PORT 8489
ENV TOMCAT_REDIRECT_PORT 8483
# Logs
ENV XVFB_LOG "/var/log/sele/Xvfb_headless.log"
ENV XMANAGER_LOG "/var/log/sele/xmanager.log"
ENV VNC_LOG "/var/log/sele/x11vnc_forever.log"
ENV SELENIUM_LOG "/var/log/sele/selenium-server-standalone.log"
ENV CATALINA_LOG "/var/log/sele/tomcat-server.log"
ENV GUACD_LOG "/var/log/sele/guacd-server.log"

#================================
# Expose Container's Directories
#================================
VOLUME /var/log

# Only expose ssh port given the other services are not secured
# forcing the user to open ssh tunnels or use docker run -p ports...
# EXPOSE ${SELENIUM_PORT} ${VNC_PORT} ${SSHD_PORT} ${TOMCAT_PORT}
EXPOSE ${SSHD_PORT}

#================
# Binary scripts
#================
ADD bin $BIN_UTILS

EXPOSE 80

WORKDIR /app
CMD ["/run.sh"]