#!/bin/sh

composer install

if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate
fi

php artisan migrate

chown -R laravel:laravel storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec php-fpm