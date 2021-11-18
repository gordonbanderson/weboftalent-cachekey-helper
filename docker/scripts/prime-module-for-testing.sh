#!/bin/bash

composer require --prefer-source --no-update silverstripe/recipe-cms:4.x-dev
composer install --prefer-source --no-interaction --no-progress --no-suggest --optimize-autoloader --verbose --profile
