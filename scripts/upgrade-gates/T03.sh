#!/usr/bin/env bash
# Gate for T03 - production environment record
gate_section "T03 - production environment record"

assert_file "docs/upgrade/environment.md"
