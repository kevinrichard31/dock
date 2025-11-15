#!/bin/sh

# Attendre que MariaDB soit prêt
echo "Waiting for MariaDB to be ready..."
while ! nc -z mariadb 3306; do
  sleep 1
done
echo "MariaDB is ready!"

# Lancer l'initialisation PHP
echo "Running PHP initialization..."
php /var/www/html/init.php

# Lancer PHP-FPM
exec php-fpm
