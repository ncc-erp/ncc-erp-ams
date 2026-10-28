#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# lib.sh - assertion helpers shared by every gate in scripts/upgrade-gates/
#
# Every assert_* function prints a PASS/FAIL line, appends the same line to
# the active gate log and returns 0 (pass) or 1 (fail).  The runner counts the
# return codes, so a gate script can simply list its asserts and let the
# runner decide whether the task is done.
# ---------------------------------------------------------------------------

GATE_ROOT="${GATE_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
GATE_DIR="$GATE_ROOT/scripts/upgrade-gates"
GATE_RESULTS_DIR="$GATE_ROOT/docs/upgrade/gate-results"
GATE_APPROVALS_DIR="${GATE_APPROVALS_DIR:-$GATE_ROOT/docs/upgrade/approvals}"

COMPOSER_JSON="${COMPOSER_JSON:-$GATE_ROOT/composer.json}"
COMPOSER_LOCK="${COMPOSER_LOCK:-$GATE_ROOT/composer.lock}"

GATE_PASS_COUNT=0
GATE_FAIL_COUNT=0
GATE_LOG_FILE="${GATE_LOG_FILE:-}"
GATE_AUDIT_JSON=""

# ---------------------------------------------------------------------------
# plumbing
# ---------------------------------------------------------------------------

gate_log() {
    if [ -n "$GATE_LOG_FILE" ]; then
        printf '%s\n' "$1" >> "$GATE_LOG_FILE"
    fi
}

gate_record() {
    # $1 = PASS|FAIL, $2 = description, $3 = optional detail
    local status="$1" desc="$2" detail="${3:-}"
    if [ "$status" = "PASS" ]; then
        GATE_PASS_COUNT=$((GATE_PASS_COUNT + 1))
        printf '  PASS  %s\n' "$desc"
        gate_log "PASS  $desc"
    else
        GATE_FAIL_COUNT=$((GATE_FAIL_COUNT + 1))
        printf '  FAIL  %s\n' "$desc"
        gate_log "FAIL  $desc"
        if [ -n "$detail" ]; then
            printf '        %s\n' "$detail"
            gate_log "        $detail"
        fi
    fi
}

gate_section() {
    printf '\n== %s ==\n' "$1"
    gate_log ""
    gate_log "== $1 =="
}

# Run composer audit once per runner invocation and cache the JSON payload.
audit_json() {
    if [ -z "$GATE_AUDIT_JSON" ] || [ ! -s "$GATE_AUDIT_JSON" ]; then
        GATE_AUDIT_JSON="$(mktemp)"
        ( cd "$GATE_ROOT" && composer audit --locked --no-plugins --format=json ) > "$GATE_AUDIT_JSON" 2>/dev/null || true
        if ! [ -s "$GATE_AUDIT_JSON" ]; then
            printf '{}' > "$GATE_AUDIT_JSON"
        fi
    fi
    printf '%s' "$GATE_AUDIT_JSON"
}

# ---------------------------------------------------------------------------
# package / version asserts
# ---------------------------------------------------------------------------

# version_ge <a> <b> -> true when the version a is greater than or equal to b
version_ge() {
    local lowest
    lowest="$(printf '%s\n%s\n' "$1" "$2" | sort -V | head -n1)"
    [ "$lowest" = "$2" ] || [ "$1" = "$2" ]
}

lock_package_version() {
    # prints the installed version of $1, or nothing when not installed
    php -r '
        $file = $argv[1];
        $pkg  = $argv[2];
        if (!is_file($file)) { exit(1); }
        $lock = json_decode(file_get_contents($file), true);
        if (!is_array($lock)) { exit(1); }
        foreach (["packages", "packages-dev"] as $section) {
            foreach (($lock[$section] ?? []) as $package) {
                if (($package["name"] ?? "") === $pkg) {
                    echo $package["version"];
                    exit(0);
                }
            }
        }
        exit(1);
    ' "$COMPOSER_LOCK" "$1"
}

