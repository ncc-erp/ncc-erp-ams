#!/usr/bin/env bash
# Gate for T07 - update Laravel 8 to the last 8.x release
gate_section "T07 - Laravel 8 fully patched"

assert_min_version "laravel/framework" "8.83.28"
assert_min_version "symfony/http-foundation" "5.4.54"
assert_min_version "symfony/mime" "5.4.52"
assert_min_version "symfony/routing" "5.4.53"
assert_min_version "symfony/http-kernel" "5.4.20"
