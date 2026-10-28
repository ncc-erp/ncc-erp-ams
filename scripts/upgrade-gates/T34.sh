#!/usr/bin/env bash
# Gate for T34 - Laravel 11 - composer.json
gate_section "T34 - Laravel 11 composer.json"

assert_min_version "laravel/framework" "11.0.0"
assert_min_version "nesbot/carbon" "3.0.0"
assert_min_version "laravel/passport" "12.0.0"
