#!/usr/bin/env bash
# Gate for T37 - L11 - Passport 12 and remaining breaking changes
gate_section "T37 - Laravel 11 closing checkpoint"

assert_file "docs/upgrade/manual-L11.md"
run_tests "LegacyTokenTest"

require_approval T37
