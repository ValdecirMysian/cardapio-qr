<?php
// Proxy de Imagens Inteligente
// Tenta: Open Food Facts -> Open Beauty Facts -> Google Images
// Cacheia resultados para performance

require_once 'config/database.php';

$ean = $_GET['ean'] ?? '';

if (empty($ean)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$cacheDir = 'uploads/cache_img/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$cacheFile = $cacheDir . $ean . '.jpg';

// 1. Servir do cache se existir e não for vazio (e recente, opcional)
if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
    header('Content-Type: image/jpeg');
    readfile($cacheFile);
    exit;
}

// Funções de busca
function fetchUrl($url) {
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
            "ignore_errors" => true,
            "timeout" => 5
        ],
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
    ]);
    return @file_get_contents($url, false, $context);
}

// 2. Open Food Facts
$url = "https://images.openfoodfacts.org/images/products/{$ean}/front_pt.200.jpg";
$img = fetchUrl($url);
if ($img && strlen($img) > 1000) { // Verifica se baixou algo válido
    file_put_contents($cacheFile, $img);
    header('Content-Type: image/jpeg');
    echo $img;
    exit;
}

// 3. Open Beauty Facts
$url = "https://images.openbeautyfacts.org/images/products/{$ean}/front_pt.200.jpg";
$img = fetchUrl($url);
if ($img && strlen($img) > 1000) {
    file_put_contents($cacheFile, $img);
    header('Content-Type: image/jpeg');
    echo $img;
    exit;
}

// 4. Google Images (Scraping)
$url = "https://www.google.com/search?tbm=isch&q=" . $ean;
$html = fetchUrl($url);

if ($html) {
    if (preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/i', $html, $matches)) {
        foreach ($matches[1] as $imgUrl) {
            // Filtra ícones pequenos e imagens do próprio google
            if (strpos($imgUrl, 'http') === 0 && strpos($imgUrl, 'google.com') === false) {
                // Tenta baixar
                $img = fetchUrl($imgUrl);
                if ($img && strlen($img) > 1000) {
                    file_put_contents($cacheFile, $img);
                    header('Content-Type: image/jpeg');
                    echo $img;
                    exit;
                }
            }
            // Fallback para thumbnails do Google (encrypted-tbn0)
            if (strpos($imgUrl, 'encrypted-tbn0') !== false) {
                $img = fetchUrl($imgUrl);
                if ($img && strlen($img) > 1000) {
                    file_put_contents($cacheFile, $img);
                    header('Content-Type: image/jpeg');
                    echo $img;
                    exit;
                }
            }
        }
    }
}

// Se falhar tudo, retorna 404 (o navegador vai mostrar o alt ou fallback)
header("HTTP/1.0 404 Not Found");
?>