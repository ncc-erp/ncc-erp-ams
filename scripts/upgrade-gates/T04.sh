#!/usr/bin/env bash
# Gate for T04 - regression smoke tests
gate_section "T04 - regression smoke test suite"

for class in SessionLoginTest PassportApiTest AssetCrudTest UploadTest ValidationTest \
              MailTest ImportTest CorsTest LivewireLoginTest PasswordRuleTest; do
    assert_file "tests/Feature/Upgrade/$class.php"
    run_tests "$class"
done

require_approval T04
