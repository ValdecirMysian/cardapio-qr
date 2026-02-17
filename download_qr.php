<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar se foi fornecido um token
if (!isset($_GET['token'])) {
    die("Token não fornecido");
}

$token = $_GET['token'];

// Verificar se o token pertence a uma farmácia do usuário logado
$stmt = $pdo->prepare("
    SELECT f.* 
    FROM farmacias f 
    WHERE f.qr_code_token = ? AND f.usuario_id = ?
");
$stmt->execute([$token, $_SESSION['user_id']]);
$farmacia = $stmt->fetch();

if (!$farmacia) {
    die("Você não tem permissão para baixar este QR Code");
}

// URL do cardápio
$qr_code_url = gerarUrlQrCode($token);

// Definir o nome do arquivo
$filename = "qrcode_" . preg_replace('/[^a-zA-Z0-9]/', '_', $farmacia['nome']) . ".png";

// Armazenar o QR Code temporariamente em um arquivo
$tempFile = 'temp_qrcode.png';

try {
    // Gerar o QR Code como uma imagem no servidor usando a API QR Server
    $imageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=" . urlencode($qr_code_url);
    $qrData = file_get_contents($imageUrl);
    
    if ($qrData === false) {
        throw new Exception("Não foi possível acessar o serviço de QR Code.");
    }
    
    // Salvar a imagem temporariamente
    if (file_put_contents($tempFile, $qrData) === false) {
        throw new Exception("Não foi possível salvar o QR Code temporariamente.");
    }
    
    // Definir os cabeçalhos para download
    header('Content-Description: File Transfer');
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tempFile));
    
    // Limpar o buffer de saída
    ob_clean();
    flush();
    
    // Enviar o arquivo
    readfile($tempFile);
    
    // Remover o arquivo temporário
    unlink($tempFile);
    exit;
    
} catch (Exception $e) {
    // Se algo deu errado, redirecionar para a página inicial com mensagem de erro
    header("Location: index.php?erro=download_qr");
    exit;
}
?>