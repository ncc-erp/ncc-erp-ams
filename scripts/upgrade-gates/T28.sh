#!/usr/bin/env bash
# Gate for T28 - Laravel 10 - composer.json
gate_section "T28 - Laravel 10 composer.json"

assert_min_version "laravel/framework" "10.48.29"
assert_min_version "phpunit/phpunit" "10.0.0"
