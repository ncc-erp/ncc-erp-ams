#!/usr/bin/env bash
# Gate for T01 - branch & baseline audit
gate_section "T01 - branch and baseline audit"

branch="$(git -C "$GATE_ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null)"
if [ "$branch" = "chore/upgrade-php-laravel" ]; then
    gate_record PASS "branch is chore/upgrade-php-laravel"
else
    gate_record FAIL "branch is chore/upgrade-php-laravel" "current branch: ${branch:-unknown}"
fi

assert_json "docs/upgrade/audit-before.json"
