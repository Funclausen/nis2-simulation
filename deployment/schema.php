<?php
declare(strict_types=1);

require $argv[1] . '/private/nis2/api/_db.php';
$pdo = nis2_db();
$tables = $pdo->query("SHOW TABLES LIKE 'nis2\\_%'")->fetchAll(PDO::FETCH_COLUMN);
if ($tables !== []) {
    throw new RuntimeException('Existing NIS2 tables found. Inspect the schema before running setup.');
}
foreach (explode(';', file_get_contents(__DIR__ . '/../database/schema.sql')) as $statement) {
    if (trim($statement) !== '') {
        $pdo->exec($statement);
    }
}
echo "Created the three NIS2 tables.\n";
