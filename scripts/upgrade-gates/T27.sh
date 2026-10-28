#!/usr/bin/env bash
# Gate for T27 - L9 - remaining breaking changes
gate_section "T27 - Laravel 9 closing checkpoint"

assert_file "docs/upgrade/manual-L9.md"

require_approval T27
