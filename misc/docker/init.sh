#!/usr/bin/env bash

php5-fpm -c /etc/php5/fpm
nginx -g 'daemon off;'
