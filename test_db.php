<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=spkk_hipertensi;charset=utf8mb4', 'root', '');
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in spkk_hipertensi: " . implode(', ', $tables) . "\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo " - Table $t has $count rows\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 