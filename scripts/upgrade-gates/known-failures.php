<?php
/**
 * Compares the result of a PHPUnit run against the list of tests that are
 * known to fail already (scripts/upgrade-gates/known-failures.txt).
 *
 * Usage: php known-failures.php <junit.xml> <known-failures.txt>
 *
 * Known failure format (one entry per line, blank lines and # comments
 * ignored):
 *
 *   Tests\Unit\SomeTest::testSomething   a single test
 *   Tests\Unit\SomeTest                  every test of that class
 *
 * Exits with 0 when every failing test is a known failure, 1 otherwise.
 * Known failures that pass again are reported so that the list can be pruned.
 */

$junitFile = $argv[1] ?? '';
$knownFile = $argv[2] ?? '';

if (!is_file($junitFile)) {
    fwrite(STDERR, "known-failures: junit report is missing\n");
    exit(1);
}

$known = [];
if (is_file($knownFile)) {
    foreach (file($knownFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $known[] = $line;
    }
}

$xml = @simplexml_load_file($junitFile);
if ($xml === false) {
    fwrite(STDERR, "known-failures: junit report is not valid XML\n");
    exit(1);
}

$tests = 0;
$failures = [];
$seenKnown = [];

$testcases = $xml->xpath('//testcase');
foreach ($testcases ?: [] as $testcase) {
    $tests++;
    $class = (string) $testcase['class'];
    $name = (string) $testcase['name'];
    $id = $class === '' ? $name : $class . '::' . $name;

    $problem = $testcase->failure ?? $testcase->error ?? null;
    if ($problem === null) {
        continue;
    }

    $isKnown = false;
    foreach ($known as $entry) {
        if ($entry === $id || $entry === $class) {
            $isKnown = true;
            $seenKnown[$entry] = true;
            break;
        }
    }

    if (!$isKnown) {
        $failures[] = $id;
    }
}

printf("phpunit: %d test(s) executed, %d unexpected failure(s)\n", $tests, count($failures));

foreach ($failures as $failure) {
    printf("  UNEXPECTED FAILURE  %s\n", $failure);
}

foreach ($known as $entry) {
    if (!isset($seenKnown[$entry])) {
        printf("  known failure no longer fails (remove from the list)  %s\n", $entry);
    }
}

exit(count($failures) === 0 ? 0 : 1);
