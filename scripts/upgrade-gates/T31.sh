#!/usr/bin/env bash
# Gate for T31 - raise the runtime to PHP 8.3
gate_section "T31 - runtime raised to PHP 8.3"

assert_php_min "8.3"
assert_composer_php_min "8.3"

phpunit_flags="--display-deprecations"
if php vendor/bin/phpunit --help 2>/dev/null | grep -q -- "--fail-on-deprecation"; then
    phpunit_flags="$phpunit_flags --fail-on-deprecation"
fi

output="$(cd "$GATE_ROOT" && php -d error_reporting=E_ALL vendor/bin/phpunit $phpunit_flags 2>&1)"
printf '%s\n' "$output" | tail -5

app_deprecations="$(printf '%s\n' "$output" \
    | grep -E "app/[^ ]*\.php:[0-9]+" \
    | grep -iE "deprecat" | head -20 || true)"

if [ -z "$app_deprecations" ]; then
    gate_record PASS "no PHP deprecation is triggered by code in app/ (vendor deprecations are ignored)"
else
    printf '%s\n' "$app_deprecations"
    gate_record FAIL "no PHP deprecation is triggered by code in app/" "see the deprecations above"
fi
