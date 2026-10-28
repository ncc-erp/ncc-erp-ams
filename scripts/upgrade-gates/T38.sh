#!/usr/bin/env bash
# Gate for T38 - Laravel 12
gate_section "T38 - Laravel 12"

assert_min_version "laravel/framework" "12.0.0"
assert_no_advisory "laravel/framework"
