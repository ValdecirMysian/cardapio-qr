<?php
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se foi fornecido um ID de produto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do produto inválido']);
    exit;
}

$produto_id = $_GET['id'];

try {
    // Buscar os tamanhos do produto
    $tamanhos = obterTamanhosProduto($pdo, $produto_id);
    
    // Retornar como JSON
    header('Content-Type: application/json');
    echo json_encode($tamanhos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>