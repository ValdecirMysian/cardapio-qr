<?php
// Proxy de Imagens Inteligente v2
// Tenta: Open Food/Beauty Facts -> Google Images -> Cosmos -> Cache
ini_set('display_errors', 0);
error_reporting(0);

$ean = $_GET['ean'] ?? '';

if (empty($ean)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$cacheDir = __DIR__ . '/uploads/cache_img/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$cacheFile = $cacheDir . $ean . '.jpg';

// 1. Servir do cache
if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
    header('Content-Type: image/jpeg');
    readfile($cacheFile);
    exit;
}

// Função auxiliar CURL
function fetchUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function saveAndServe($data, $path) {
    if ($data && strlen($data) > 1000) {
        file_put_contents($path, $data);
        header('Content-Type: image/jpeg');
        echo $data;
        exit;
    }
}

// 2. Open Food Facts (Direto)
$url = "https://images.openfoodfacts.org/images/products/{$ean}/front_pt.200.jpg";
$img = fetchUrl($url);
saveAndServe($img, $cacheFile);

// 3. Open Beauty Facts (Direto)
$url = "https://images.openbeautyfacts.org/images/products/{$ean}/front_pt.200.jpg";
$img = fetchUrl($url);
saveAndServe($img, $cacheFile);

// 4. Google Images (Scraping)
$url = "https://www.google.com/search?tbm=isch&q=" . $ean;
$html = fetchUrl($url);

if ($html) {
    // Procura por imagens thumb do Google (encrypted-tbn0)
    if (preg_match_all('/src="(https:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=[^"]+)"/i', $html, $matches)) {
        foreach ($matches[1] as $imgUrl) {
            // Decodifica entidades HTML na URL (&amp; -> &)
            $imgUrl = htmlspecialchars_decode($imgUrl);
            $img = fetchUrl($imgUrl);
            saveAndServe($img, $cacheFile);
        }
    }
}

// 5. Cosmos Bluesoft (Fallback)
$url = "https://cosmos.bluesoft.com.br/produtos/" . $ean;
$html = fetchUrl($url);
if ($html) {
    if (preg_match('/<img[^>]*class="product-thumbnail"[^>]*src="([^"]+)"/i', $html, $matches)) {
        $imgUrl = $matches[1];
        if (strpos($imgUrl, 'http') !== 0) $imgUrl = "https://cosmos.bluesoft.com.br" . $imgUrl;
        $img = fetchUrl($imgUrl);
        saveAndServe($img, $cacheFile);
    }
}

// Se falhar tudo
header("HTTP/1.0 404 Not Found");
// Retorna uma imagem transparente de 1px
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
?>