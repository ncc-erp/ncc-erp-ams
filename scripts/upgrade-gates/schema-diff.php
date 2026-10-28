<?php
/**
 * Compares the schema of the test database with the schema dump of the
 * production database (tests/fixtures/schema-prod.sql).
 *
 * Usage: php schema-diff.php <fixture.sql> <accepted-diff-file.md>
 *
 * The dump of the test database is created with mysqldump --no-data
 * --skip-comments.  Both dumps are normalised (conditional comments,
 * AUTO_INCREMENT counters and back ticks are removed, tables are sorted) and
 * then compared table by table.
 *
 * Differences that are listed in the accepted-diff-file are reported as
 * accepted, every other difference makes the check fail.
 */

$fixtureFile = $argv[1] ?? '';
$acceptedFile = $argv[2] ?? '';

if (!is_file($fixtureFile)) {
    fwrite(STDERR, "schema-diff: fixture $fixtureFile is missing\n");
    exit(1);
}

$accepted = [];
if (is_file($acceptedFile)) {
    foreach (file($acceptedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $accepted[] = $line;
    }
}

/**
 * Parses a mysqldump --no-data output into a comparable structure.
 */
function parse_dump(string $sql): array
{
    $lines = preg_split('/\R/', $sql);
    $tables = [];
    $current = null;

    foreach ($lines as $line) {
        $line = trim($line);

        // drop conditional comments and plain comments
        if ($line === '' || str_starts_with($line, '/*') || str_starts_with($line, '--')
            || str_starts_with($line, '#') || str_starts_with($line, 'DROP ')
            || str_starts_with($line, 'SET ')) {
            continue;
        }

        if (preg_match('/^CREATE TABLE `([^`]+)`/i', $line, $matches)) {
            $current = $matches[1];
            $tables[$current] = ['columns' => [], 'indexes' => []];
            continue;
        }

        if ($current === null) {
            continue;
        }

        if ($line === ')' || $line === ');') {
            $current = null;
            continue;
        }

        if (preg_match('/^PRIMARY KEY \((.+)\)$/i', $line, $matches)) {
            $tables[$current]['indexes']['PRIMARY'] = ['unique' => true, 'columns' => split_index_columns($matches[1])];
            continue;
        }

        if (preg_match('/^(UNIQUE KEY|KEY|INDEX) `([^`]+)` \((.+)\)$/i', $line, $matches)) {
            $tables[$current]['indexes'][$matches[2]] = [
                'unique' => strtoupper($matches[1]) === 'UNIQUE KEY',
                'columns' => split_index_columns($matches[3]),
            ];
            continue;
        }

        if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $matches)) {
            $definition = $matches[2];
            $nullable = true;
            $default = null;
            $extra = '';

            if (preg_match('/\bNOT NULL\b/i', $definition)) {
                $nullable = false;
            }
            if (preg_match("/\bDEFAULT\s+('(?:[^']|'')*'|[^\s,]+)/i", $definition, $matches2)) {
                $default = $matches2[1];
            }
            if (preg_match('/\b(AUTO_INCREMENT|UNIQUE|PRIMARY KEY)\b/i', $definition, $matches2)) {
                $extra = strtoupper($matches2[1]);
            }

            // the type is everything up to the first column attribute
            $type = preg_split('/\s+(NOT NULL|NULL|DEFAULT|AUTO_INCREMENT|UNIQUE|PRIMARY KEY|COLLATE|CHARACTER SET|COMMENT|GENERATED|VIRTUAL|STORED|ON UPDATE|CURRENT_TIMESTAMP)\b/i', $definition)[0];
            $type = trim($type);
            $type = preg_replace('/\s+/', ' ', $type);

            $tables[$current]['columns'][$matches[1]] = [
                'type' => normalise_type($type),
                'nullable' => $nullable,
                'default' => $default === null ? null : trim($default, "'"),
                'extra' => $extra,
            ];
            continue;
        }
    }

    ksort($tables);

    return $tables;
}

function split_index_columns(string $raw): array
{
    $columns = [];
    foreach (explode(',', $raw) as $column) {
        $column = trim($column);
        $column = trim($column, '`');
        $column = preg_replace('/\(\d+\)$/', '', $column);
        $columns[] = $column;
    }
    sort($columns);

    return $columns;
}

