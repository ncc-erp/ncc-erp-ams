#!/usr/bin/env bash
# Gate for T36 - L11 - Carbon 3
gate_section "T36 - Laravel 11 Carbon 3"

run_tests "DateCalculationTest"

unsafe="$(grep -rEn 'diffIn[A-Za-z]*\(' app --include='*.php' \
    | grep -vE 'absolute|abs\(|\(int\)|carbon3-ok' | head -20 || true)"

if [ -z "$unsafe" ]; then
    gate_record PASS "every diffIn* call in app/ handles the Carbon 3 float/signed result"
else
    printf '%s\n' "$unsafe"
    gate_record FAIL "every diffIn* call in app/ handles the Carbon 3 float/signed result" "see the calls above"
fi
