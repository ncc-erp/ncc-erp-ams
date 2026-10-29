#!/usr/bin/env bash
# Gate for T11 - update the remaining dependencies
gate_section "T11 - remaining dependencies updated"

# aws/aws-sdk-php is not part of this list: the version that fixes its
# advisory requires PHP 8.1, the runtime is raised in T19. The check for it
# lives in the T19 gate.
for pkg in guzzlehttp/guzzle guzzlehttp/psr7 league/commonmark phpseclib/phpseclib \
           nesbot/carbon enshrined/svg-sanitize mtdowling/jmespath.php \
           maatwebsite/excel phpoffice/phpspreadsheet nyholm/psr7 psy/psysh; do
    assert_no_advisory "$pkg"
done

run_test_classes_once "UploadTest" "ImportTest"
