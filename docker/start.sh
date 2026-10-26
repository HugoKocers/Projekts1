#!/bin/sh
set -eu

export PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\\*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
php /usr/local/bin/seed-game-if-empty.php

exec apache2-foreground
