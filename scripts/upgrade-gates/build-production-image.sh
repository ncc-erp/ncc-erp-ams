#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# build-production-image.sh - builds the production image on the host and
# records the result for the T42 gate.
#
# The test container has no access to the docker socket on purpose, so the
# production image is built here and the outcome is written to
# docs/upgrade/gate-results/T42-build.log.
#
#   bash scripts/upgrade-gates/build-production-image.sh
# ---------------------------------------------------------------------------
set -uo pipefail

GATE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LOG="$GATE_ROOT/docs/upgrade/gate-results/T42-build.log"
IMAGE="ncc-erp-ams-gate-check"
PORT="${GATE_CHECK_PORT:-8099}"

mkdir -p "$(dirname "$LOG")"
: > "$LOG"

cd "$GATE_ROOT"

log() {
    printf '%s\n' "$1" | tee -a "$LOG"
}

log "production image build: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"

if docker compose -f docker-compose.yml config > /tmp/t42-compose-config.log 2>&1; then
    log "COMPOSE CONFIG OK"
else
    log "COMPOSE CONFIG FAILED"
    tail -20 /tmp/t42-compose-config.log >> "$LOG"
    exit 1
fi

if docker build -f Dockerfile.alpine -t "$IMAGE" . > /tmp/t42-build.log 2>&1; then
    log "BUILD OK"
else
    log "BUILD FAILED"
    tail -30 /tmp/t42-build.log >> "$LOG"
    exit 1
fi

docker rm -f "$IMAGE" >/dev/null 2>&1

if docker run --rm -d --name "$IMAGE" -p "$PORT:80" \
    --env-file "$GATE_ROOT/.env.docker" \
    -e APACHE_LOG_DIR=/var/log/apache2 \
    --entrypoint /bin/sh "$IMAGE" \
    -c 'php artisan config:cache >/tmp/t42-config.log 2>&1 && exec httpd -DNO_DETACH' > /dev/null 2>&1; then
    sleep 30
    code="$(curl --max-time 15 -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PORT/health" || true)"
    docker rm -f "$IMAGE" > /dev/null 2>&1
    log "HTTP $code /health"
    if [ "$code" = "200" ] || [ "$code" = "302" ]; then
        log "RESULT OK"
        exit 0
    fi
    log "RESULT FAILED"
    exit 1
fi

log "RESULT FAILED - the container did not start"
exit 1
