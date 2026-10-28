#!/bin/sh
# Activates the PHP runtime requested by scripts/upgrade-gates/.php-version and
# then execs whatever the container was asked to run.
set -e

PHP_VERSION_FILE="${GATE_PHP_VERSION_FILE:-/var/www/html/scripts/upgrade-gates/.php-version}"

if [ ! -f "$PHP_VERSION_FILE" ]; then
    echo "entrypoint: cannot find $PHP_VERSION_FILE" >&2
    exit 1
fi

PHP_VER=$(tr -d ' \t\r\n' < "$PHP_VERSION_FILE")
case "$PHP_VER" in
    8.[0-9]) ;;
    *)
        echo "entrypoint: unsupported PHP version '$PHP_VER' in $PHP_VERSION_FILE" >&2
        exit 1
        ;;
esac

if [ ! -x "/usr/bin/php$PHP_VER" ]; then
    echo "entrypoint: PHP $PHP_VER is not installed in this image" >&2
    exit 1
fi

ln -sf "/usr/bin/php$PHP_VER" /usr/local/bin/php
ln -sf "/usr/bin/php-config$PHP_VER" /usr/local/bin/php-config
ln -sf "/usr/bin/phpize$PHP_VER" /usr/local/bin/phpize
if [ -x "/usr/sbin/php-fpm$PHP_VER" ]; then
    ln -sf "/usr/sbin/php-fpm$PHP_VER" /usr/local/bin/php-fpm
elif [ -x "/usr/bin/php-fpm$PHP_VER" ]; then
    ln -sf "/usr/bin/php-fpm$PHP_VER" /usr/local/bin/php-fpm
fi

echo "entrypoint: PHP $PHP_VER active ($(php -r 'echo PHP_VERSION;'))"

# the repository is bind mounted from the host, git refuses to work on it
# unless the directory is marked as safe
git config --global --add safe.directory /var/www/html 2>/dev/null || true

# storage/ and bootstrap/cache live in their own volume, make sure the
# directories the application writes to exist
mkdir -p \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/private \
    /var/www/html/bootstrap/cache \
    2>/dev/null || true

exec "$@"
