<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $tables = db()->fetchAll("SHOW TABLES");
    echo "Tables in " . DB_NAME . ":\n";
    foreach ($tables as $row) {
        echo "- " . array_values($row)[0] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
