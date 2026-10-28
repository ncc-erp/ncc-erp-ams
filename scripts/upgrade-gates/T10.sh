#!/usr/bin/env bash
# Gate for T10 - patch Passport / JWT / oauth2-server
gate_section "T10 - Passport and JWT patched"

assert_min_version "laravel/passport" "10.4.0"
assert_min_version "firebase/php-jwt" "6.0.0"
assert_no_advisory "league/oauth2-server"
run_tests "PassportApiTest"
