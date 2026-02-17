<?php
// Limpar qualquer output anterior (espaços em branco, warnings)
// ob_clean(); // Comentado para evitar erros se não houver buffer ativo

// Ativar exibição de erros para debug (remova em produção se preferir)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/xml; charset=UTF-8");

// Ajuste do caminho do banco de dados (estava incorreto)
require_once '../config/database.php';

// Obter token da URL
$token = $_GET['token'] ?? '';

if (!$token) {
    echo '<?xml version="1.0" encoding="UTF-8"?><error>Token nao fornecido</error>';
    exit;
}

// Buscar farmácia
$stmt = $pdo->prepare("SELECT * FROM farmacias WHERE qr_code_token = ?");
$stmt->execute([$token]);
$farmacia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$farmacia) {
    echo '<?xml version="1.0" encoding="UTF-8"?><error>Farmacia nao encontrada</error>';
    exit;
}

// Configurações Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = "$protocol://$host";

// ===========================================
// MODOS DE OPERAÇÃO DO FEED
// ===========================================
// 
// 1. SEM PARÂMETROS: Envia todos os produtos do cardápio
//    URL: feed_meta.php?token=xxx
//
// 2. apenas_promocao=1 (LEGADO): Filtra apenas produtos em promoção
//    URL: feed_meta.php?token=xxx&apenas_promocao=1
//    PROBLEMA: Produtos que saem da promoção SOMEM do feed e a Meta não remove
//
// 3. vitrine_whatsapp=1 (NOVO - RECOMENDADO): Envia todos mas controla visibilidade
//    URL: feed_meta.php?token=xxx&vitrine_whatsapp=1
//    SOLUÇÃO: Todos os produtos são enviados, mas só aparece in_stock se:
//             - estoque_disponivel = 1 E promocao = 1
//             Produtos fora de promoção ficam out_of_stock (Meta esconde automaticamente)
//
// ===========================================

$apenas_promocao = isset($_GET['apenas_promocao']) && $_GET['apenas_promocao'] == '1';
$vitrine_whatsapp = isset($_GET['vitrine_whatsapp']) && $_GET['vitrine_whatsapp'] == '1';

// Buscar produtos
// Apenas produtos ativos e que devem aparecer no cardápio
$sql = "SELECT 
            p.*, 
            c.nome as categoria_nome 
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.farmacia_id = ? 
        AND p.mostrar_no_cardapio = 1";

