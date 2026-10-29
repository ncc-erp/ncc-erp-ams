#!/usr/bin/env bash
# Gate for T19 - raise the runtime to PHP 8.1
gate_section "T19 - runtime raised to PHP 8.1"

assert_php_min "8.1"
assert_composer_php_min "8.1"
assert_no_grep "php:7|php7|PHP_VERSION=7|php:8\.0" Dockerfile Dockerfile.alpine Dockerfile.fpm-alpine Dockerfile.php8.1

# the version of aws/aws-sdk-php that fixes its advisory requires PHP 8.1, so
# the advisory can only be closed after the runtime has been raised
assert_no_advisory "aws/aws-sdk-php"
