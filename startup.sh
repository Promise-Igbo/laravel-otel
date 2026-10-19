#!/bin/bash
set -e

if ! php -m | grep -qi '^opentelemetry$'; then
    echo "Installing OpenTelemetry extension..."
    pecl install opentelemetry
    echo "extension=opentelemetry.so" > /usr/local/etc/php/conf.d/99-opentelemetry.ini
fi

echo "Starting PHP-FPM..."
exec php-fpm