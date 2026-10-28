#!/usr/bin/env bash
# Gate for T40 - clean up dependencies and update every package
gate_section "T40 - dependency cleanup"

if php "$GATE_DIR/accepted-risks.php" "$(audit_json)" "docs/upgrade/accepted-risks.md"; then
    gate_record PASS "every remaining advisory is documented in docs/upgrade/accepted-risks.md"
else
    gate_record FAIL "every remaining advisory is documented in docs/upgrade/accepted-risks.md" "see output above"
fi

if (cd "$GATE_ROOT" && composer audit --locked --abandoned=fail --no-plugins --format=summary) > /tmp/gate-t40-abandoned.log 2>&1; then
    gate_record PASS "composer audit --abandoned=fail is clean"
else
    tail -20 /tmp/gate-t40-abandoned.log
    gate_record FAIL "composer audit --abandoned=fail is clean" "see output above"
fi

if [ ! -s "$GATE_DIR/known-failures.txt" ] || ! grep -qvE '^\s*(#|$)' "$GATE_DIR/known-failures.txt"; then
    gate_record PASS "scripts/upgrade-gates/known-failures.txt is empty"
else
    gate_record FAIL "scripts/upgrade-gates/known-failures.txt is empty" "the list still contains entries"
fi
