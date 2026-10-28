#!/usr/bin/env bash
# Gate for T11 - update the remaining dependencies
gate_section "T11 - remaining dependencies updated"

for pkg in guzzlehttp/guzzle guzzlehttp/psr7 league/commonmark phpseclib/phpseclib \
           nesbot/carbon enshrined/svg-sanitize aws/aws-sdk-php mtdowling/jmespath.php \
           maatwebsite/excel phpoffice/phpspreadsheet nyholm/psr7 psy/psysh; do
    assert_no_advisory "$pkg"
done

run_tests "UploadTest"
run_tests "ImportTest"