function normalise_type(string $type): string
{
    $type = strtolower(trim($type));
    $type = preg_replace('/\bunsigned\b/', 'unsigned', $type);
    $type = preg_replace('/\s+/', ' ', $type);
    // integer display widths are not significant
    $type = preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $type);
    // the charset of a string column is not part of the schema comparison
    $type = preg_replace('/\s+(character set|collate)\s+\S+/i', '', $type);

    return trim($type);
}

/**
 * Creates the dump of the test database.
 */
function dump_current_schema(): string
{
    $host = getenv('DB_HOST') ?: 'mysql';
    $database = getenv('DB_DATABASE') ?: 'ams_test';
    $user = getenv('DB_USERNAME') ?: 'ams';
    $password = getenv('DB_PASSWORD') ?: 'ams';
    $port = getenv('DB_PORT') ?: '3306';

    $command = sprintf(
        'mysqldump --no-data --skip-comments --skip-add-locks --skip-disable-keys --compact --host=%s --port=%s --user=%s --password=%s %s 2>/dev/null',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($user),
        escapeshellarg($password),
        escapeshellarg($database)
    );

    exec($command, $output, $status);

    if ($status !== 0) {
        fwrite(STDERR, "schema-diff: mysqldump failed\n");
        exit(1);
    }

    return implode("\n", $output);
}

$fixture = parse_dump(file_get_contents($fixtureFile));
$current = parse_dump(dump_current_schema());

$differences = [];

foreach ($fixture as $table => $definition) {
    if (!isset($current[$table])) {
        $differences[] = "table $table is missing in the test database";
        continue;
    }
    foreach ($definition['columns'] as $column => $attributes) {
        if (!isset($current[$table]['columns'][$column])) {
            $differences[] = "table $table is missing column $column";
            continue;
        }
        $other = $current[$table]['columns'][$column];
        if ($attributes['type'] !== $other['type']) {
            $differences[] = "table $table column $column has type {$other['type']} instead of {$attributes['type']}";
        }
        if ($attributes['nullable'] !== $other['nullable']) {
            $differences[] = "table $table column $column nullable is "
                . var_export($other['nullable'], true) . ' instead of ' . var_export($attributes['nullable'], true);
        }
        if ($attributes['default'] !== $other['default']) {
            $differences[] = "table $table column $column default is "
                . var_export($other['default'], true) . ' instead of ' . var_export($attributes['default'], true);
        }
    }
    foreach ($current[$table]['columns'] as $column => $attributes) {
        if (!isset($definition['columns'][$column])) {
            $differences[] = "table $table has the extra column $column";
        }
    }
    foreach ($definition['indexes'] as $index => $attributes) {
        if (!isset($current[$table]['indexes'][$index])) {
            $differences[] = "table $table is missing index $index";
            continue;
        }
        if ($attributes['columns'] !== $current[$table]['indexes'][$index]['columns']) {
            $differences[] = "table $table index $index covers different columns";
        }
    }
    foreach ($current[$table]['indexes'] as $index => $attributes) {
        if (!isset($definition['indexes'][$index])) {
            $differences[] = "table $table has the extra index $index";
        }
    }
}

foreach ($current as $table => $definition) {
    if (!isset($fixture[$table])) {
        $differences[] = "table $table is not part of the production schema";
    }
}

if (!$differences) {
    printf("schema-diff: the schema of %d tables matches the production dump\n", count($current));
    exit(0);
}

$unexpected = [];
foreach ($differences as $difference) {
    $accepted = false;
    foreach ($accepted as $entry) {
        if ($entry === $difference || str_contains($difference, $entry)) {
            $accepted = true;
            break;
        }
    }
    if ($accepted) {
        printf("  accepted difference  %s\n", $difference);
        continue;
    }
    $unexpected[] = $difference;
}

if ($unexpected) {
    printf("schema-diff: %d unexpected difference(s)\n", count($unexpected));
    foreach ($unexpected as $difference) {
        printf("  - %s\n", $difference);
    }
    exit(1);
}

printf("schema-diff: %d difference(s), all of them accepted\n", count($differences));
exit(0);
