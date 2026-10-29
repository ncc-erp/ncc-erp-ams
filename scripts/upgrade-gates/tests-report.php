<?php
/**
 * Checks a JUnit report of PHPUnit against a list of test classes.
 *
 * Usage: php tests-report.php <junit.xml> <Class> [<Class>...]
 *
 * Every class has to be part of the report and none of its tests may have
 * failed. The classes that are missing or that failed are printed, the exit
 * code is 0 when every class is green.
 */

$junitFile = $argv[1] ?? '';
$classes = array_slice($argv, 2);

if (!is_file($junitFile)) {
    fwrite(STDERR, "tests-report: junit report is missing\n");
    exit(1);
}

$xml = @simplexml_load_file($junitFile);
if ($xml === false) {
    fwrite(STDERR, "tests-report: junit report is not valid XML\n");
    exit(1);
}

$tests = 0;
$problems = [];
$classesSeen = [];

foreach ($xml->xpath('//testcase') ?: [] as $testcase) {
    $tests++;
    $class = (string) $testcase['class'];

    foreach ($classes as $wanted) {
        if ($class === $wanted || str_ends_with($class, '\\'.$wanted)) {
            $classesSeen[$wanted] = ($classesSeen[$wanted] ?? 0) + 1;

            if ($testcase->failure ?? $testcase->error ?? null) {
                $problems[] = $class.'::'.$testcase['name'];
            }
        }
    }
}

$missing = [];
foreach ($classes as $class) {
    if (!isset($classesSeen[$class])) {
        $missing[] = $class;
    }
}

printf("phpunit: %d test(s) executed for %d class(es)\n", $tests, count($classes));

foreach ($missing as $class) {
    printf("  MISSING CLASS  %s\n", $class);
}

foreach ($problems as $problem) {
    printf("  FAILED TEST  %s\n", $problem);
}

exit(count($missing) === 0 && count($problems) === 0 ? 0 : 1);
