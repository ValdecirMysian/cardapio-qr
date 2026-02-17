<?php
/**
 * ═══════════════════════════════════════════════════════════
 * GERADOR DE ÍCONES PWA
 * ═══════════════════════════════════════════════════════════
 * 
 * Sistema seguro para gerar ícones PWA personalizados
 * a partir do logo da farmácia.
 * 
 * SEGURANÇA:
 * - Validação completa de imagens
 * - Tratamento robusto de erros
 * - Fallback para ícones padrão Mediz
 * - Nunca quebra o sistema
 */

/**
 * Gera um ícone PWA redimensionado a partir de uma imagem
 * 
 * @param string $source_path Caminho da imagem original
 * @param string $output_path Caminho do ícone a ser gerado
 * @param int $size Tamanho do ícone (largura e altura)
 * @return bool Retorna true se sucesso, false se falhar
 */
function gerarIconePWA($source_path, $output_path, $size) {
    try {
        // Verifica se GD está disponível
        if (!extension_loaded('gd')) {
            error_log("GD extension not loaded - usando ícones padrão Mediz");
            return false;
        }
        
        // Verifica se arquivo existe
        if (!file_exists($source_path)) {
            error_log("Source file not found: $source_path");
            return false;
        }
        
        // Detecta tipo da imagem
        $imageInfo = @getimagesize($source_path);
        if ($imageInfo === false) {
            error_log("Invalid image file: $source_path");
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // Valida dimensões mínimas
        if ($originalWidth < 72 || $originalHeight < 72) {
            error_log("Image too small: {$originalWidth}x{$originalHeight} (minimum 72x72)");
            return false;
        }
        
        // Carrega imagem original
        $original = null;
        switch ($mimeType) {
            case 'image/png':
                $original = @imagecreatefrompng($source_path);
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $original = @imagecreatefromjpeg($source_path);
                break;
            case 'image/gif':
                $original = @imagecreatefromgif($source_path);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $original = @imagecreatefromwebp($source_path);
                }
                break;
            default:
                error_log("Unsupported image type: $mimeType");
                return false;
        }
        
        if (!$original) {
            error_log("Failed to create image from source: $source_path");
            return false;
        }
        
        // Cria nova imagem com fundo transparente
        $resized = imagecreatetruecolor($size, $size);
        
        if (!$resized) {
            imagedestroy($original);
            error_log("Failed to create resized image");
            return false;
        }
        
        // Preserva transparência
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagealphablending($resized, true);
        
        // Calcula dimensões para manter proporção (centralizado)
        $ratio = min($size / $originalWidth, $size / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        $dstX = (int)(($size - $newWidth) / 2);
        $dstY = (int)(($size - $newHeight) / 2);
        
        // Redimensiona com alta qualidade
        $resample_result = imagecopyresampled(
            $resized, $original,
            $dstX, $dstY, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        if (!$resample_result) {
            imagedestroy($resized);
            imagedestroy($original);
            error_log("Failed to resample image");
            return false;
        }
        
        // Garante que o diretório existe
        $output_dir = dirname($output_path);
        if (!is_dir($output_dir)) {
            @mkdir($output_dir, 0755, true);
        }
        
        // Salva como PNG com compressão máxima
        $result = imagepng($resized, $output_path, 9);
        
        // Libera memória
        imagedestroy($resized);
        imagedestroy($original);
        
        if ($result) {
            // Define permissões corretas
            @chmod($output_path, 0644);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error generating icon: " . $e->getMessage());
        return false;
    } catch (Error $e) {
        error_log("Fatal error generating icon: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se a farmácia tem ícones personalizados
 * 
 * @param int $farmacia_id ID da farmácia
 * @return bool True se tem ícones personalizados
 */
function temIconesPersonalizados($farmacia_id) {
    $icon_dir = 'icons/farmacia_' . $farmacia_id;
    $icon_192 = $icon_dir . '/icon-192x192.png';
    
    return is_dir($icon_dir) && file_exists($icon_192);
}

/**
 * Retorna o caminho base dos ícones para uma farmácia
 * 
 * @param int $farmacia_id ID da farmácia
 * @return string Caminho base dos ícones (com ou sem farmacia_id)
 */
function getCaminhoIcones($farmacia_id) {
    if (temIconesPersonalizados($farmacia_id)) {
        return 'icons/farmacia_' . $farmacia_id;
    }
    return 'icons'; // Ícones padrão Mediz
}
