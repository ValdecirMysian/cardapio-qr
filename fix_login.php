<?php
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}

try {
    // Criar tabela usuarios
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100),
        senha VARCHAR(255) NOT NULL,
        empresa VARCHAR(50),
        is_admin TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        remember_token VARCHAR(255),
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Tabela 'usuarios' verificada/criada.<br>";

    // Verificar se existe usuário admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE usuario = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $senha = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, email, senha, empresa, is_admin, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@sistema.com', $senha, 'cardapio-qr', 1, 1]);
        echo "Usuário 'admin' (senha: admin) criado com sucesso.<br>";
    } else {
        echo "Usuário 'admin' já existe.<br>";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>