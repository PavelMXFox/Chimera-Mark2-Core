#!/bin/bash

php /var/www/html/cli/installPackages.php
php /var/www/html/cli/migration.php
php /var/www/html/cli/initialize.php
