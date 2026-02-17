<?php
require_once '../config/database.php';

try {
    echo "Iniciando atualização do banco de dados...<br>";

    // Verificar se as colunas já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM produtos LIKE 'ean'");
    $eanExists = $stmt->fetch();

    if (!$eanExists) {
        $sql = "ALTER TABLE produtos ADD COLUMN ean VARCHAR(20) DEFAULT NULL AFTER nome";
        $pdo->exec($sql);
        echo "Coluna 'ean' adicionada com sucesso!<br>";
    } else {
        echo "Coluna 'ean' já existe.<br>";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM produtos LIKE 'sku_externo'");
    $skuExists = $stmt->fetch();

    if (!$skuExists) {
        $sql = "ALTER TABLE produtos ADD COLUMN sku_externo VARCHAR(50) DEFAULT NULL AFTER ean";
        $pdo->exec($sql);
        echo "Coluna 'sku_externo' adicionada com sucesso!<br>";
    } else {
        echo "Coluna 'sku_externo' já existe.<br>";
    }

    // Adicionar colunas da Evolution API e Feed na tabela farmacias
    $evolutionCols = [
        'usar_evolution_api' => "TINYINT(1) DEFAULT 0",
        'evolution_instance_name' => "VARCHAR(100) DEFAULT NULL",
        'evolution_api_key' => "VARCHAR(255) DEFAULT NULL",
        'evolution_api_url' => "VARCHAR(255) DEFAULT 'https://evolution.probotfarmapro.online'",
        'feed_apenas_promocao' => "TINYINT(1) DEFAULT 0"
    ];

    foreach ($evolutionCols as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM farmacias LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE farmacias ADD COLUMN $col $def");
            echo "Coluna '$col' adicionada.<br>";
        }
    }

    // Criar tabela api_logs se não existir (sem FK para evitar erros de compatibilidade)
    $sql = "CREATE TABLE IF NOT EXISTS api_logs (
        id int(11) NOT NULL AUTO_INCREMENT,
        farmacia_id int(11) NOT NULL,
        endpoint varchar(50) NOT NULL,
        method varchar(10) NOT NULL,
        request_data text DEFAULT NULL,
        response_data text DEFAULT NULL,
        status_code int(11) NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        KEY farmacia_id (farmacia_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Tabela 'api_logs' verificada/criada com sucesso!<br>";

    echo "Atualização concluída com sucesso!";

} catch (PDOException $e) {
    die("Erro ao atualizar banco de dados: " . $e->getMessage());
}
?>