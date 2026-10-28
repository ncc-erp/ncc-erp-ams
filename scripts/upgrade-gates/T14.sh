#!/usr/bin/env bash
# Gate for T14 - drop javiereguiluz/easyslugger
gate_section "T14 - javiereguiluz/easyslugger removed"

assert_not_installed "javiereguiluz/easyslugger"
assert_no_grep "EasySlugger" app config resources
