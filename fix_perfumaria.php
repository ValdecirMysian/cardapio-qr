<?php
require_once 'functions.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Atualiza a categoria Perfumaria com descrição e ícone
    $sql = "UPDATE categorias 
            SET descricao = 'Perfumes, maquiagens e cosméticos', 
                icone = 'spray-can' 
            WHERE nome = 'Perfumaria'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Sucesso: Categoria 'Perfumaria' atualizada com descrição e ícone.\n";
    } else {
        echo "Aviso: Nenhuma linha alterada. Verifique se a categoria 'Perfumaria' existe.\n";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
