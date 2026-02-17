<?php
/**
 * ============================================================
 * GERADOR DE ÍCONES PWA
 * ============================================================
 * 
 * Este script gera todos os ícones necessários para o PWA
 * a partir de uma imagem original (logo da farmácia).
 * 
 * REQUISITOS:
 * - PHP com extensão GD habilitada
 * - Imagem original de pelo menos 512x512 pixels
 * 
 * USO:
 * 1. Coloque este arquivo na pasta public/
 * 2. Acesse via navegador: http://seusite.com/generate-icons.php?source=logo.png
 * 3. Ou execute via CLI: php generate-icons.php logo.png
 */

// Configuração
$sizes = [
    16, 32, 72, 96, 128, 144, 152, 167, 180, 192, 384, 512
];

$outputDir = __DIR__ . '/icons';
$sourceImage = null;

// Detecta se é CLI ou Web
if (php_sapi_name() === 'cli') {
    // CLI
    if ($argc < 2) {
        echo "Uso: php generate-icons.php <imagem-original.png>\n";
        echo "Exemplo: php generate-icons.php logo.png\n";
        exit(1);
    }
    $sourceImage = $argv[1];
} else {
    // Web
    $sourceImage = $_GET['source'] ?? null;
    header('Content-Type: text/plain; charset=utf-8');
    
    if (!$sourceImage) {
        echo "Uso: ?source=logo.png\n";
        echo "Exemplo: generate-icons.php?source=uploads/logo.png\n";
        exit;
    }
}

// Verifica se GD está disponível
if (!extension_loaded('gd')) {
    die("Erro: A extensão GD do PHP não está instalada.\nInstale com: sudo apt-get install php-gd\n");
}

// Verifica se a imagem existe
if (!file_exists($sourceImage)) {
    die("Erro: Imagem não encontrada: $sourceImage\n");
}

// Cria pasta de ícones se não existir
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "✅ Pasta 'icons/' criada\n";
}

// Detecta tipo da imagem
$imageInfo = getimagesize($sourceImage);
if ($imageInfo === false) {
    die("Erro: Arquivo não é uma imagem válida.\n");
}

$mimeType = $imageInfo['mime'];
$originalWidth = $imageInfo[0];
$originalHeight = $imageInfo[1];

echo "📷 Imagem original: {$originalWidth}x{$originalHeight} ({$mimeType})\n\n";

if ($originalWidth < 512 || $originalHeight < 512) {
    echo "⚠️  Aviso: Para melhor qualidade, use uma imagem de pelo menos 512x512 pixels.\n\n";
}

// Carrega a imagem original
switch ($mimeType) {
    case 'image/png':
        $original = imagecreatefrompng($sourceImage);
        break;
    case 'image/jpeg':
        $original = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/gif':
        $original = imagecreatefromgif($sourceImage);
        break;
    case 'image/webp':
        $original = imagecreatefromwebp($sourceImage);
        break;
    default:
        die("Erro: Tipo de imagem não suportado: $mimeType\n");
}

if (!$original) {
    die("Erro: Não foi possível carregar a imagem.\n");
}

// Preserva transparência
imagealphablending($original, true);
imagesavealpha($original, true);

// Gera cada tamanho
$generated = 0;
foreach ($sizes as $size) {
    $outputPath = "{$outputDir}/icon-{$size}x{$size}.png";
    
    // Cria nova imagem com fundo transparente
    $resized = imagecreatetruecolor($size, $size);
    
    // Preserva transparência
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
    imagealphablending($resized, true);
    
    // Redimensiona mantendo proporção (centralizado)
    $srcWidth = $originalWidth;
    $srcHeight = $originalHeight;
    
    // Calcula dimensões para manter proporção
    $ratio = min($size / $srcWidth, $size / $srcHeight);
    $newWidth = (int)($srcWidth * $ratio);
    $newHeight = (int)($srcHeight * $ratio);
    $dstX = (int)(($size - $newWidth) / 2);
    $dstY = (int)(($size - $newHeight) / 2);
    
    // Redimensiona
    imagecopyresampled(
        $resized, $original,
        $dstX, $dstY, 0, 0,
        $newWidth, $newHeight,
        $srcWidth, $srcHeight
    );
    
    // Salva como PNG
    if (imagepng($resized, $outputPath, 9)) {
        echo "✅ Gerado: icon-{$size}x{$size}.png\n";
        $generated++;
    } else {
        echo "❌ Erro ao gerar: icon-{$size}x{$size}.png\n";
    }
    
    imagedestroy($resized);
}

// Gera favicon.ico (16x16 e 32x32 combinados)
$faviconPath = __DIR__ . '/favicon.ico';

// Cria ícone 16x16 para favicon
$icon16 = imagecreatetruecolor(16, 16);
imagealphablending($icon16, false);
imagesavealpha($icon16, true);
$transparent = imagecolorallocatealpha($icon16, 0, 0, 0, 127);
imagefill($icon16, 0, 0, $transparent);
imagealphablending($icon16, true);

$ratio = min(16 / $originalWidth, 16 / $originalHeight);
$newWidth = (int)($originalWidth * $ratio);
$newHeight = (int)($originalHeight * $ratio);
$dstX = (int)((16 - $newWidth) / 2);
$dstY = (int)((16 - $newHeight) / 2);
imagecopyresampled($icon16, $original, $dstX, $dstY, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

// Salva como PNG temporário e converte para ICO
$tempPng = sys_get_temp_dir() . '/favicon_temp.png';
imagepng($icon16, $tempPng);
imagedestroy($icon16);

// Copia como favicon (simplificado - na prática, usar biblioteca phpico ou ferramenta externa)
if (copy("{$outputDir}/icon-32x32.png", $faviconPath)) {
    echo "✅ Gerado: favicon.ico (copiado de 32x32)\n";
    $generated++;
}

@unlink($tempPng);

// Limpa memória
imagedestroy($original);

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "🎉 Geração concluída! {$generated} arquivos criados.\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";
echo "Próximos passos:\n";
echo "1. Verifique os ícones gerados na pasta 'icons/'\n";
echo "2. Ajuste o manifest.json se necessário\n";
echo "3. Teste a instalação do PWA no celular\n";
echo "\n";
echo "💡 Dica: Para um favicon.ico de melhor qualidade,\n";
echo "   use: https://realfavicongenerator.net/\n";

// Se for web, mostra link para visualizar
if (php_sapi_name() !== 'cli') {
    echo "\n";
    echo "📁 Ícones gerados:\n";
    foreach ($sizes as $size) {
        echo "   - icons/icon-{$size}x{$size}.png\n";
    }
}
?>
