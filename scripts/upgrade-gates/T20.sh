#!/usr/bin/env bash
# Gate for T20 - Laravel 9 - composer.json
gate_section "T20 - Laravel 9 composer.json"

assert_min_version "laravel/framework" "9.0.0"
assert_min_version "laravel/passport" "11.0.0"
assert_not_installed "swiftmailer/swiftmailer"
