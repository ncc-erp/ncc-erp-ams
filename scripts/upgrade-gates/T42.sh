#!/usr/bin/env bash
# Gate for T42 - infrastructure and deploy
#
# The production image is built on the host, the test container deliberately has
# no access to the docker socket.  The build therefore writes its evidence to
# docs/upgrade/gate-results/T42-build.log, which this gate checks.

gate_section "T42 - infrastructure and deploy"

build_log="docs/upgrade/gate-results/T42-build.log"

if [ -f "$build_log" ] && grep -q "BUILD OK" "$build_log" && grep -qE "HTTP (200|302)" "$build_log"; then
    gate_record PASS "the production image builds and the container answers with HTTP 200/302 on /"
    grep -E "BUILD OK|HTTP [0-9]+" "$build_log" | head -5
else
    gate_record FAIL "the production image builds and the container answers with HTTP 200/302 on /" \
        "missing evidence in $build_log - run scripts/upgrade-gates/build-production-image.sh on the host"
fi

if command -v docker >/dev/null 2>&1; then
    if docker compose -f docker-compose.yml config > /tmp/gate-t42-config.log 2>&1; then
        gate_record PASS "docker-compose.yml is a valid compose file"
    else
        tail -20 /tmp/gate-t42-config.log
        gate_record FAIL "docker-compose.yml is a valid compose file" "see output above"
    fi
else
    gate_record FAIL "docker-compose.yml is a valid compose file" "the docker client is not available"
fi

assert_no_grep "continue-on-error" ".github/workflows/ci.yml"
assert_no_grep "php7|php:7|7\.4|php8\.0|PHP 8\.1" Dockerfile Dockerfile.alpine Dockerfile.fpm-alpine Dockerfile.php8.1 docker-compose.yml install.sh snipeit.sh README.md SETUP.md .github/workflows ansible
