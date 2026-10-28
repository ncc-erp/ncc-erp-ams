#!/usr/bin/env bash
# Gate for T05 - CI and deploy workflow
gate_section "T05 - CI and deploy workflow"

assert_file ".github/workflows/ci.yml"
assert_grep "upgrade-gates/run.sh" ".github/workflows/ci.yml"
assert_grep "composer install --no-dev" ".github/workflows/deploy-prod.yml"

for f in .github/workflows/ci.yml .github/workflows/deploy-prod.yml; do
    if python3 -c "import sys,yaml;yaml.safe_load(open(sys.argv[1]))" "$f" 2>/dev/null; then
        gate_record PASS "$f is valid YAML"
    else
        gate_record FAIL "$f is valid YAML"
    fi
done
