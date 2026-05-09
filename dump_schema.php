<?php
require_once 'admin/config/database.php';
$tables = db()->fetchAll("SHOW TABLES");
foreach($tables as $t) {
    $table = current($t);
    echo "--- $table ---\n";
    $cols = db()->fetchAll("DESCRIBE $table");
    foreach($cols as $c) {
        echo "  {$c['Field']} ({$c['Type']})\n";
    }
}
