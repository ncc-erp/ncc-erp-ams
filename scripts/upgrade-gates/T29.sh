#!/usr/bin/env bash
# Gate for T29 - L10 - drop $dates
gate_section "T29 - Laravel 10 date casts"

assert_no_grep 'protected \$dates' app
