#!/usr/bin/env bash
# Gate for T26 - L9 - Passport 11
gate_section "T26 - Laravel 9 Passport 11"

assert_no_grep 'Passport::routes\(' app
assert_route "oauth/token"
assert_route "oauth/personal-access-tokens"
run_tests "PassportApiTest"
