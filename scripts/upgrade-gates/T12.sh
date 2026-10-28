#!/usr/bin/env bash
# Gate for T12 - mid-term audit
gate_section "T12 - mid-term audit"

assert_json "docs/upgrade/audit-l8-patched.json"
assert_advisories_subset "docs/upgrade/advisories-allowed-l8.txt"

# The critical advisory of laravel/framework is only closed by the major
# upgrade of the framework (T20 - T39) and the one of aws/aws-sdk-php by the
# runtime upgrade to PHP 8.1 (T19). Both are documented in
# docs/upgrade/REPORT.md.
assert_no_critical_advisories "laravel/framework" "aws/aws-sdk-php"

require_approval T12
