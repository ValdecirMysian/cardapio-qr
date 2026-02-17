<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Obter farmácia do usuário logado
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);

// Verificar se foi fornecido um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?msg=erro_id_invalido");
    exit();
}

$produto_id = $_GET['id'];

// Verificar se o produto pertence à farmácia do usuário
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND farmacia_id = ?");
$stmt->execute([$produto_id, $farmacia['id']]);
$produto = $stmt->fetch();

if (!$produto) {
    header("Location: index.php?msg=erro_produto_nao_encontrado");
    exit();
}

// Excluir produto
$stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
$stmt->execute([$produto_id]);

// Excluir imagem, se existir
if (!empty($produto['imagem']) && file_exists($produto['imagem'])) {
    excluirImagem($produto['imagem']);
}

// Redirecionar para a página principal
header("Location: index.php?msg=produto_excluido");
exit();
?>