#!/usr/bin/env bash
# Gate for T13 - drop patchwork/utf8
gate_section "T13 - patchwork/utf8 removed"

assert_not_installed "patchwork/utf8"
assert_no_grep "Patchwork" app config resources
