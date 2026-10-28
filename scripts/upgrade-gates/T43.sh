#!/usr/bin/env bash
# Gate for T43 - final audit, report and pull request
gate_section "T43 - final audit and report"

assert_json "docs/upgrade/audit-after.json"
assert_file "docs/upgrade/REPORT.md"

require_approval T43
