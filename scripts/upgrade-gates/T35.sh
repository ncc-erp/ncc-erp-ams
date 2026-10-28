#!/usr/bin/env bash
# Gate for T35 - L11 - migrations using ->change()
gate_section "T35 - Laravel 11 schema"

assert_file "tests/fixtures/schema-prod.sql"

if php "$GATE_DIR/schema-diff.php" "tests/fixtures/schema-prod.sql" "docs/upgrade/schema-diff-accepted.md"; then
    gate_record PASS "the migrated schema matches tests/fixtures/schema-prod.sql"
else
    gate_record FAIL "the migrated schema matches tests/fixtures/schema-prod.sql" "see differences above"
fi
