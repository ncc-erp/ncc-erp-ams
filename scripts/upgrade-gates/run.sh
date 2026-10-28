#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# run.sh - runner for the PHP/Laravel upgrade gate suite.
#
#   run.sh T07              common gate + the gates of every task up to T07
#   run.sh T07 --only       only the gate of T07 (for quick experiments, it
#                           does not mark the task as done)
#   run.sh T24 --no-common  common gate is skipped, but only for tasks that
#                           are listed in partial.txt
#   run.sh --selftest       run the self test of the assertion library
#   run.sh --lock Txx       (re)create checksums.sha256, requires the
#                           approval file docs/upgrade/approvals/Txx.md
#
# The result of every run is written to docs/upgrade/gate-results/Txx.log.
# ---------------------------------------------------------------------------
set -uo pipefail

GATE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
export GATE_ROOT

GATE_DIR="$GATE_ROOT/scripts/upgrade-gates"
source "$GATE_DIR/lib.sh"

TASK=""
ONLY=0
NO_COMMON=0
SELFTEST=0
DO_LOCK=0

while [ $# -gt 0 ]; do
    case "$1" in
        --selftest)  SELFTEST=1 ;;
        --only)      ONLY=1 ;;
        --no-common) NO_COMMON=1 ;;
        --lock)
            DO_LOCK=1
            shift
            TASK="$1"
            ;;
        -h|--help)
            sed -n '2,20p' "$0"
            exit 0
            ;;
        *)
            TASK="$1"
            ;;
    esac
    shift
done

if [ "$SELFTEST" -eq 1 ]; then
    gate_selftest
    exit $?
fi

if [ -z "$TASK" ]; then
    echo "usage: run.sh <Txx> [--only] [--no-common] | run.sh --selftest | run.sh --lock <Txx>" >&2
    exit 2
fi

if ! printf '%s' "$TASK" | grep -qE '^T[0-9]{2}$'; then
    echo "invalid task id '$TASK' (expected T01..T43)" >&2
    exit 2
fi

TASK_NUM=$((10#${TASK#T}))

if [ "$TASK_NUM" -lt 1 ] || [ "$TASK_NUM" -gt 43 ]; then
    echo "task id '$TASK' is out of range (T01..T43)" >&2
    exit 2
fi

# ---------------------------------------------------------------------------
# --lock
# ---------------------------------------------------------------------------
if [ "$DO_LOCK" -eq 1 ]; then
    APPROVAL="$GATE_ROOT/docs/upgrade/approvals/$TASK.md"
    if [ ! -f "$APPROVAL" ]; then
        echo "cannot lock: $APPROVAL does not exist" >&2
        exit 1
    fi
    if ! grep -qE '^Approved-by:[[:space:]]*.+' "$APPROVAL"; then
        echo "cannot lock: $APPROVAL has no 'Approved-by:' line" >&2
        exit 1
    fi

    LOCK_FILE="$GATE_DIR/checksums.sha256"
    ( cd "$GATE_ROOT" && {
        find scripts/upgrade-gates -type f \
            ! -name '.php-version' \
            ! -name 'checksums.sha256' \
            ! -name 'known-failures.txt' \
            -print0 \
        | sort -z \
        | xargs -0 sha256sum
        [ -d tests/Feature/Upgrade ] && find tests/Feature/Upgrade -type f -print0 | sort -z | xargs -0 sha256sum
        [ -f tests/fixtures/schema-prod.sql ] && sha256sum tests/fixtures/schema-prod.sql
        sha256sum docker-compose.test.yml
    } ) > "$LOCK_FILE"

    echo "locked $(wc -l < "$LOCK_FILE") files into $LOCK_FILE"
    exit 0
fi

# ---------------------------------------------------------------------------
# run the gates
# ---------------------------------------------------------------------------
mkdir -p "$GATE_ROOT/docs/upgrade/gate-results"
GATE_LOG_FILE="$GATE_ROOT/docs/upgrade/gate-results/$TASK.log"
: > "$GATE_LOG_FILE"
export GATE_LOG_FILE

{
    echo "Upgrade gate run"
    echo "task:   $TASK"
    echo "when:   $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
    echo "php:    $(php -r 'echo PHP_VERSION;')"
    echo "branch: $(git -C "$GATE_ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo unknown)"
    echo "commit: $(git -C "$GATE_ROOT" rev-parse --short HEAD 2>/dev/null || echo unknown)"
    echo
} >> "$GATE_LOG_FILE"

echo "=== upgrade gate: $TASK ==="
echo "log: docs/upgrade/gate-results/$TASK.log"
echo

if [ "$NO_COMMON" -eq 1 ]; then
    if ! grep -qE "^${TASK}($|[[:space:]])" "$GATE_DIR/partial.txt" 2>/dev/null; then
        echo "task $TASK is not allowed to use --no-common" >&2
        exit 2
    fi
fi

if [ "$ONLY" -eq 0 ] && [ "$NO_COMMON" -eq 0 ]; then
    source "$GATE_DIR/common.sh"
    gate_common
fi

if [ "$ONLY" -eq 1 ]; then
    GATES="$TASK"
else
    GATES="$(for i in $(seq 1 $TASK_NUM); do printf 'T%02d ' "$i"; done)"
fi

for gate in $GATES; do
    gate_file="$GATE_DIR/$gate.sh"
    if [ ! -f "$gate_file" ]; then
        echo "gate file $gate_file is missing" >&2
        exit 2
    fi
    gate_section "Gate $gate"
    source "$gate_file"
done

echo
if [ "$GATE_FAIL_COUNT" -gt 0 ]; then
    echo "=== $TASK: FAIL ($GATE_FAIL_COUNT failed, $GATE_PASS_COUNT passed) ==="
    printf '\n=== %s: FAIL (%d failed, %d passed) ===\n' "$TASK" "$GATE_FAIL_COUNT" "$GATE_PASS_COUNT" >> "$GATE_LOG_FILE"
    exit 1
fi

echo "=== $TASK: PASS ($GATE_PASS_COUNT asserts) ==="
printf '\n=== %s: PASS (%d asserts) ===\n' "$TASK" "$GATE_PASS_COUNT" >> "$GATE_LOG_FILE"
exit 0
