#!/usr/bin/env bash
# Gate for T04 - regression smoke tests
gate_section "T04 - regression smoke test suite"

TEST_CLASSES=""

for class in SessionLoginTest PassportApiTest AssetCrudTest UploadTest ValidationTest \
              MailTest ImportTest CorsTest LivewireLoginTest PasswordRuleTest; do
    assert_file "tests/Feature/Upgrade/$class.php"
    TEST_CLASSES="$TEST_CLASSES $class"
done

# all the classes run in a single phpunit process, that is a lot faster than
# one process per class
# shellcheck disable=SC2086
run_test_classes_once $TEST_CLASSES

require_approval T04
