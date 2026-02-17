<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

header('Content-Type: application/json');

// Verificar login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Obter farmácia do usuário
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);
if (!$farmacia) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Farmácia não encontrada']);
    exit();
}

// Ler dados da requisição (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'atualizar_preco') {
    $id = $input['id'] ?? null;
    $preco = $input['preco'] ?? null;

    if (!$id || !is_numeric($preco)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    // Verificar se o produto pertence à farmácia
    $stmt = $pdo->prepare("UPDATE produtos SET preco = ? WHERE id = ? AND farmacia_id = ?");
    $result = $stmt->execute([$preco, $id, $farmacia['id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} elseif ($action == 'toggle_status') {
    $id = $input['id'] ?? null;
    $ativo = $input['ativo'] ?? null; // espera true/false ou 1/0

    if (!$id || $ativo === null) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    // Converter para booleano/inteiro
    $estoque_disponivel = $ativo ? 1 : 0;

    // Verificar se o produto pertence à farmácia
    $stmt = $pdo->prepare("UPDATE produtos SET estoque_disponivel = ? WHERE id = ? AND farmacia_id = ?");
    $result = $stmt->execute([$estoque_disponivel, $id, $farmacia['id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} elseif ($action == 'toggle_promocao') {
    $id = $input['id'] ?? null;
    $ativo = $input['ativo'] ?? null;

    if (!$id || $ativo === null) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    $promocao = $ativo ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE produtos SET promocao = ? WHERE id = ? AND farmacia_id = ?");
    $result = $stmt->execute([$promocao, $id, $farmacia['id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} elseif ($action == 'toggle_destaque') {
    $id = $input['id'] ?? null;
    $ativo = $input['ativo'] ?? null;

    if (!$id || $ativo === null) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    $destaque = $ativo ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE produtos SET destaque = ? WHERE id = ? AND farmacia_id = ?");
    $result = $stmt->execute([$destaque, $id, $farmacia['id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
