#!/usr/bin/env bash
# Gate for T39 - Laravel 13
gate_section "T39 - Laravel 13"

assert_min_version "laravel/framework" "13.0.0"
assert_no_advisory "laravel/framework"