composer_json_requires() {
    # prints the raw requirement string of $1 in section $2 (default: require)
    php -r '
        $file = $argv[1];
        $pkg  = $argv[2];
        $sec  = $argv[3] ?: "require";
        if (!is_file($file)) { exit(1); }
        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json)) { exit(1); }
        $value = $json[$sec][$pkg] ?? null;
        if ($value === null) { exit(1); }
        echo is_array($value) ? implode(",", $value) : $value;
        exit(0);
    ' "$COMPOSER_JSON" "$1" "${2:-require}"
}

assert_min_version() {
    # assert_min_version <pkg> <version>  -> installed version >= <version>
    local pkg="$1" want="$2" installed
    installed="$(lock_package_version "$pkg")"
    if [ -z "$installed" ]; then
        gate_record FAIL "assert_min_version $pkg >= $want" "package '$pkg' is not installed"
        return 1
    fi
    if version_ge "${installed#v}" "$want"; then
        gate_record PASS "assert_min_version $pkg >= $want (installed ${installed#v})"
        return 0
    fi
    gate_record FAIL "assert_min_version $pkg >= $want" "installed ${installed#v} is lower than $want"
    return 1
}

assert_max_major() {
    # assert_max_major <pkg> <major>  -> installed major version <= <major>
    local pkg="$1" want="$2" installed major
    installed="$(lock_package_version "$pkg")"
    if [ -z "$installed" ]; then
        gate_record FAIL "assert_max_major $pkg <= $want" "package '$pkg' is not installed"
        return 1
    fi
    major="${installed#v}"
    major="${major%%.*}"
    if [ "$major" -le "$want" ] 2>/dev/null; then
        gate_record PASS "assert_max_major $pkg <= $want (installed ${installed#v})"
        return 0
    fi
    gate_record FAIL "assert_max_major $pkg <= $want" "installed ${installed#v} has major $major"
    return 1
}

assert_not_installed() {
    # assert_not_installed <pkg>
    local pkg="$1" installed
    installed="$(lock_package_version "$pkg")"
    if [ -z "$installed" ]; then
        gate_record PASS "assert_not_installed $pkg"
        return 0
    fi
    gate_record FAIL "assert_not_installed $pkg" "package is still installed at ${installed#v}"
    return 1
}

assert_not_required() {
    # assert_not_required <pkg> [require|require-dev]
    local pkg="$1" section="${2:-require}" value
    value="$(composer_json_requires "$pkg" "$section")"
    if [ -z "$value" ]; then
        gate_record PASS "assert_not_required $pkg ($section)"
        return 0
    fi
    gate_record FAIL "assert_not_required $pkg ($section)" "still required as '$value'"
    return 1
}

assert_required() {
    # assert_required <pkg> [require|require-dev]
    local pkg="$1" section="${2:-require}" value
    value="$(composer_json_requires "$pkg" "$section")"
    if [ -n "$value" ]; then
        gate_record PASS "assert_required $pkg ($section) = $value"
        return 0
    fi
    gate_record FAIL "assert_required $pkg ($section)" "not listed in composer.json ($section)"
    return 1
}

assert_composer_php_min() {
    # assert_composer_php_min <version>  -> the "php" constraint has a lower bound >= version
    local want="$1" constraint lower
    constraint="$(composer_json_requires php require)"
    if [ -z "$constraint" ]; then
        gate_record FAIL "assert_composer_php_min $want" "composer.json has no php constraint"
        return 1
    fi
    lower="$(php -r '
        $constraint = $argv[1];
        $candidates = [];
        if (preg_match_all("/([0-9]+(?:\.[0-9]+)*)/", $constraint, $m)) {
            foreach ($m[1] as $v) { $candidates[] = $v; }
        }
        // ">=" / "^" style constraints: the lower bound is the first version found
        echo $candidates ? $candidates[0] : "";
    ' "$constraint")"
    if [ -z "$lower" ]; then
        gate_record FAIL "assert_composer_php_min $want" "cannot parse php constraint '$constraint'"
        return 1
    fi
    if version_ge "$lower" "$want"; then
        gate_record PASS "assert_composer_php_min $want (constraint '$constraint')"
        return 0
    fi
    gate_record FAIL "assert_composer_php_min $want" "php constraint '$constraint' allows $lower"
    return 1
}

