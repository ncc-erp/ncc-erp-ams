#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# common.sh - the gate that every task has to pass.
#
# It is executed for every `run.sh Txx` invocation unless --no-common is used
# for a task listed in partial.txt.
# ---------------------------------------------------------------------------

gate_common_checksum() {
    local lock="$GATE_DIR/checksums.sha256" output
    if [ ! -f "$lock" ]; then
        gate_record PASS "common: gate files are not locked yet (no checksums.sha256)"
        return 0
    fi
    output="$(cd "$GATE_ROOT" && sha256sum -c "$lock" 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: gate files match checksums.sha256"
        return 0
    fi
    printf '%s\n' "$output" | grep -v ': OK$' | head -20
    gate_record FAIL "common: gate files match checksums.sha256" "protected files were modified"
    return 1
}

gate_common_composer() {
    local output
    output="$(cd "$GATE_ROOT" && composer validate --no-check-publish --no-interaction 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: composer validate"
    else
        printf '%s\n' "$output" | tail -10
        gate_record FAIL "common: composer validate" "see output above"
    fi

    output="$(cd "$GATE_ROOT" && composer install --no-interaction --no-progress 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: composer install"
    else
        printf '%s\n' "$output" | tail -20
        gate_record FAIL "common: composer install" "see output above"
    fi
}

gate_common_lint() {
    local failures count
    failures="$(find "$GATE_ROOT/app" "$GATE_ROOT/config" "$GATE_ROOT/routes" "$GATE_ROOT/database" \
        -name '*.php' -type f -print0 2>/dev/null \
        | xargs -0 -n1 -P4 php -l 2>&1 | grep -v '^No syntax errors detected' || true)"
    count="$(printf '%s' "$failures" | grep -c . || true)"
    if [ "${count:-0}" -eq 0 ]; then
        gate_record PASS "common: php -l on app/ config/ routes/ database/"
        return 0
    fi
    printf '%s\n' "$failures" | head -20
    gate_record FAIL "common: php -l on app/ config/ routes/ database/" "$count file(s) with syntax errors"
    return 1
}

gate_common_database() {
    local output
    output="$(cd "$GATE_ROOT" && php artisan migrate:fresh --force 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: php artisan migrate:fresh --force"
    else
        printf '%s\n' "$output" | tail -25
        gate_record FAIL "common: php artisan migrate:fresh --force" "see output above"
        return 1
    fi

    # the application reads its configuration from the settings table, without
    # a settings row every artisan command that boots the application fails
    output="$(cd "$GATE_ROOT" && php artisan db:seed --class='Database\Seeders\SettingsSeeder' --force 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: the default settings are seeded"
    else
        printf '%s\n' "$output" | tail -15
        gate_record FAIL "common: the default settings are seeded" "see output above"
    fi

    # passport refuses to boot without its key pair
    if [ ! -f "$GATE_ROOT/storage/oauth-private.key" ] || [ ! -f "$GATE_ROOT/storage/oauth-public.key" ]; then
        output="$(cd "$GATE_ROOT" && php artisan passport:keys --force 2>&1)"
        if [ $? -eq 0 ]; then
            gate_record PASS "common: the passport keys are generated"
        else
            printf '%s\n' "$output" | tail -15
            gate_record FAIL "common: the passport keys are generated" "see output above"
        fi
    else
        gate_record PASS "common: the passport keys are present"
    fi
}

gate_common_artisan() {
    local cmd output version about
    version="$(framework_version)"

    # `php artisan about` only exists since Laravel 9, on Laravel 8 the
    # console kernel is checked with `php artisan --version`
    if version_ge "$version" "9.0"; then
        about="about"
    else
        about="--version"
    fi

    for cmd in "$about" "route:list" "config:cache" "view:cache"; do
        output="$(cd "$GATE_ROOT" && php artisan $cmd 2>&1)"
        if [ $? -eq 0 ]; then
            gate_record PASS "common: php artisan $cmd"
        else
            printf '%s\n' "$output" | tail -15
            gate_record FAIL "common: php artisan $cmd" "see output above"
        fi
    done

    for cmd in "config:clear" "view:clear"; do
        (cd "$GATE_ROOT" && php artisan $cmd >/dev/null 2>&1)
    done
}

gate_common_tests() {
    local junit output
    junit="$(mktemp --suffix=.xml)"
    output="$(cd "$GATE_ROOT" && php vendor/bin/phpunit --log-junit "$junit" 2>&1)"
    local code=$?
    printf '%s\n' "$output" | tail -15

    local result
    result="$(php "$GATE_DIR/known-failures.php" "$junit" "$GATE_DIR/known-failures.txt")"
    local status=$?
    printf '%s\n' "$result"

    # the report is kept, the task gates use it instead of starting another
    # phpunit process for every test class
    cp "$junit" "$GATE_JUNIT_REPORT"
    GATE_JUNIT_FILE="$GATE_JUNIT_REPORT"

    rm -f "$junit"

    if [ "$status" -ne 0 ]; then
        gate_record FAIL "common: full PHPUnit suite is green" "see failures above"
        return 1
    fi
    if [ "$code" -ne 0 ]; then
        gate_record FAIL "common: full PHPUnit suite is green" "phpunit exited with $code"
        return 1
    fi
    gate_record PASS "common: full PHPUnit suite is green"
    return 0
}

gate_common() {
    gate_section "Common gate"
    GATE_JUNIT_FILE=""

    gate_common_checksum
    gate_common_composer
    gate_common_lint
    gate_common_database
    gate_common_artisan
    gate_common_tests
    gate_common_restore_settings
}

gate_common_restore_settings() {
    # the test suite migrates the database from scratch, the settings row is
    # gone afterwards and every artisan command needs it
    local output
    output="$(cd "$GATE_ROOT" && php artisan db:seed --class='Database\Seeders\SettingsSeeder' --force 2>&1)"
    if [ $? -eq 0 ]; then
        gate_record PASS "common: the default settings are seeded again after the test suite"
    else
        printf '%s\n' "$output" | tail -15
        gate_record FAIL "common: the default settings are seeded again after the test suite" "see output above"
    fi
}
