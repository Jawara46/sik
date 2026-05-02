#!/bin/zsh
set -euo pipefail
PHP_CGI_BIN="/Applications/MAMP/bin/php/php8.2.0/bin/php-cgi"
PHP_INI="/Applications/MAMP/bin/php/php8.2.0/conf/php.ini"
FASTCGI_HOST="127.0.0.1:9072"
export PHP_FCGI_CHILDREN="4"
export PHP_FCGI_MAX_REQUESTS="500"
exec "$PHP_CGI_BIN" -b "$FASTCGI_HOST" -c "$PHP_INI"