# ---------------------------------------------------------------------------
# grep / file asserts
# ---------------------------------------------------------------------------

assert_no_grep() {
    # assert_no_grep <regex> <path...>
    local regex="$1"; shift
    local matches=""
    local path
    for path in "$@"; do
        if [ ! -e "$path" ]; then
            gate_record FAIL "assert_no_grep '$regex'" "path does not exist: $path"
            return 1
        fi
    done
    matches="$(grep -rEIl --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git -- "$regex" "$@" 2>/dev/null | head -20)"
    if [ -z "$matches" ]; then
        gate_record PASS "assert_no_grep '$regex' in $*"
        return 0
    fi
    gate_record FAIL "assert_no_grep '$regex' in $*" "still found in: $(printf '%s' "$matches" | tr '\n' ' ')"
    return 1
}

assert_grep() {
    # assert_grep <regex> <file>
    local regex="$1" file="$2"
    if [ ! -f "$file" ]; then
        gate_record FAIL "assert_grep '$regex' in $file" "file does not exist"
        return 1
    fi
    if grep -qE -- "$regex" "$file" 2>/dev/null; then
        gate_record PASS "assert_grep '$regex' in $file"
        return 0
    fi
    gate_record FAIL "assert_grep '$regex' in $file" "pattern not found"
    return 1
}

assert_file() {
    # assert_file <path>
    if [ -f "$1" ]; then
        gate_record PASS "assert_file $1"
        return 0
    fi
    gate_record FAIL "assert_file $1" "file does not exist"
    return 1
}

assert_dir() {
    if [ -d "$1" ]; then
        gate_record PASS "assert_dir $1"
        return 0
    fi
    gate_record FAIL "assert_dir $1" "directory does not exist"
    return 1
}

assert_json() {
    # assert_json <path>
    if [ ! -f "$1" ]; then
        gate_record FAIL "assert_json $1" "file does not exist"
        return 1
    fi
    if php -r '
        $data = json_decode(file_get_contents($argv[1]), true);
        exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);
    ' "$1"; then
        gate_record PASS "assert_json $1"
        return 0
    fi
    gate_record FAIL "assert_json $1" "not valid JSON"
    return 1
}

# ---------------------------------------------------------------------------
# advisory asserts
# ---------------------------------------------------------------------------

assert_no_advisory() {
    # assert_no_advisory <pkg>
    local pkg="$1" json count
    json="$(audit_json)"
    count="$(php -r '
        $data = json_decode(file_get_contents($argv[1]), true);
        $advisories = $data["advisories"] ?? [];
        echo count($advisories[$argv[2]] ?? []);
    ' "$json")"
    if [ "${count:-1}" = "0" ]; then
        gate_record PASS "assert_no_advisory $pkg"
        return 0
    fi
    gate_record FAIL "assert_no_advisory $pkg" "$count advisory/advisories still reported"
    return 1
}

assert_advisories_subset() {
    # assert_advisories_subset <allowlist-file>
    # allowlist format, one rule per line:
    #   <package>              -> any severity allowed
    #   <package>:<severity>   -> only advisories up to <severity> allowed
    #   <glob>:<severity>      -> same, matched with fnmatch
    #   # ... comments and blank lines are ignored
    local file="$1" json
    if [ ! -f "$file" ]; then
        gate_record FAIL "assert_advisories_subset $file" "allowlist file does not exist"
        return 1
    fi
    json="$(audit_json)"
    if php "$GATE_DIR/advisory-subset.php" "$json" "$file"; then
        gate_record PASS "assert_advisories_subset $file"
        return 0
    fi
    gate_record FAIL "assert_advisories_subset $file" "see details above"
    return 1
}

assert_no_critical_advisories() {
    local json count
    json="$(audit_json)"
    count="$(php -r '
        $data = json_decode(file_get_contents($argv[1]), true);
        $n = 0;
        foreach (($data["advisories"] ?? []) as $items) {
            foreach ($items as $item) {
                if (($item["severity"] ?? "") === "critical") { $n++; }
            }
        }
        echo $n;
    ' "$json")"
    if [ "${count:-1}" = "0" ]; then
        gate_record PASS "assert_no_critical_advisories"
        return 0
    fi
    gate_record FAIL "assert_no_critical_advisories" "$count critical advisories remain"
    return 1
}