// Modo LEGADO: filtra na query (não recomendado para WhatsApp)
if ($apenas_promocao && !$vitrine_whatsapp) {
    $sql .= " AND p.promocao = 1";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$farmacia['id']]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Início do XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
    <title><?php echo htmlspecialchars($farmacia['nome']); ?></title>
    <link><?php echo $baseUrl . '/cardapio.php?token=' . $token; ?></link>
    <description>Catálogo de produtos - <?php echo htmlspecialchars($farmacia['nome']); ?></description>
    
    <?php foreach ($produtos as $prod): ?>
    <?php 
        // ===========================================
        // LÓGICA DE DISPONIBILIDADE
        // ===========================================
        
        if ($vitrine_whatsapp) {
            // MODO VITRINE WHATSAPP:
            // Só aparece como disponível se tiver estoque E estiver em promoção
            // Isso faz a Meta esconder automaticamente produtos fora de promoção
            $availability = ($prod['estoque_disponivel'] == 1 && $prod['promocao'] == 1) 
                ? 'in_stock' 
                : 'out_of_stock';
        } else {
            // MODO PADRÃO:
            // Disponibilidade baseada apenas no estoque
            $availability = ($prod['estoque_disponivel'] == 1) ? 'in_stock' : 'out_of_stock';
        }
        
        // Formatar preço (ex: 19.90 BRL)
        $price = number_format($prod['preco'], 2, '.', '') . ' BRL';
        
        // Link da imagem
        $imageLink = '';
        if (!empty($prod['imagem'])) {
            // Se a imagem já for uma URL completa, usa ela
            if (filter_var($prod['imagem'], FILTER_VALIDATE_URL)) {
                $imageLink = $prod['imagem'];
            } else {
                // Se for caminho relativo, adiciona a base
                // Remove barra inicial se houver para evitar duplicidade
                $imgRel = ltrim($prod['imagem'], '/');
                $imageLink = $baseUrl . '/cardapio-qr/' . $imgRel; 
                // Nota: Assumindo que o script roda na raiz ou que o caminho relativo precisa de ajuste.
                // Se o arquivo feed_meta.php está em /cardapio-qr/, e a imagem é 'uploads/x.jpg'
                // A URL deve ser BASE/cardapio-qr/uploads/x.jpg se a base for a raiz do domínio.
                // Ajuste para o ambiente específico:
                if (strpos($host, 'localhost') !== false) {
                     // Localhost (xampp)
                     $imageLink = $baseUrl . '/cardapio-qr/' . $imgRel;
                } else {
                     // Produção (mediz.digital) - Assumindo que o sistema está em /entrar/cardapio-qr/ ou algo assim?
                     // O usuário acessou https://mediz.digital/entrar/cardapio-qr/estoque_rapido
                     // Então o script está em /entrar/cardapio-qr/
                     // Vamos usar URL relativa ao script atual para garantir
                     $pathInfo = pathinfo($_SERVER['PHP_SELF']);
                     $dir = rtrim($pathInfo['dirname'], '/\\'); // /entrar/cardapio-qr
                     $imageLink = $baseUrl . $dir . '/' . $imgRel;
                }
            }
        } else {
            // Imagem placeholder se não tiver
            $imageLink = $baseUrl . '/assets/img/no-image.png'; // Ajustar conforme necessário
        }

        // Link do produto (Deep link)
        // Adicionamos &item=ID para permitir scroll automático ou modal no futuro
        $link = $baseUrl . '/cardapio-qr/cardapio.php?token=' . $token . '&item=' . $prod['id'];
        if (strpos($host, 'mediz.digital') !== false) {
             // Ajuste fino para produção se necessário
             $pathInfo = pathinfo($_SERVER['PHP_SELF']);
             $dir = rtrim($pathInfo['dirname'], '/\\');
             $link = $baseUrl . $dir . '/cardapio.php?token=' . $token . '&item=' . $prod['id'];
        }

        // Descrição
        $description = !empty($prod['descricao']) ? $prod['descricao'] : $prod['nome'];
        $description = strip_tags($description);
        
        // ID único
        // Pode usar o SKU externo se tiver, ou ID do banco
        $id = $prod['id'];
        if (!empty($prod['sku_externo'])) {
            // Opcional: concatenar ou usar preferencialmente
            // $id = $prod['sku_externo']; 
        }
    ?>
    <item>
        <g:id><?php echo htmlspecialchars($id); ?></g:id>
        <g:title><?php echo htmlspecialchars($prod['nome']); ?></g:title>
        <g:description><?php echo htmlspecialchars($description); ?></g:description>
        <g:link><?php echo htmlspecialchars($link); ?></g:link>
        <g:image_link><?php echo htmlspecialchars($imageLink); ?></g:image_link>
        <g:brand><?php echo htmlspecialchars($farmacia['nome']); ?></g:brand>
        <g:condition>new</g:condition>
        <g:availability><?php echo $availability; ?></g:availability>
        <g:price><?php echo $price; ?></g:price>
        
        <?php if (!empty($prod['categoria_nome'])): ?>
        <g:product_type><?php echo htmlspecialchars($prod['categoria_nome']); ?></g:product_type>
        <g:custom_label_0><?php echo htmlspecialchars($prod['categoria_nome']); ?></g:custom_label_0>
        <?php endif; ?>

        <?php if (!empty($prod['ean'])): ?>
        <g:gtin><?php echo htmlspecialchars($prod['ean']); ?></g:gtin>
        <?php endif; ?>

        <?php if (!empty($prod['sku_externo'])): ?>
        <g:mpn><?php echo htmlspecialchars($prod['sku_externo']); ?></g:mpn>
        <?php endif; ?>
        
        <?php if ($prod['promocao']): ?>
        <g:custom_label_1>promocao</g:custom_label_1>
        <?php endif; ?>

        <?php if ($prod['destaque']): ?>
        <g:custom_label_2>destaque</g:custom_label_2>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>