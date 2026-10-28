<?php
/**
 * Checks a `composer audit --format=json` payload against an allowlist.
 *
 * Usage: php advisory-subset.php <audit.json> <allowlist.txt>
 *
 * Allowlist format (one rule per line, blank lines and # comments ignored):
 *
 *   laravel/framework        every severity of this package is allowed
 *   laravel/framework:low    only advisories of severity "low" or lower
 *   symfony/*:low            same, but the package name may be a glob
 *
 * Exits with 0 when every advisory is covered by the allowlist, 1 otherwise.
 */

$auditFile = $argv[1] ?? '';
$allowFile = $argv[2] ?? '';

if (!is_file($auditFile) || !is_file($allowFile)) {
    fwrite(STDERR, "advisory-subset: audit or allowlist file is missing\n");
    exit(1);
}

$severityRank = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

$audit = json_decode(file_get_contents($auditFile), true);
if (!is_array($audit)) {
    fwrite(STDERR, "advisory-subset: audit file is not valid JSON\n");
    exit(1);
}

$rules = [];
foreach (file($allowFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $parts = explode(':', $line);
    $package = trim($parts[0]);
    $maxSeverity = isset($parts[1]) ? strtolower(trim($parts[1])) : 'any';
    $rules[] = ['package' => $package, 'max' => $maxSeverity];
}

$problems = [];
$total = 0;

foreach (($audit['advisories'] ?? []) as $package => $advisories) {
    foreach ($advisories as $advisory) {
        $total++;
        $severity = strtolower($advisory['severity'] ?? 'unknown');
        $advisoryId = $advisory['advisoryId'] ?? ($advisory['cve'] ?? 'unknown');
        $allowed = false;

        foreach ($rules as $rule) {
            if (!fnmatch($rule['package'], $package)) {
                continue;
            }
            if ($rule['max'] === 'any') {
                $allowed = true;
                break;
            }
            $limit = $severityRank[$rule['max']] ?? 4;
            $current = $severityRank[$severity] ?? 4;
            if ($current <= $limit) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $problems[] = sprintf(
                '%s (%s, %s) is not covered by the allowlist',
                $package,
                $advisoryId,
                $severity
            );
        }
    }
}

if ($problems) {
    printf("advisory-subset: %d of %d advisories are not allowed\n", count($problems), $total);
    foreach ($problems as $problem) {
        printf("  - %s\n", $problem);
    }
    exit(1);
}

printf("advisory-subset: all %d advisories are allowed\n", $total);
exit(0);