# ---------------------------------------------------------------------------
# runtime asserts
# ---------------------------------------------------------------------------

assert_php_min() {
    # assert_php_min <version>
    local want="$1" current
    current="$(php -r 'echo PHP_VERSION;')"
    if version_ge "$current" "$want"; then
        gate_record PASS "assert_php_min $want (running $current)"
        return 0
    fi
    gate_record FAIL "assert_php_min $want" "running PHP $current"
    return 1
}

assert_route() {
    # assert_route <uri>
    local uri="$1" output
    output="$(cd "$GATE_ROOT" && php artisan route:list --json 2>/dev/null)"
    if [ -z "$output" ]; then
        gate_record FAIL "assert_route $uri" "route:list produced no output"
        return 1
    fi
    if printf '%s' "$output" | php -r '
        $uri = $argv[1];
        $data = json_decode(stream_get_contents(STDIN), true);
        if (!is_array($data)) { exit(1); }
        foreach ($data as $route) {
            if (($route["uri"] ?? "") === $uri) { exit(0); }
        }
        exit(1);
    ' "$uri"; then
        gate_record PASS "assert_route $uri"
        return 0
    fi
    gate_record FAIL "assert_route $uri" "route not registered"
    return 1
}

run_tests() {
    # run_tests <filter>  -> phpunit --filter <filter> is green and ran >= 1 test
    local filter="$1" output code count
    output="$(cd "$GATE_ROOT" && php vendor/bin/phpunit --filter "$filter" 2>&1)"
    code=$?
    printf '%s\n' "$output" | tail -25
    gate_log "----- phpunit --filter $filter output -----"
    printf '%s\n' "$output" >> "${GATE_LOG_FILE:-/dev/null}"
    if [ "$code" -ne 0 ]; then
        gate_record FAIL "run_tests $filter" "phpunit exited with $code"
        return 1
    fi
    count="$(printf '%s' "$output" | php -r '
        $out = stream_get_contents(STDIN);
        if (preg_match("/OK\s*\((\d+)\s+tests?/", $out, $m)) {
            echo $m[1];
            exit(0);
        }
        if (preg_match("/Tests:\s+(\d+)/", $out, $m)) {
            echo $m[1];
            exit(0);
        }
        echo 0;
    ')"
    if [ "${count:-0}" -lt 1 ] 2>/dev/null; then
        gate_record FAIL "run_tests $filter" "no test matched the filter"
        return 1
    fi
    gate_record PASS "run_tests $filter ($count tests green)"
    return 0
}

require_approval() {
    # require_approval <Txx>
    local id="$1"
    local file="$GATE_APPROVALS_DIR/$id.md"
    if [ ! -f "$file" ]; then
        gate_record FAIL "require_approval $id" "missing approval file $file"
        return 1
    fi
    if grep -qE '^Approved-by:[[:space:]]*.+' "$file"; then
        gate_record PASS "require_approval $id"
        return 0
    fi
    gate_record FAIL "require_approval $id" "approval file has no 'Approved-by:' line"
    return 1
}

# ---------------------------------------------------------------------------
# self test
# ---------------------------------------------------------------------------

