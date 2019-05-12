#!/bin/sh
set -e

# Wait for the database
while ! mysqladmin ping -hmariadb -u$MYSQL_USER -p$MYSQL_PASSWORD --silent; do

    echo "MariaDB container might not be ready yet... sleeping..."
    sleep 3
done

# Ensure we have vendor/ ready
if [ ! -f /var/www/vendor/autoload.php ]; then

    echo "Eveapi App container might not be ready yet... sleeping..."
    chown -R www-data:www-data storage
    composer install
    #php artisan key:generate
    #php artisan vendor:publish --force --all

    #php artisan migrate

    #sleep 30
fi

echo "test"
