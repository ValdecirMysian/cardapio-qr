<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Obter farmácia do usuário logado
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit();
}

// Obter dados do formulário
$nome = $_POST['nome_farmacia'];
$endereco = $_POST['endereco'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';

// Validar dados
if (empty($nome)) {
    header("Location: index.php?msg=erro_nome_vazio");
    exit();
}

// Atualizar dados da farmácia
$stmt = $pdo->prepare("UPDATE farmacias SET nome = ?, endereco = ?, telefone = ?, whatsapp = ? WHERE id = ? AND usuario_id = ?");
$stmt->execute([$nome, $endereco, $telefone, $whatsapp, $farmacia['id'], $_SESSION['user_id']]);

// Redirecionar para a página principal
header("Location: index.php?msg=farmacia_atualizada");
exit();
?>