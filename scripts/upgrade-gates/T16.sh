#!/usr/bin/env bash
# Gate for T16 - replace unicodeveloper/laravel-password
gate_section "T16 - unicodeveloper/laravel-password replaced"

assert_not_installed "unicodeveloper/laravel-password"
assert_no_grep "dumbpwd|Unicodeveloper" app resources config
run_tests "PasswordRuleTest"
