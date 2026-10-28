<?php
/**
 * Checks that every advisory `composer audit` still reports is documented in
 * docs/upgrade/accepted-risks.md.
 *
 * Usage: php accepted-risks.php <audit.json> <accepted-risks.md>
 *
 * Format of the accepted risks file:
 *
 *   ## vendor/package
 *   - CVE-2024-1234: why we accept this risk
 *
 * Exits with 0 when every advisory is accepted, 1 otherwise.
 */

$auditFile = $argv[1] ?? '';
$acceptedFile = $argv[2] ?? '';

if (!is_file($auditFile)) {
    fwrite(STDERR, "accepted-risks: audit file is missing\n");
    exit(1);
}

$accepted = [];
if (is_file($acceptedFile)) {
    $package = '';
    foreach (file($acceptedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^##\s+(\S+)/', $line, $matches)) {
            $package = $matches[1];
            continue;
        }
        if (preg_match('/^-\s+(\S+)/', $line, $matches) && $package !== '') {
            $accepted[$package][$matches[1]] = $line;
        }
    }
}

$audit = json_decode(file_get_contents($auditFile), true);
if (!is_array($audit)) {
    fwrite(STDERR, "accepted-risks: audit file is not valid JSON\n");
    exit(1);
}

$problems = [];
$total = 0;

foreach (($audit['advisories'] ?? []) as $package => $advisories) {
    foreach ($advisories as $advisory) {
        $total++;
        $id = $advisory['advisoryId'] ?? ($advisory['cve'] ?? 'unknown');
        if (isset($accepted[$package][$id])) {
            continue;
        }
        $problems[] = sprintf(
            '%s / %s (%s) is not listed in %s',
            $package,
            $id,
            $advisory['severity'] ?? 'unknown',
            basename($acceptedFile)
        );
    }
}

if ($problems) {
    printf("accepted-risks: %d of %d advisories are not accepted\n", count($problems), $total);
    foreach ($problems as $problem) {
        printf("  - %s\n", $problem);
    }
    exit(1);
}

printf("accepted-risks: all %d advisories are accepted\n", $total);
exit(0);
