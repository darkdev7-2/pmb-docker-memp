FROM ubuntu:20.04

LABEL maintainer="PMB Deployment"
LABEL description="Apache 2 + PHP 7.4 + PMB 7.4.1"

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_LOG_DIR=/var/log/apache2

# -------------------------------------------------------
# 1. Paquets système + PHP 7.4 (dépôts natifs Ubuntu 20.04)
#    Pas de PPA tiers requis : PHP 7.4 est inclus dans focal.
# -------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libapache2-mod-php7.4 \
    php7.4 \
    php7.4-mysql \
    php7.4-mbstring \
    php7.4-gd \
    php7.4-xsl \
    php7.4-curl \
    php7.4-intl \
    php7.4-soap \
    php7.4-zip \
    php7.4-bz2 \
    php7.4-xml \
    php7.4-xmlrpc \
    php7.4-apcu \
    php-imagick \
    php7.4-ldap \
    php7.4-sqlite3 \
    php7.4-dev \
    php-pear \
    yaz \
    libyaz-dev \
    libyaz5 \
    libxslt-dev \
    libfreetype6-dev \
    libpng-dev \
    wget \
    unzip \
    curl \
    nano \
    openssl \
    git \
    build-essential \
    pkg-config \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -------------------------------------------------------
# 2. Extension PHP YAZ (Z39.50) — compilée depuis source vendorisée
#    Vendorisé depuis pecl.php.net/get/yaz-1.2.4.tgz
# -------------------------------------------------------
COPY vendor/yaz.tgz /tmp/yaz.tgz
RUN tar xz -C /tmp -f /tmp/yaz.tgz \
    && cd /tmp/yaz-1.2.4 \
    && phpize7.4 \
    && ./configure --with-php-config=/usr/bin/php-config7.4 \
    && make -j$(nproc) \
    && make install \
    && echo "extension=yaz.so" >> /etc/php/7.4/apache2/php.ini \
    && echo "extension=yaz.so" >> /etc/php/7.4/cli/php.ini \
    && rm -rf /tmp/yaz.tgz /tmp/yaz-1.2.4

# -------------------------------------------------------
# 3. Activation des modules Apache
# -------------------------------------------------------
RUN a2enmod ssl rewrite php7.4 headers

# -------------------------------------------------------
# 4. Ports Apache (9001 HTTP / 9002 HTTPS)
# -------------------------------------------------------
RUN sed -i 's/Listen 80/Listen 9001/' /etc/apache2/ports.conf \
    && sed -i 's/Listen 443/Listen 9002/' /etc/apache2/ports.conf

# -------------------------------------------------------
# 5. Installation de PMB 7.4.1 (source vendorisée ou téléchargée)
# -------------------------------------------------------
COPY vendor/pmb7.4.1.zip /tmp/pmb.zip
RUN mkdir -p /var/www/html/pmb \
    && unzip -q /tmp/pmb.zip -d /tmp/pmb_src \
    && cp -R /tmp/pmb_src/pmb/. /var/www/html/pmb/ \
    && rm -rf /tmp/pmb.zip /tmp/pmb_src

# Patch verif.class.php : version_compare("10.4.34","10.4","<=") = false
# On étend le max à 10.4.99 pour accepter toutes les sous-versions 10.4.x
COPY patches/verif.class.php /var/www/html/pmb/tables/requirements/classes/verif.class.php

# Logos des ministères béninois affichés sur la page d'accueil OPAC
COPY patches/images/logo_memp.png /var/www/html/pmb/opac_css/images/site/logo_memp.png
COPY patches/images/logo_secondaire.png /var/www/html/pmb/opac_css/images/site/logo_secondaire.png

# Patch common.tpl.php : ajout du bandeau logos ministères dans l'OPAC
COPY patches/templates/common.tpl.php /var/www/html/pmb/opac_css/includes/templates/common.tpl.php

# Patch templates admin : logos ministères sur login + interface principale
COPY patches/admin_templates/index.tpl.php /var/www/html/pmb/includes/templates/index.tpl.php
COPY patches/admin_templates/common.tpl.php /var/www/html/pmb/includes/templates/common.tpl.php

RUN chown -R www-data:www-data /var/www/html/pmb \
    && chmod -R 755 /var/www/html/pmb

# -------------------------------------------------------
# 6. Certificat SSL auto-signé (remplacer par Let's Encrypt en prod)
# -------------------------------------------------------
RUN mkdir -p /etc/ssl/pmb \
    && openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
         -keyout /etc/ssl/pmb/server.key \
         -out    /etc/ssl/pmb/server.crt \
         -subj "/C=BJ/ST=Oueme/L=Porto-Novo/O=PMB/CN=pmb-app" 2>/dev/null

# -------------------------------------------------------
# 7. Configuration Apache
# -------------------------------------------------------
COPY conf/apache-pmb.conf /etc/apache2/sites-available/pmb.conf
RUN a2ensite pmb \
    && a2dissite 000-default || true

# -------------------------------------------------------
# 8. Configuration PHP (surchargée par le volume mount)
# -------------------------------------------------------
COPY conf/php.ini /etc/php/7.4/apache2/php.ini

EXPOSE 9001 9002

CMD ["apachectl", "-D", "FOREGROUND"]
