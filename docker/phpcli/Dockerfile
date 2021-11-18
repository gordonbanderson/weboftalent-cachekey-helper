FROM php:7.4-cli-buster

RUN apt-get -y update && apt -y upgrade
RUN apt-get -y install figlet git zip unzip libicu-dev default-mysql-client  curl software-properties-common
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt -y install nodejs
RUN docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN apt-get -y autoremove && apt-get -y clean

# alter bash prompt
ENV PS1A="\u@silverstripe-freetext-search.test:\w> "
RUN echo 'PS1=$PS1A' >> ~/.bashrc

# intro message when attaching to shell
RUN echo 'figlet -w 120 SilverStripe Free Text Search' >> ~/.bashrc

# install composer - see https://medium.com/@c.harrison/speedy-composer-installs-in-docker-builds-41eea6d0172b
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

RUN echo "alias prime-module-for-testing='composer require --prefer-source --no-update silverstripe/recipe-cms:4.x-dev && composer install --prefer-source --no-interaction --no-progress --no-suggest --optimize-autoloader --verbose --profile'" >> ~/.bashrc
RUN echo "alias generate-html-coverage='phpdbg -qrr vendor/bin/phpunit -d memory_limit=4G --coverage-html report   tests/'" >> ~/.bashrc
RUN echo "" >> ~/.bashrc

# Prevent the container from exiting
CMD tail -f /dev/null
