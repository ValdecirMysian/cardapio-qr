<?php
/**
 * MANIFEST DINÂMICO PARA PWA
 * Gera o manifest.json com o token da farmácia
 */

header('Content-Type: application/manifest+json');
header('Cache-Control: public, max-age=86400');

if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}

$token = $_GET['token'] ?? $_GET['t'] ?? '';

// Valores padrão
$nome = 'Cardápio Digital';
$nomeAbreviado = 'Cardápio';
$descricao = 'Faça seu pedido pelo cardápio digital';
$corPrimaria = '#0d6efd';
$corFundo = '#ffffff';

// Se tem token, busca dados da farmácia
if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, cor_primaria, logo FROM farmacias WHERE qr_code_token = ?");
        $stmt->execute([$token]);
        $farmacia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($farmacia) {
            $nome = $farmacia['nome'] . ' - Cardápio';
            $palavras = explode(' ', $farmacia['nome']);
            $nomeAbreviado = $palavras[0];
            if (strlen($nomeAbreviado) > 12) {
                $nomeAbreviado = substr($nomeAbreviado, 0, 12);
            }
            $descricao = 'Cardápio digital - ' . $farmacia['nome'];
            $corPrimaria = !empty($farmacia['cor_primaria']) ? $farmacia['cor_primaria'] : '#0d6efd';
            
            // Verificar se tem ícones personalizados
            $farmacia_id = $farmacia['id'];
            $icon_dir_custom = 'icons/farmacia_' . $farmacia_id;
            $icon_test = $icon_dir_custom . '/icon-192x192.png';
            
            if (is_dir($icon_dir_custom) && file_exists($icon_test)) {
                // Usa ícones personalizados da farmácia
                $iconPath = $icon_dir_custom;
            } else {
                // Usa ícones padrão Mediz
                $iconPath = 'icons';
            }
        }
    } catch (Exception $e) {
        // Usa valores padrão
    }
}

// Define caminho dos ícones (padrão se não foi definido)
if (!isset($iconPath)) {
    $iconPath = 'icons';
}

$startUrl = $token ? "./cardapio.php?token={$token}" : "./cardapio.php";

$manifest = [
    'name' => $nome,
    'short_name' => $nomeAbreviado,
    'description' => $descricao,
    'start_url' => $startUrl,
    'id' => $token ? "cardapio-{$token}" : 'cardapio-digital',
    'display' => 'standalone',
    'background_color' => $corFundo,
    'theme_color' => $corPrimaria,
    'orientation' => 'portrait-primary',
    'scope' => './',
    'lang' => 'pt-BR',
    'icons' => [
        ['src' => $iconPath . '/icon-72x72.png', 'sizes' => '72x72', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-96x96.png', 'sizes' => '96x96', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-128x128.png', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-144x144.png', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-152x152.png', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-384x384.png', 'sizes' => '384x384', 'type' => 'image/png', 'purpose' => 'maskable any'],
        ['src' => $iconPath . '/icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable any']
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);