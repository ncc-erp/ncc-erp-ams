#!/usr/bin/env bash
# Gate for T23 - L9 - fruitcake/laravel-cors removed
gate_section "T23 - Laravel 9 CORS middleware"

assert_not_installed "fruitcake/laravel-cors"
assert_no_grep "Fruitcake" app config
run_test_classes_once "CorsTest"