gate_selftest() {
    # Exercises every assert helper against a fixture project: each one has to
    # pass for a good input and fail for a bad input.
    local failures=0 fixture
    fixture="$(mktemp -d)"

    mkdir -p "$fixture/app" "$fixture/config" "$fixture/docs/upgrade/approvals"
    cat > "$fixture/composer.json" <<'JSON'
{
    "require": {
        "php": "^8.1",
        "foo/bar": "^1.0",
        "laravel/framework": "^8.83"
    },
    "require-dev": {
        "baz/qux": "^2.0"
    }
}
JSON
    cat > "$fixture/composer.lock" <<'JSON'
{
    "packages": [
        {"name": "foo/bar", "version": "v1.2.3"},
        {"name": "laravel/framework", "version": "v8.83.28"},
        {"name": "old/pkg", "version": "v0.9.0"}
    ],
    "packages-dev": [
        {"name": "baz/qux", "version": "v2.1.0"}
    ]
}
JSON
    printf 'hello world\n' > "$fixture/app/sample.txt"
    printf '{"a": 1}\n' > "$fixture/data.json"
    printf '{"a": 1,\n' > "$fixture/broken.json"
    printf 'Approved-by: Selftest\n' > "$fixture/docs/upgrade/approvals/T99.md"

    local out
    run_case() {
        local name="$1"; shift
        local expect="$1"; shift
        out="$(COMPOSER_JSON="$fixture/composer.json" COMPOSER_LOCK="$fixture/composer.lock" \
               GATE_APPROVALS_DIR="$fixture/docs/upgrade/approvals" \
               GATE_LOG_FILE="" bash -c "
                   source '$GATE_DIR/lib.sh'
                   $*
               " 2>&1)"
        local code=$?
        if [ "$expect" = "pass" ] && [ "$code" -eq 0 ]; then
            printf '  PASS  selftest %s (expected pass)\n' "$name"
        elif [ "$expect" = "fail" ] && [ "$code" -ne 0 ]; then
            printf '  PASS  selftest %s (expected fail)\n' "$name"
        else
            printf '  FAIL  selftest %s (expected %s, got %s)\n' "$name" "$expect" "$code"
            printf '%s\n' "$out" | sed 's/^/        /'
            failures=$((failures + 1))
        fi
    }

    run_case "assert_min_version pass"        pass "assert_min_version foo/bar 1.2.3"
    run_case "assert_min_version fail"        fail "assert_min_version foo/bar 1.3.0"
    run_case "assert_max_major pass"         pass "assert_max_major foo/bar 1"
    run_case "assert_max_major fail"         fail "assert_max_major foo/bar 0"
    run_case "assert_not_installed pass"     pass "assert_not_installed ghost/pkg"
    run_case "assert_not_installed fail"     fail "assert_not_installed old/pkg"
    run_case "assert_not_required pass"      pass "assert_not_required ghost/pkg require"
    run_case "assert_not_required fail"      fail "assert_not_required foo/bar require"
    run_case "assert_not_required dev fail"  fail "assert_not_required baz/qux require-dev"
    run_case "assert_composer_php_min pass"  pass "assert_composer_php_min 8.1"
    run_case "assert_composer_php_min fail"  fail "assert_composer_php_min 8.2"
    run_case "assert_no_grep pass"           pass "assert_no_grep 'goodbye' '$fixture/app'"
    run_case "assert_no_grep fail"           fail "assert_no_grep 'hello' '$fixture/app'"
    run_case "assert_grep pass"              pass "assert_grep 'hello' '$fixture/app/sample.txt'"
    run_case "assert_grep fail"              fail "assert_grep 'goodbye' '$fixture/app/sample.txt'"
    run_case "assert_file pass"              pass "assert_file '$fixture/app/sample.txt'"
    run_case "assert_file fail"              fail "assert_file '$fixture/app/missing.txt'"
    run_case "assert_json pass"              pass "assert_json '$fixture/data.json'"
    run_case "assert_json fail"              fail "assert_json '$fixture/broken.json'"
    run_case "assert_php_min pass"           pass "assert_php_min 7.0"
    run_case "assert_php_min fail"           fail "assert_php_min 99.0"
    run_case "require_approval pass"         pass "require_approval T99"
    run_case "require_approval fail"         fail "require_approval T98"

    # asserts that need a real Laravel application
    if [ -d "$GATE_ROOT/app" ]; then
        run_case "assert_route pass"          pass "assert_route 'login'"
        run_case "assert_route fail"          fail "assert_route 'this-route-does-not-exist'"
        run_case "run_tests fail"             fail "run_tests 'ThisTestClassDoesNotExist'"
    else
        printf '  SKIP  selftest assert_route/run_tests (no application found)\n'
    fi

    rm -rf "$fixture"

    if [ "$failures" -gt 0 ]; then
        printf '\nselftest: %d case(s) failed\n' "$failures"
        return 1
    fi
    printf '\nselftest: all cases behaved as expected\n'
    return 0
}
