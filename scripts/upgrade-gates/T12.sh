#!/usr/bin/env bash
# Gate for T12 - mid-term audit
gate_section "T12 - mid-term audit"

assert_json "docs/upgrade/audit-l8-patched.json"
assert_advisories_subset "docs/upgrade/advisories-allowed-l8.txt"
assert_no_critical_advisories

require_approval T12
