<?php
$possible_paths = [
    '../config/database.php',
    'config/database.php',
    '../includes/database.php',
    'includes/database.php',
    '../db.php',
    'db.php',
    '../database.php',
    'database.php',
    '../conexao.php',
    'conexao.php'
];

echo "Procurando database.php...\n";
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "ENCONTRADO: $path\n";
    } else {
        echo "Nao encontrado: $path\n";
    }
}
?>
