#!/usr/bin/env bash
# Gate for T02 - gate test system
gate_section "T02 - gate test system"

assert_file "scripts/upgrade-gates/run.sh"
assert_file "scripts/upgrade-gates/lib.sh"
assert_file "scripts/upgrade-gates/common.sh"
assert_file "scripts/upgrade-gates/partial.txt"
assert_file "docker-compose.test.yml"
assert_file "scripts/upgrade-gates/.php-version"

missing=""
for i in $(seq -w 1 43); do
    [ -f "scripts/upgrade-gates/T$i.sh" ] || missing="$missing T$i.sh"
done
if [ -z "$missing" ]; then
    gate_record PASS "gate scripts T01.sh .. T43.sh all exist"
else
    gate_record FAIL "gate scripts T01.sh .. T43.sh all exist" "missing:$missing"
fi

syntax_errors=""
for f in scripts/upgrade-gates/*.sh; do
    bash -n "$f" 2>&1 || syntax_errors="$syntax_errors $f"
done
if [ -z "$syntax_errors" ]; then
    gate_record PASS "bash -n on every gate script"
else
    gate_record FAIL "bash -n on every gate script" "syntax errors in:$syntax_errors"
fi

if bash "$GATE_DIR/run.sh" --selftest > /tmp/gate-selftest.log 2>&1; then
    gate_record PASS "run.sh --selftest passes"
else
    tail -30 /tmp/gate-selftest.log
    gate_record FAIL "run.sh --selftest passes" "see output above"
fi

require_approval T02
