<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

verificarLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $nome = trim($_POST['nome']);
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

        if (empty($nome)) {
            echo json_encode(['success' => false, 'message' => 'Nome da categoria é obrigatório']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, parent_id) VALUES (?, ?)");
            $stmt->execute([$nome, $parent_id]);
            echo json_encode(['success' => true, 'message' => 'Categoria adicionada com sucesso']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar: ' . $e->getMessage()]);
        }
    } elseif ($acao === 'listar') {
        try {
            // Buscar todas as categorias
            $stmt = $pdo->query("SELECT c.*, p.nome as parent_nome 
                               FROM categorias c 
                               LEFT JOIN categorias p ON c.parent_id = p.id 
                               ORDER BY c.nome");
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $categorias]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()]);
        }
    }
}
?>