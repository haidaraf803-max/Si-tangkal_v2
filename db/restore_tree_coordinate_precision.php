<?php
/**
 * Restores the original precision of tree coordinates from the verified SQL
 * snapshot in this directory. The legacy `pohon.koordinat_x/y` FLOAT columns
 * retain only roughly 3 decimal places at Cimahi's longitude, which makes
 * points appear as a staircase when viewed closely.
 *
 * Usage:
 *   php db/restore_tree_coordinate_precision.php          # dry run
 *   php db/restore_tree_coordinate_precision.php --apply  # backup + restore
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This maintenance script may only run from the command line.\n");
}

require_once __DIR__ . '/../includes/config.php';

const SOURCE_DUMP = __DIR__ . '/dump-db_sitangkal-202608131025.sql';
const X_COLUMN_INDEX = 18;
const Y_COLUMN_INDEX = 19;

/**
 * Split the values in one SQL tuple without breaking quoted text such as
 * "III (Tiga)" or names that include commas.
 *
 * @return list<string>
 */
function splitSqlTuple(string $tuple): array
{
    $values = [];
    $buffer = '';
    $quoted = false;
    $length = strlen($tuple);

    for ($i = 1; $i < $length - 1; $i++) {
        $char = $tuple[$i];

        if ($quoted) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length - 1) {
                $buffer .= $tuple[++$i];
                continue;
            }
            if ($char === "'") {
                if ($i + 1 < $length - 1 && $tuple[$i + 1] === "'") {
                    $buffer .= $tuple[++$i];
                    continue;
                }
                $quoted = false;
            }
            continue;
        }

        if ($char === "'") {
            $quoted = true;
            $buffer .= $char;
        } elseif ($char === ',') {
            $values[] = trim($buffer);
            $buffer = '';
        } else {
            $buffer .= $char;
        }
    }

    $values[] = trim($buffer);
    return $values;
}

/**
 * @return array<int, array{x:string, y:string}>
 */
function loadCoordinatesFromDump(string $path): array
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Cannot read source snapshot: {$path}");
    }

    $needle = 'INSERT INTO `pohon` VALUES ';
    $start = strpos($sql, $needle);
    if ($start === false) {
        throw new RuntimeException('The source snapshot does not contain pohon rows.');
    }

    $cursor = $start + strlen($needle);
    $length = strlen($sql);
    $depth = 0;
    $quoted = false;
    $tupleStart = null;
    $coordinates = [];

    for ($i = $cursor; $i < $length; $i++) {
        $char = $sql[$i];

        if ($quoted) {
            if ($char === '\\') {
                $i++;
                continue;
            }
            if ($char === "'") {
                if ($i + 1 < $length && $sql[$i + 1] === "'") {
                    $i++;
                    continue;
                }
                $quoted = false;
            }
            continue;
        }

        if ($char === "'") {
            $quoted = true;
            continue;
        }
        if ($char === '(') {
            if ($depth === 0) {
                $tupleStart = $i;
            }
            $depth++;
            continue;
        }
        if ($char === ')') {
            $depth--;
            if ($depth === 0 && $tupleStart !== null) {
                $values = splitSqlTuple(substr($sql, $tupleStart, $i - $tupleStart + 1));
                if (count($values) > Y_COLUMN_INDEX
                    && ctype_digit($values[0])
                    && is_numeric($values[X_COLUMN_INDEX])
                    && is_numeric($values[Y_COLUMN_INDEX])) {
                    $coordinates[(int) $values[0]] = [
                        'x' => $values[X_COLUMN_INDEX],
                        'y' => $values[Y_COLUMN_INDEX],
                    ];
                }
                $tupleStart = null;
            }
            continue;
        }
        if ($char === ';' && $depth === 0) {
            break;
        }
    }

    if ($coordinates === []) {
        throw new RuntimeException('No valid tree coordinates were parsed from the snapshot.');
    }

    return $coordinates;
}

/** @param list<array{id:int, koordinat_x:string|null, koordinat_y:string|null}> $rows */
function writeCoordinateBackup(array $rows): string
{
    $backupDirectory = __DIR__ . '/backups';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0775, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException("Cannot create backup directory: {$backupDirectory}");
    }

    $path = $backupDirectory . '/pohon_coordinates_before_precision_restore_' . date('Ymd_His') . '.csv';
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException("Cannot create coordinate backup: {$path}");
    }

    fputcsv($handle, ['id', 'koordinat_x_before', 'koordinat_y_before']);
    foreach ($rows as $row) {
        fputcsv($handle, [$row['id'], $row['koordinat_x'], $row['koordinat_y']]);
    }
    fclose($handle);

    return $path;
}

$apply = in_array('--apply', $argv, true);
$coordinates = loadCoordinatesFromDump(SOURCE_DUMP);
$pdo = getPDO();
$rows = $pdo->query('SELECT id, koordinat_x, koordinat_y FROM pohon ORDER BY id ASC')->fetchAll();
$targetIds = array_map(static fn(array $row): int => (int) $row['id'], $rows);
$matchingIds = array_values(array_filter($targetIds, static fn(int $id): bool => isset($coordinates[$id])));
$missingIds = array_values(array_diff($targetIds, $matchingIds));

$columnTypes = $pdo->query(
    "SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS\n"
    . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pohon'\n"
    . "AND COLUMN_NAME IN ('koordinat_x', 'koordinat_y')\n"
    . 'ORDER BY COLUMN_NAME'
)->fetchAll();

echo 'Source coordinate rows: ' . count($coordinates) . PHP_EOL;
echo 'Current tree rows: ' . count($rows) . PHP_EOL;
echo 'Matching IDs: ' . count($matchingIds) . PHP_EOL;
echo 'Missing source coordinates: ' . count($missingIds) . PHP_EOL;
echo 'Current column types: ' . implode(', ', array_map(
    static fn(array $column): string => $column['COLUMN_NAME'] . '=' . $column['COLUMN_TYPE'],
    $columnTypes
)) . PHP_EOL;

if (!$apply) {
    echo "Dry run only. Re-run with --apply to create a CSV backup, upgrade the coordinate columns, and restore matching coordinates.\n";
    exit(0);
}

if ($matchingIds === []) {
    throw new RuntimeException('No coordinate IDs match the verified snapshot. Nothing was changed.');
}

$backupPath = writeCoordinateBackup($rows);
echo "Backup written: {$backupPath}\n";

// MySQL FLOAT cannot represent the required coordinate precision at longitude
// ~107.5. Use fixed decimals before writing the original values back.
$pdo->exec(
    'ALTER TABLE pohon '
    . 'MODIFY koordinat_x DECIMAL(11,8) NOT NULL DEFAULT 0.00000000, '
    . 'MODIFY koordinat_y DECIMAL(10,8) NULL DEFAULT NULL'
);

$update = $pdo->prepare(
    'UPDATE pohon SET koordinat_x = :x, koordinat_y = :y WHERE id = :id'
);

$pdo->beginTransaction();
try {
    foreach ($matchingIds as $id) {
        $update->execute([
            ':x' => $coordinates[$id]['x'],
            ':y' => $coordinates[$id]['y'],
            ':id' => $id,
        ]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

echo 'Restored coordinate rows: ' . count($matchingIds) . PHP_EOL;
if ($missingIds !== []) {
    echo 'Rows retained without source coordinates: ' . implode(', ', $missingIds) . PHP_EOL;
}
