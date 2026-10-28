#!/usr/bin/env bash
# Gate for T18 - replace or remove Codeception
gate_section "T18 - Codeception module-laravel5 removed"

assert_not_installed "codeception/module-laravel5"
assert_not_installed "phpunit/php-token-stream"
