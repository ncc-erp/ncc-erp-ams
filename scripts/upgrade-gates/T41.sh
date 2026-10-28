#!/usr/bin/env bash
# Gate for T41 - frontend build and assets
gate_section "T41 - frontend build"

if ! command -v npm >/dev/null 2>&1; then
    gate_record FAIL "npm ci and npm run production succeed" "npm is not available in the test image"
else
    if (cd "$GATE_ROOT" && npm ci) > /tmp/gate-t41-ci.log 2>&1; then
        gate_record PASS "npm ci succeeds"
    else
        tail -20 /tmp/gate-t41-ci.log
        gate_record FAIL "npm ci succeeds" "see output above"
    fi

    if (cd "$GATE_ROOT" && npm run production) > /tmp/gate-t41-build.log 2>&1; then
        gate_record PASS "npm run production succeeds"
    else
        tail -20 /tmp/gate-t41-build.log
        gate_record FAIL "npm run production succeeds" "see output above"
    fi
fi

assert_file "public/mix-manifest.json"
assert_file "docs/upgrade/npm-audit.txt"
