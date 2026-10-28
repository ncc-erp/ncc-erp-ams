#!/usr/bin/env bash
# Gate for T21 - L9 - facade/ignition replaced
gate_section "T21 - Laravel 9 ignition package"

assert_not_installed "facade/ignition"
assert_no_grep 'Facade\\Ignition' app config
