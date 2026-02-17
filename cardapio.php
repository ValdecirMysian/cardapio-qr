<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo'); // Garante fuso horário correto

require_once '../config/database.php';
require_once 'functions.php';

$token = $_GET['token'] ?? $_GET['t'] ?? '';

if (!$token) {
    die("Token não fornecido.");
}

// Buscar informações da farmácia
$stmt = $pdo->prepare("SELECT * FROM farmacias WHERE qr_code_token = ?");
$stmt->execute([$token]);
$farmacia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$farmacia) {
    die("Farmácia não encontrada.");
}

// Garantir que os campos do Pixel existam
$farmacia['pixel_id'] = $farmacia['pixel_id'] ?? null;
$farmacia['pixel_ativo'] = $farmacia['pixel_ativo'] ?? false;

// Verifica imagem de capa (lógica do antigo cardápio)
if (empty($farmacia['imagem_capa'])) {
    $g = glob('uploads/capa_farmacia_' . $farmacia['id'] . '.*');
    if (!empty($g)) { $farmacia['imagem_capa'] = $g[0]; }
}

// Registrar visualização (simples)
$pdo->prepare("UPDATE farmacias SET visualizacoes = visualizacoes + 1 WHERE id = ?")->execute([$farmacia['id']]);

// Buscar produtos e categorias
// Query modificada para suportar hierarquia
$sql = "SELECT 
            p.id as produto_id, p.nome as produto_nome, p.descricao, p.preco, p.imagem, 
            p.promocao, p.destaque, p.tarja, p.exige_receita, p.principio_ativo, p.is_leite,
            p.indicacao, p.contra_indicacao, p.registro_ms, p.tem_tamanhos,
            c.id as categoria_id, c.nome as categoria_nome, c.parent_id, cp.nome as parent_nome
        FROM farmacias f
        LEFT JOIN produtos p ON f.id = p.farmacia_id 
            AND p.mostrar_no_cardapio = TRUE 
            AND p.estoque_disponivel = TRUE
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN categorias cp ON c.parent_id = cp.id
        WHERE f.qr_code_token = ?
        ORDER BY cp.nome, c.nome, p.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute([$token]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estrutura_cardapio = [];
$promocoes = [];
$destaques = [];
$todos_produtos = [];
$contagem_leites = 0;

foreach ($result as $row) {
    if ($row['produto_id']) {
        $prod = [
            'id' => $row['produto_id'],
            'nome' => $row['produto_nome'],
            'descricao' => $row['descricao'],
            'preco' => $row['preco'],
            'imagem' => $row['imagem'],
            'promocao' => $row['promocao'],
            'destaque' => $row['destaque'],
            'tarja' => $row['tarja'],
            'exige_receita' => $row['exige_receita'],
            'principio_ativo' => $row['principio_ativo'],
            'is_leite' => $row['is_leite'],
            'tem_tamanhos' => $row['tem_tamanhos'],
            'indicacao' => $row['indicacao'],
            'contra_indicacao' => $row['contra_indicacao'],
            'registro_ms' => $row['registro_ms'],
            'categoria_nome' => $row['categoria_nome'],
            'parent_id' => $row['parent_id'],
            'parent_nome' => $row['parent_nome']
        ];
        
        $todos_produtos[] = $prod;
        
        if ($prod['is_leite']) $contagem_leites++;
        if ($prod['promocao']) $promocoes[] = $prod;
        if ($prod['destaque']) $destaques[] = $prod;

        $catId = $row['categoria_id'];
        $catNome = $row['categoria_nome'];
        $parentId = $row['parent_id'];
        $parentNome = $row['parent_nome'];

        if ($parentId) {
            // Subcategoria
            if (!isset($estrutura_cardapio[$parentId])) {
                $estrutura_cardapio[$parentId] = [
                    'nome' => $parentNome,
                    'filhos' => [],
                    'produtos_raiz' => []
                ];
            }
            if (!isset($estrutura_cardapio[$parentId]['filhos'][$catId])) {
                $estrutura_cardapio[$parentId]['filhos'][$catId] = [
                    'nome' => $catNome,
                    'produtos' => []
                ];
            }
            $estrutura_cardapio[$parentId]['filhos'][$catId]['produtos'][] = $prod;
        } else {
            // Raiz
            if (!isset($estrutura_cardapio[$catId])) {
                $estrutura_cardapio[$catId] = [
                    'nome' => $catNome,
                    'filhos' => [],
                    'produtos_raiz' => []
                ];
            }
            $estrutura_cardapio[$catId]['produtos_raiz'][] = $prod;
        }
    }
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

function getCategoryIcon($nome) {
    $n = mb_strtolower($nome, 'UTF-8');
    
    // Mapeamento de palavras-chave para ícones FontAwesome
    if (strpos($n, 'bebê') !== false || strpos($n, 'bebe') !== false) return 'fa-baby';
    if (strpos($n, 'infantil') !== false || strpos($n, 'criança') !== false) return 'fa-child';
    if (strpos($n, 'leite') !== false || strpos($n, 'fórmula') !== false || strpos($n, 'formula') !== false) return 'fa-baby-bottle'; // Mamadeira para leites
    if (strpos($n, 'fralda') !== false) return 'fa-baby';
    
    if (strpos($n, 'medicamento') !== false || strpos($n, 'fármaco') !== false || strpos($n, 'remedio') !== false || strpos($n, 'remédio') !== false) return 'fa-pills';
    if (strpos($n, 'genérico') !== false || strpos($n, 'generico') !== false) return 'fa-tablets';
    if (strpos($n, 'antibiótico') !== false) return 'fa-bacteria';
    if (strpos($n, 'xarope') !== false) return 'fa-prescription-bottle';
    
    if (strpos($n, 'perfumaria') !== false || strpos($n, 'perfume') !== false) return 'fa-spray-can';
    if (strpos($n, 'higiene') !== false || strpos($n, 'banho') !== false || strpos($n, 'sabonete') !== false) return 'fa-pump-soap';
    if (strpos($n, 'beleza') !== false || strpos($n, 'cosmético') !== false || strpos($n, 'maquiagem') !== false) return 'fa-magic';
    if (strpos($n, 'cabelo') !== false || strpos($n, 'shampoo') !== false) return 'fa-spray-can';
    if (strpos($n, 'dermo') !== false || strpos($n, 'pele') !== false || strpos($n, 'rosto') !== false || strpos($n, 'facial') !== false) return 'fa-spa';
    if (strpos($n, 'solar') !== false || strpos($n, 'sol') !== false) return 'fa-sun';
    
    if (strpos($n, 'suplemento') !== false || strpos($n, 'vitamina') !== false || strpos($n, 'academia') !== false || strpos($n, 'whey') !== false) return 'fa-dumbbell';
    
    if (strpos($n, 'primeiros socorros') !== false || strpos($n, 'curativo') !== false) return 'fa-first-aid';
    if (strpos($n, 'aparelho') !== false || strpos($n, 'medidor') !== false || strpos($n, 'termômetro') !== false) return 'fa-stethoscope';
    if (strpos($n, 'ortopedi') !== false) return 'fa-crutch';
    
    if (strpos($n, 'bucal') !== false || strpos($n, 'dente') !== false || strpos($n, 'escova') !== false) return 'fa-tooth';
    if (strpos($n, 'homem') !== false || strpos($n, 'barba') !== false) return 'fa-mars';
    if (strpos($n, 'mulher') !== false || strpos($n, 'intima') !== false || strpos($n, 'íntima') !== false) return 'fa-venus';
    if (strpos($n, 'preservativo') !== false) return 'fa-shield-virus';
    
    // Default fallback
    return 'fa-capsules';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= e($farmacia['nome']) ?> - Cardápio Digital</title>
    <!-- FarmaPro Pixel -->
<?php if (!empty($farmacia['pixel_id']) && !empty($farmacia['pixel_ativo'])): ?>
<script src="https://mediz.digital/ads/pixel.js.php?id=<?php echo htmlspecialchars($farmacia['pixel_id']); ?>"></script>
<?php endif; ?>
    
    <!-- Meta Tags para SEO e Compartilhamento -->
    <meta name="description" content="Confira nosso cardápio digital e faça seu pedido!">
    <meta property="og:title" content="<?= e($farmacia['nome']) ?>">
    <meta property="og:description" content="Peça agora pelo nosso cardápio digital.">
    <?php if ($farmacia['logo']): ?>
    <meta property="og:image" content="<?= e($farmacia['logo']) ?>">
    <?php endif; ?>
    
     <!-- Manifest Dinâmico (com token) -->
    <link rel="manifest" href="manifest.php?token=<?= urlencode($token) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/icon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Apple Touch Icons (iOS) -->
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/icon-180x180.png">
    
    <!-- Meta Tags PWA -->
    <meta name="theme-color" content="<?= !empty($farmacia['cor_primaria']) ? e($farmacia['cor_primaria']) : '#0d6efd' ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= e($farmacia['nome']) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?= e($farmacia['nome']) ?>">
    <meta name="msapplication-TileColor" content="<?= !empty($farmacia['cor_primaria']) ? e($farmacia['cor_primaria']) : '#0d6efd' ?>">
    <meta name="msapplication-TileImage" content="icons/icon-144x144.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: <?= !empty($farmacia['cor_primaria']) ? $farmacia['cor_primaria'] : '#0d6efd' ?>;
            --primary-dark: <?= !empty($farmacia['cor_secundaria']) ? $farmacia['cor_secundaria'] : '#0a58ca' ?>;
            --secondary: #6c757d;
            --background: #f8f9fa;
            --surface: #ffffff;
            --text-main: #212529;
            --text-secondary: #6c757d;
            --border-radius: 16px;
            --shadow: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --safe-area-top: env(safe-area-inset-top, 0px);
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            padding-bottom: 80px;
        }

        /* Navbar & Header */
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-toggler {
            border: none;
            color: white;
            padding: 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white !important;
        }

        /* Header Info */
        .header-info {
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 900;
        }

        .pharmacy-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: var(--shadow);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-open {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-closed {
            background-color: #f8d7da;
            color: #842029;
        }

        /* Search */
        .search-container {
            position: relative;
            margin-bottom: 24px;
        }

        .search-input {
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            padding: 15px 50px 15px 20px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
            box-shadow: var(--shadow);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            outline: none;
            background: white;
            transform: translateY(-2px);
        }

        /* Filters */
        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
            justify-content: center;
        }

        .filter-chip {
            background: #f8f9fa;
            border: 2px solid transparent;
            color: #6c757d;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-chip:hover,
        .filter-chip.active {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(123, 104, 238, 0.3);
        }

        /* Cards */
        .categoria-section {
            margin-bottom: 40px;
        }

        .categoria-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 0 5px;
        }

        .categoria-icon {
            width: 48px;
            height: 48px;
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .categoria-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-main);
        }

        .produtos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            justify-content: center;
        }

        @media (min-width: 576px) {
            .produtos-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 16px;
            }
        }

        @media (min-width: 768px) {
            .produtos-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 24px;
            }
        }

        /* Footer MEDIZ */
        .mediz-footer {
            text-align: center;
            padding: 30px 20px;
            margin-top: 40px;
            border-top: 1px solid rgba(0,0,0,0.05);
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .mediz-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .mediz-footer img {
            height: 20px;
            margin-left: 5px;
            vertical-align: middle;
            opacity: 0.8;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
            background: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-img-container {
            position: relative;
            /* padding-top: 75%; Removed to fix layout with fixed height */
            overflow: hidden;
            background: #f8f9fa;
        }

        .card-img-top {
            /* position: absolute; Removed to work with flex container */
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
            transition: transform 0.3s ease;
        }
        
        .card:hover .card-img-top {
            transform: scale(1.05);
        }

        .badge-container {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .badge-leite {
            background-color: #0dcaf0;
            color: #fff;
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }

        .card-text {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .produto-preco {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: auto;
            margin-bottom: 16px;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .btn-whatsapp {
            background: #25D366;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            margin-top: 8px;
        }

        .btn-whatsapp:hover {
            background: #128C7E;
            color: white;
        }

        /* Cart Floating Button */
        .cart-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
            cursor: pointer;
            z-index: 1050;
            transition: var(--transition);
            border: none;
        }

        .cart-toggle:hover {
            transform: scale(1.1);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        /* Cart Sidebar */
        .cart-container {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: white;
            z-index: 1060;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -4px 0 20px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }

        .cart-container.show {
            right: 0;
        }

        .cart-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .cart-footer {
            background: white;
            padding: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        }

        /* Tarja Badges */
        .tarja-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            text-transform: uppercase;
            font-weight: bold;
            color: white;
            vertical-align: middle;
        }
        .tarja-amarela { background-color: #ffc107; color: #000; border: 1px solid #e0a800; }
        .tarja-vermelha { background-color: #dc3545; border: 1px solid #b02a37; }
        .tarja-preta { background-color: #000; border: 1px solid #333; }

        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        /* ===== SCROLL ARROWS ===== */
        .scroll-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: var(--transition);
            opacity: 0.9;
        }
        
        .scroll-btn:hover {
            background: var(--primary);
            color: white;
            opacity: 1;
        }
        
        .scroll-left { left: -15px; }
        .scroll-right { right: -15px; }
        
        @media (max-width: 768px) {
            .scroll-btn { display: none; } /* Ocultar em mobile onde o touch é nativo */
        }

        /* ===== NOVO: PROMOÇÕES HORIZONTAIS ===== */
        .promo-scroll {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 20px 5px;
            scrollbar-width: none; /* Firefox */
        }
        .promo-scroll::-webkit-scrollbar { display: none; }
        
        .promo-card {
            min-width: 280px;
            width: 280px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            flex-shrink: 0;
            padding: 15px;
            position: relative;
            border: 2px solid #ffc107;
            display: flex;
            flex-direction: column;
        }

        /* ===== NOVO: GRID DE CATEGORIAS ===== */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .cat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        
        .cat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .cat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* ===== NOVO: NAVIGATION BREADCRUMB - BOTÃO FLUTUANTE ===== */
/* Botão flutuante - REMOVIDO, substituído pela seta no header */
.nav-breadcrumb {
    display: none !important; /* Escondido permanentemente */
}

/* Seta de voltar no header */
.header-back-arrow {
    display: none;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
    color: white;
    font-size: 1.1rem;
    margin-right: 10px;
}

.header-back-arrow:hover {
    background: rgba(255, 255, 255, 0.15);
}

.header-back-arrow.visible {
    display: flex;
}

.header-back-arrow i {
    font-size: 1.2rem;
}


        /* ===== HEADER (PORTED FROM OLD) ===== */
        .farmacia-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            margin: 30px 0;
            box-shadow: var(--shadow);
            text-align: center;
            border: 1px solid rgba(123, 104, 238, 0.1);
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .farmacia-header h1 {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .farmacia-header--capa {
            color: #fff;
            border: none;
        }

        .farmacia-header--capa h1 {
            background: none;
            -webkit-text-fill-color: initial;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }

        .farmacia-header--capa .lead,
        .farmacia-header--capa p,
        .farmacia-header--capa a { color: #fff !important; }
        .farmacia-header--capa .text-primary,
        .farmacia-header--capa .text-success { color: #fff !important; }

        /* ===== MODAL DE ENTREGA ===== */
        .delivery-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.7) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 2000 !important;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .delivery-modal.show {
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
        }

        /* CSS adicional de segurança */
        .delivery-modal[style*="display: flex"] {
            opacity: 1 !important;
            visibility: visible !important;
        }

        .delivery-modal-content {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.8) translateY(-50px);
            transition: var(--transition);
        }

        .delivery-modal.show .delivery-modal-content {
            transform: scale(1) translateY(0);
        }

        .delivery-modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-modal-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .delivery-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .delivery-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .delivery-modal-body {
            padding: 30px;
        }

        .delivery-form .form-group {
            margin-bottom: 20px;
        }

        .delivery-form label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: block;
        }
        /* ===== ESTILOS PWA ===== */
    .pwa-install-banner {
        position: fixed;
        bottom: -100px;
        left: 0;
        right: 0;
        background: white;
        padding: 16px 20px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        z-index: 99998;
        display: flex;
        align-items: center;
        gap: 16px;
        border-top: 3px solid var(--primary, #0d6efd);
        transition: transform 0.3s ease, bottom 0.3s ease;
    }
    
    .pwa-install-banner.show {
        bottom: 0;
    }
    
    .pwa-install-banner.hide {
        bottom: -100px;
    }
    
    .pwa-banner-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary, #0d6efd) 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .pwa-banner-icon i {
        color: white;
        font-size: 24px;
    }
    
    .pwa-banner-text {
        flex: 1;
        min-width: 0;
    }
    
    .pwa-banner-text strong {
        display: block;
        margin-bottom: 2px;
        color: #1f2937;
    }
    
    .pwa-banner-text span {
        color: #6b7280;
        font-size: 14px;
    }
    
    .pwa-banner-btn {
        background: var(--primary, #0d6efd);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .pwa-banner-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
    }
    
    .pwa-banner-close {
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 18px;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }
    
    .pwa-banner-close:hover {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .pwa-toast {
        position: fixed;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 14px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 12px;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .pwa-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    .pwa-toast button {
        background: white;
        color: #667eea;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .pwa-success-toast {
        position: fixed;
        bottom: 100px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #10b981;
        color: white;
        padding: 14px 24px;
        border-radius: 10px;
        font-weight: 500;
        z-index: 99999;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .pwa-success-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    /* Ajuste para não sobrepor o carrinho flutuante */
    @media (max-width: 768px) {
        .pwa-install-banner {
            padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        }
    }
    
/* ========== ESTILOS POPUP iOS ========== */
.ios-install-popup {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    top: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    z-index: 99999;
    display: none;
    align-items: flex-end;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

.ios-install-popup.show {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.ios-install-content {
    background: white;
    border-radius: 20px 20px 0 0;
    padding: 30px 25px 40px;
    width: 100%;
    max-width: 400px;
    position: relative;
    animation: slideUp 0.4s ease;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
}

.ios-install-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #f1f1f1;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.ios-install-close:hover {
    background: #e0e0e0;
    color: #333;
}

.ios-install-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary, #0d6efd), #667eea);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 32px;
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
}

.ios-install-content h3 {
    text-align: center;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 5px;
}

.ios-install-subtitle {
    text-align: center;
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 25px;
}

.ios-install-steps {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
}

.ios-step {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 12px 0;
}

.ios-step:first-child {
    border-bottom: 1px solid #e9ecef;
    padding-top: 0;
}

.ios-step:last-child {
    padding-bottom: 0;
}

.ios-step-number {
    width: 28px;
    height: 28px;
    background: var(--primary, #0d6efd);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.ios-step-text {
    color: #333;
    font-size: 0.95rem;
    line-height: 1.5;
}

.ios-share-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #007AFF;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    font-size: 12px;
    margin: 0 3px;
    vertical-align: middle;
}

.ios-add-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #007AFF;
    font-size: 18px;
    margin-left: 5px;
    vertical-align: middle;
}

.ios-install-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--primary, #0d6efd), #667eea);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.ios-install-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

.ios-install-btn:active {
    transform: translateY(0);
}

.ios-dont-show {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 15px;
    color: #888;
    font-size: 0.85rem;
    cursor: pointer;
}

.ios-dont-show input {
    cursor: pointer;
}

/* Seta apontando para baixo (indicando onde fica o botão compartilhar) */
.ios-install-content::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 5px;
    background: #ccc;
    border-radius: 3px;
}

/* ========== TOAST DE CONFIRMAÇÃO DE SAÍDA (PWA) ========== */
.exit-toast {
    position: fixed;
    bottom: calc(80px + var(--safe-area-bottom));
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 16px 24px;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 500;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    z-index: 99999;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    gap: 10px;
    pointer-events: none;
    backdrop-filter: blur(10px);
}

.exit-toast.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}

.exit-toast i {
    font-size: 1.2rem;
}
    </style>
</head>
<body>
    <!-- Toast de Confirmação de Saída -->
    <div id="exitToast" class="exit-toast">
        <i class="fas fa-arrow-left"></i>
        <span>Pressione novamente para sair</span>
    </div>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <div class="d-flex align-items-center">
                <!-- Seta de voltar (aparece quando entra em categoria) -->
                <div class="header-back-arrow" id="headerBackArrow" onclick="resetNavigation()">
                    <i class="fas fa-arrow-left"></i>
                </div>
                
                <a class="navbar-brand" href="#">
                    <?php if ($farmacia['logo']): ?>
                    <img src="<?= e($farmacia['logo']) ?>" width="30" height="30" class="d-inline-block align-top rounded-circle" alt="">
                    <?php else: ?>
                    <i class="fas fa-clinic-medical"></i>
                    <?php endif; ?>
                    <?= e($farmacia['nome']) ?>
                </a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto category-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="resetNavigation(); return false;">
                            <i class="fas fa-home me-2"></i> Início
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4 main-container">
        <!-- Header Info (Updated with Cover Image Support) -->
        <div class="farmacia-header <?= !empty($farmacia['imagem_capa']) ? 'farmacia-header--capa' : '' ?>" <?php if (!empty($farmacia['imagem_capa'])): ?>style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= e($farmacia['imagem_capa']) ?>');"<?php endif; ?>>
            <div class="d-flex flex-column align-items-center gap-3">
                <?php if ($farmacia['logo']): ?>
                <img src="<?= e($farmacia['logo']) ?>" alt="Logo" class="pharmacy-logo mb-2">
                <?php else: ?>
                <div class="pharmacy-logo bg-light d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-clinic-medical fa-2x text-muted"></i>
                </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <h1 class="h3 fw-bold mb-2"><?= e($farmacia['nome']) ?></h1>
                    <div class="<?= !empty($farmacia['imagem_capa']) ? 'text-white' : 'text-muted' ?> mb-3">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= e($farmacia['endereco']) ?>
                    </div>
                    
                    <?php
                    $agora = date('H:i');
                    $aberto = $agora >= $farmacia['horario_abertura'] && $agora <= $farmacia['horario_fechamento'];
                    ?>
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <span class="status-badge <?= $aberto ? 'status-open' : 'status-closed' ?>">
                            <i class="fas fa-circle fa-xs"></i>
                            <?= $aberto ? 'Aberto Agora' : 'Fechado' ?>
                        </span>
                        <span class="<?= !empty($farmacia['imagem_capa']) ? 'text-white' : 'text-muted' ?> small">
                            • Fecha às <?= substr($farmacia['horario_fechamento'], 0, 5) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="search-container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="position-relative">
                        <input type="text" class="form-control search-input" id="productSearch" placeholder="🔍 Buscar produtos...">
                        <i id="searchIcon" class="fas fa-search position-absolute" style="right: 20px; top: 50%; transform: translateY(-50%); color: #6c757d; pointer-events: none;"></i>
                        <i id="clearSearchBtn" class="fas fa-times position-absolute" style="right: 20px; top: 50%; transform: translateY(-50%); color: #dc3545; cursor: pointer; display: none;" onclick="search.clear()"></i>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="filter-chips">
                        <span class="filter-chip active" data-filter="all">
                            <i class="fas fa-th-large me-1"></i>Todos
                        </span>
                        <span class="filter-chip" data-filter="promocao">
                            <i class="fas fa-fire me-1"></i>Ofertas
                        </span>
                        <?php if ($contagem_leites > 0): ?>
                        <span class="filter-chip" data-filter="leite">
                            <i class="fas fa-baby-bottle me-1"></i>Leites
                        </span>
                        <?php endif; ?>
                        <span class="filter-chip" data-filter="destaque">
                            <i class="fas fa-star me-1"></i>Destaques
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: CATEGORY GRID (ROOT) -->
        <div id="view-root" class="fade-in mb-5">
            <h3 class="mb-3 text-dark"><i class="fas fa-th-large me-2"></i>Departamentos</h3>
            
            <div class="category-grid">
                <?php foreach ($estrutura_cardapio as $catId => $catData): ?>
                <div class="cat-card" onclick="openCategory(<?= $catId ?>)">
                    <div class="cat-icon">
                        <i class="fas <?= getCategoryIcon($catData['nome']) ?>"></i>
                    </div>
                    <h5 class="mb-0"><?= e($catData['nome']) ?></h5>
                    <small class="text-muted mt-2">
                        <?= count($catData['produtos_raiz']) + count($catData['filhos']) ?> itens
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PROMOÇÕES (HORIZONTAL) -->
        <?php if (!empty($promocoes)): ?>
        <section id="promocoes-section" class="mb-5 fade-in">
            <h3 class="mb-3 text-primary"><i class="fas fa-fire me-2"></i>Ofertas Imperdíveis</h3>
            
            <div class="scroll-wrapper">
                <button class="scroll-btn scroll-left" onclick="scrollPromo(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="promo-scroll" id="promoContainer">
                    <?php foreach ($promocoes as $prod): ?>
                    <div class="promo-card">
                        <div class="position-relative mb-3 text-center">
                            <?php if ($prod['imagem'] && file_exists($prod['imagem'])): ?>
                                <img src="<?= e($prod['imagem']) ?>" style="height: 150px; object-fit: contain;" alt="<?= e($prod['nome']) ?>">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 150px;">
                                    <i class="fas <?= getCategoryIcon($prod['nome']) ?> fa-3x text-secondary opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-danger position-absolute top-0 start-0">Promoção</span>
                        </div>
                        <h6 class="text-truncate"><?= e($prod['nome']) ?></h6>
                        <div class="text-primary fw-bold mb-2"><?= formatPrice($prod['preco']) ?></div>
                        <button class="btn btn-sm btn-outline-primary w-100 mt-auto" onclick="cart.add(<?= $prod['id'] ?>, '<?= addslashes(e($prod['nome'])) ?>', <?= $prod['preco'] ?>, '<?= addslashes(e($prod['imagem'] ?? '')) ?>', <?= $prod['tem_tamanhos'] ? 'true' : 'false' ?>)">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="scroll-btn scroll-right" onclick="scrollPromo(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- VIEW: SINGLE CATEGORY (HIDDEN) -->
<div id="view-category" style="display: none;" class="fade-in">
            
            <!-- ANVISA ALERT (Moved here) -->
            <div id="anvisa-alert" class="alert alert-warning mb-4" style="display: none;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-prescription-bottle-alt fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Informações sobre Medicamentos</h5>
                        <p class="mb-0 small">Medicamentos tarjados só podem ser adquiridos mediante receita médica. Consulte sempre um profissional.</p>
                    </div>
                </div>
            </div>

            <h2 class="mb-4" id="cat-title">Categoria</h2>

            <!-- SUBCATEGORIES PILLS -->
            <div id="subcat-pills" class="d-flex flex-wrap gap-2 mb-4"></div>

            <!-- PRODUCTS GRID -->
            <div class="produtos-grid" id="cat-products"></div>
        </div>

        <!-- VIEW: SEARCH RESULTS (HIDDEN) -->
        <div id="view-search-results" class="produtos-grid fade-in" style="display: none;"></div>

        <!-- HIDDEN DATA FOR JS -->
        <script>
            const MENU_DATA = <?= json_encode($estrutura_cardapio) ?>;
            const ALL_PRODUCTS = <?= json_encode($todos_produtos) ?>;
            
            function formatPrice(price) {
                return 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');
            }

            function openCategory(catId) {
                const data = MENU_DATA[catId];
                if(!data) return;

                document.getElementById('view-root').style.display = 'none';
                document.getElementById('view-search-results').style.display = 'none';
                const promoSection = document.getElementById('promocoes-section');
                if (promoSection) promoSection.style.display = 'none';

                document.getElementById('view-category').style.display = 'block';
                // Mostrar seta de voltar no header
                const headerBackArrow = document.getElementById('headerBackArrow');
                if (headerBackArrow) headerBackArrow.classList.add('visible');
                document.getElementById('cat-title').textContent = data.nome;
                
                // Exibir alerta da ANVISA apenas para Medicamentos
                const anvisaAlert = document.getElementById('anvisa-alert');
                if (anvisaAlert) {
                    const catName = data.nome.toLowerCase();
                    if (catName.includes('medicamento') || 
                        catName.includes('fármaco') || 
                        catName.includes('genérico') || 
                        catName.includes('referência') ||
                        catName.includes('similar') ||
                        catName.includes('controlado') ||
                        catName.includes('antibiótico')) {
                        anvisaAlert.style.display = 'block';
                    } else {
                        anvisaAlert.style.display = 'none';
                    }
                }
                
                // Render Subcategories
                const subContainer = document.getElementById('subcat-pills');
                subContainer.innerHTML = '';
                
                if (data.filhos) {
                    Object.values(data.filhos).forEach(sub => {
                        subContainer.innerHTML += `<span class="filter-chip" onclick="scrollToSub('${sub.nome}')">${sub.nome}</span>`;
                    });
                }
                
                const prodContainer = document.getElementById('cat-products');
                prodContainer.innerHTML = '';

                // Render Root Products
                if(data.produtos_raiz.length > 0) {
                     renderProducts(data.produtos_raiz, prodContainer);
                }

                // Render Child Categories Products
                 if (data.filhos) {
                    Object.values(data.filhos).forEach(sub => {
                        if(sub.produtos.length > 0) {
                            prodContainer.innerHTML += `<div class="col-12 mt-4 mb-2"><h4 class="text-primary border-bottom pb-2" id="sub-${sub.nome}">${sub.nome}</h4></div>`;
                            renderProducts(sub.produtos, prodContainer);
                        }
                    });
                }
                
                // Rolar suavemente para o início do conteúdo (pulando o header)
                const searchContainer = document.querySelector('.search-container');
                if (searchContainer) {
                    const navbarHeight = 80; // Altura aproximada da navbar
                    const targetPosition = searchContainer.getBoundingClientRect().top + window.pageYOffset - navbarHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                } else {
                    window.scrollTo(0, 0);
                }
            }

            function getCategoryIconJS(name) {
                const n = name.toLowerCase();
                if (n.includes('bebê') || n.includes('bebe')) return 'fa-baby';
                if (n.includes('infantil') || n.includes('criança')) return 'fa-child';
                if (n.includes('leite') || n.includes('fórmula') || n.includes('formula')) return 'fa-baby-bottle';
                if (n.includes('fralda')) return 'fa-baby';
                
                if (n.includes('medicamento') || n.includes('fármaco') || n.includes('remedio') || n.includes('remédio')) return 'fa-pills';
                if (n.includes('genérico') || n.includes('generico')) return 'fa-tablets';
                if (n.includes('antibiótico')) return 'fa-bacteria';
                if (n.includes('xarope')) return 'fa-prescription-bottle';
                
                if (n.includes('perfumaria') || n.includes('perfume')) return 'fa-spray-can';
                if (n.includes('higiene') || n.includes('banho') || n.includes('sabonete')) return 'fa-pump-soap';
                if (n.includes('beleza') || n.includes('cosmético') || n.includes('maquiagem')) return 'fa-magic';
                if (n.includes('cabelo') || n.includes('shampoo')) return 'fa-spray-can';
                if (n.includes('dermo') || n.includes('pele') || n.includes('rosto') || n.includes('facial')) return 'fa-spa';
                if (n.includes('solar') || n.includes('sol')) return 'fa-sun';
                
                if (n.includes('suplemento') || n.includes('vitamina') || n.includes('academia') || n.includes('whey')) return 'fa-dumbbell';
                
                if (n.includes('primeiros socorros') || n.includes('curativo')) return 'fa-first-aid';
                if (n.includes('aparelho') || n.includes('medidor') || n.includes('termômetro')) return 'fa-stethoscope';
                if (n.includes('ortopedi')) return 'fa-crutch';
                
                if (n.includes('bucal') || n.includes('dente') || n.includes('escova')) return 'fa-tooth';
                if (n.includes('homem') || n.includes('barba')) return 'fa-mars';
                if (n.includes('mulher') || n.includes('intima') || n.includes('íntima')) return 'fa-venus';
                if (n.includes('preservativo')) return 'fa-shield-virus';
                
                return 'fa-capsules';
            }

            function renderProducts(products, container) {
                products.forEach(p => {
                    // Lógica para Tarja
                    let tarjaHtml = '';
                    if (p.tarja && p.tarja.toLowerCase() !== 'sem_tarja' && p.tarja.toLowerCase() !== 'sem tarja') {
                        let tarjaClass = 'bg-secondary';
                        if (p.tarja.includes('Vermelha')) tarjaClass = 'tarja-vermelha';
                        if (p.tarja.includes('Preta')) tarjaClass = 'tarja-preta';
                        if (p.tarja.includes('Amarela')) tarjaClass = 'tarja-amarela';
                        tarjaHtml = `<span class="tarja-badge ${tarjaClass}">${p.tarja}</span>`;
                    }

                    // Botão de Detalhes (só aparece se tiver info relevante)
                    const hasDetails = p.indicacao || p.contra_indicacao || p.registro_ms || p.principio_ativo;
                    const detailsBtn = hasDetails ? 
                        `<button class="btn btn-sm btn-link text-decoration-none p-0 mt-2" onclick="openProductDetails(${p.id})">
                            <i class="fas fa-info-circle me-1"></i>Ver bula / detalhes
                         </button>` : '';

                    // Lógica de Imagem vs Ícone
                    let imgHtml = '';
                    if (p.imagem) {
                        imgHtml = `<img src="${p.imagem}" class="card-img-top" style="max-height: 100%; object-fit: contain;" onerror="this.parentElement.innerHTML = '<div class=\'d-flex align-items-center justify-content-center bg-light rounded w-100 h-100\'><i class=\'fas ${getCategoryIconJS(p.nome)} fa-4x text-secondary opacity-50\'></i></div>'">`;
                    } else {
                        imgHtml = `<div class="d-flex align-items-center justify-content-center bg-light rounded w-100 h-100"><i class="fas ${getCategoryIconJS(p.nome)} fa-4x text-secondary opacity-50"></i></div>`;
                    }

                    let html = `
                    <div class="produto-item" data-promocao="${p.promocao ? 'true' : 'false'}" data-leite="${p.is_leite ? 'true' : 'false'}" data-destaque="${p.destaque ? 'true' : 'false'}" data-name="${p.nome}" data-description="${p.descricao}">
                        <div class="card h-100">
                             <div class="badge-container">
                                ${p.promocao ? '<span class="badge bg-danger">Promoção</span>' : ''}
                                ${p.destaque ? '<span class="badge bg-warning text-dark">Destaque</span>' : ''}
                             </div>
                             <div class="card-img-container" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                                ${imgHtml}
                             </div>
                             <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-truncate mb-1">${p.nome}</h5>
                                
                                ${p.principio_ativo ? `<small class="text-muted mb-2"><i class="fas fa-flask me-1"></i>${p.principio_ativo.substring(0, 30)}${p.principio_ativo.length > 30 ? '...' : ''}</small>` : ''}
                                ${tarjaHtml}
                                
                                <div class="produto-preco mb-3 mt-2">${formatPrice(p.preco)}</div>
                                
                                ${detailsBtn}

                                <button class="btn btn-primary mt-auto" onclick="cart.add(${p.id}, '${p.nome.replace(/'/g, "\\'")}', ${p.preco}, '${p.imagem || ''}', ${p.tem_tamanhos ? 'true' : 'false'})">
                                    Adicionar
                                </button>
                             </div>
                        </div>
                    </div>
                    `;
                    container.innerHTML += html;
                });
            }

            function openProductDetails(id) {
                const product = ALL_PRODUCTS.find(p => p.id == id);
                if (!product) return;

                const modal = document.getElementById('productDetailsModal');
                const body = document.getElementById('productDetailsBody');
                
                let content = `
                    <div class="text-center mb-4">
                        ${product.imagem ? `<img src="${product.imagem}" class="img-fluid rounded mb-3" style="max-height: 200px;">` : ''}
                        <h5 class="fw-bold">${product.nome}</h5>
                        <div class="text-primary h4">${formatPrice(product.preco)}</div>
                    </div>
                `;

                if (product.principio_ativo) {
                    content += `
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-flask me-2 text-primary"></i>Princípio Ativo</h6>
                            <p class="text-muted">${product.principio_ativo}</p>
                        </div>
                    `;
                }

                if (product.registro_ms) {
                    content += `
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-registered me-2 text-primary"></i>Registro MS</h6>
                            <p class="text-muted">${product.registro_ms}</p>
                        </div>
                    `;
                }

                if (product.indicacao) {
                    content += `
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-check-circle me-2 text-success"></i>Indicação</h6>
                            <p class="text-muted">${product.indicacao}</p>
                        </div>
                    `;
                }

                if (product.contra_indicacao) {
                    content += `
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Contraindicação</h6>
                            <p class="text-muted">${product.contra_indicacao}</p>
                        </div>
                    `;
                }

                if (product.tarja) {
                    content += `
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-tag me-2 text-secondary"></i>Classificação</h6>
                            <span class="badge bg-secondary">${product.tarja}</span>
                        </div>
                    `;
                }

                // Add to cart button inside modal
                content += `
                    <hr>
                    <button class="btn btn-primary w-100 btn-lg" onclick="cart.add(${product.id}, '${product.nome.replace(/'/g, "\\'")}', ${product.preco}, '${product.imagem || ''}', ${product.tem_tamanhos ? 'true' : 'false'}); closeProductDetails();">
                        <i class="fas fa-shopping-cart me-2"></i>Adicionar ao Carrinho
                    </button>
                `;

                body.innerHTML = content;
                
                modal.classList.add('show');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeProductDetails() {
                const modal = document.getElementById('productDetailsModal');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }

            function resetNavigation() {
                document.getElementById('view-category').style.display = 'none';
                // Esconder seta de voltar do header
                const headerBackArrow = document.getElementById('headerBackArrow');
                if (headerBackArrow) headerBackArrow.classList.remove('visible');
                document.getElementById('view-search-results').style.display = 'none';
                document.getElementById('view-root').style.display = 'block';
                const promoSection = document.getElementById('promocoes-section');
                if (promoSection) promoSection.style.display = 'block';
                
                // Limpar busca e filtros visuais
                const searchInput = document.getElementById('productSearch');
                if(searchInput) searchInput.value = '';
                
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                const allChip = document.querySelector('.filter-chip[data-filter="all"]');
                if(allChip) allChip.classList.add('active');
                
                // Resetar objeto search
                search.currentFilter = 'all';
                const searchResults = document.getElementById('searchResults');
                if(searchResults) searchResults.style.display = 'none';

                window.scrollTo(0,0);
            }

            function scrollToSub(name) {
                 const el = document.getElementById('sub-'+name);
                 if(el) el.scrollIntoView({behavior: 'smooth'});
            }

            function scrollPromo(direction) {
                const container = document.getElementById('promoContainer');
                if (container) {
                    const scrollAmount = 300; // Largura aproximada de um card + gap
                    container.scrollBy({
                        left: direction * scrollAmount,
                        behavior: 'smooth'
                    });
                }
            }
        </script>
    </div>

    <!-- Carrinho Melhorado -->
    <div class="cart-container" id="cartContainer">
        <div class="cart-header">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>Meu Carrinho
            </h5>
            <button class="btn-close btn-close-white" onclick="cart.toggle()"></button>
        </div>
        
        <div class="cart-body">
            <div class="cart-items" id="cartItems">
                <div class="text-center py-5" id="emptyCart">
                    <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                    <h5>Seu carrinho está vazio</h5>
                    <p class="text-muted">Adicione produtos para começar</p>
                </div>
            </div>
        </div>
        
        <div class="cart-footer" id="cartFooter" style="display: none;">
            <div class="cart-summary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Itens no carrinho:</span>
                    <span id="cartItemCount" class="badge bg-primary fs-6">0</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Subtotal:</span>
                    <span id="cartSubtotal" class="fw-semibold">R$ 0,00</span>
                </div>
                
                <div id="cartDelivery" class="d-flex justify-content-between align-items-center mb-2" style="display: none;">
                    <span id="cartDeliveryText" class="text-muted">Entrega:</span>
                    <span id="cartDeliveryValue" class="fw-semibold">R$ 0,00</span>
                </div>
                
                <div id="freeDeliveryNotice" style="display: none;"></div>
                
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total:</span>
                    <strong id="cartTotal" class="text-primary h4 mb-0">R$ 0,00</strong>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <?php if ($farmacia['whatsapp']): ?>
                <button class="btn btn-whatsapp btn-lg" onclick="console.log('Botão clicado!'); delivery.openModal();">
    <i class="fab fa-whatsapp me-2"></i>Finalizar Pedido no WhatsApp
</button>
                <?php endif; ?>
                
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-secondary w-100" onclick="cart.toggle()">
                            <i class="fas fa-arrow-left me-1"></i>Continuar
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-danger w-100" onclick="cart.clear()">
                            <i class="fas fa-trash me-1"></i>Limpar
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Os preços podem sofrer alterações.
                </small>
            </div>
        </div>
    </div>

    <!-- Cart Toggle -->
<button class="cart-toggle" onclick="cart.toggle()" id="cartToggleBtn">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge" id="cartToggleBadge" style="display: none;">0</span>
    </button>

    <!-- Modal de Dados de Entrega -->
    <div class="delivery-modal" id="deliveryModal">
        <div class="delivery-modal-content">
            <div class="delivery-modal-header">
                <h4>
                    <i class="fas fa-shipping-fast me-2"></i>
                    Dados para Entrega
                </h4>
                <button type="button" class="btn btn-whatsapp me-2" onclick="delivery.sendNow()">
                    <i class="fab fa-whatsapp me-2"></i>Pular e Enviar Agora
                </button>
                <button class="delivery-modal-close" onclick="console.log('Botão fechar clicado'); delivery.closeModal();" type="button">
    <i class="fas fa-times"></i>
</button>
            </div>
            
            <div class="delivery-modal-body">
                <div id="savedInfoIndicator" class="saved-info-indicator" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    Dados salvos automaticamente! Você pode editá-los abaixo se necessário.
                </div>
                <form class="delivery-form" id="deliveryForm">
                    <div class="form-group">
                        <label for="customerName" class="required">Nome Completo</label>
                        <input type="text" 
                               class="form-control" 
                               id="customerName" 
                               placeholder="Digite seu nome completo"
                               required>
                        <div class="form-text">Como você gostaria de ser chamado(a)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customerPhone" class="required">Telefone</label>
                        <input type="tel" 
                               class="form-control" 
                               id="customerPhone" 
                               placeholder="(00) 00000-0000"
                               required>
                        <div class="form-text">Para contato sobre a entrega</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deliveryAddress" class="required">Endereço</label>
                        <input type="text" 
                               class="form-control" 
                               id="deliveryAddress" 
                               placeholder="Rua, Avenida, etc."
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="houseNumber" class="required">Número</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="houseNumber" 
                                       placeholder="Nº"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="complement">Complemento</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="complement" 
                                       placeholder="Apto, Casa, etc.">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="neighborhood" class="required">Bairro</label>
                        <input type="text" 
                               class="form-control" 
                               id="neighborhood" 
                               placeholder="Nome do bairro"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentMethod" class="required">Forma de Pagamento</label>
                        <select class="form-select" id="paymentMethod" required>
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência Bancária</option>
                        </select>
                        <div class="form-text">Como você pretende pagar o pedido</div>
                    </div>
                    
                    <div class="form-group" id="changeGroup" style="display: none;">
                        <label for="changeFor">Troco para quanto?</label>
                        <input type="text" 
                               class="form-control" 
                               id="changeFor" 
                               placeholder="R$ 50,00">
                        <div class="form-text">Deixe em branco se não precisar de troco</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observations">Observações</label>
                        <textarea class="form-control" 
                                  id="observations" 
                                  rows="3" 
                                  placeholder="Alguma observação especial? Referência para entrega?"></textarea>
                        <div class="form-text">Informações adicionais para facilitar a entrega</div>
                    </div>
                </form>
            </div>
            
            <div class="delivery-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="delivery.closeModal()">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </button>
                <button type="button" class="btn btn-whatsapp" onclick="delivery.submitOrder()">
                    <i class="fab fa-whatsapp me-2"></i>Enviar Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Produto -->
    <div class="delivery-modal" id="productDetailsModal">
        <div class="delivery-modal-content">
            <div class="delivery-modal-header">
                <h4><i class="fas fa-info-circle me-2"></i>Detalhes do Medicamento</h4>
                <button class="delivery-modal-close" onclick="closeProductDetails()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="delivery-modal-body" id="productDetailsBody">
                <!-- Conteúdo será injetado via JS -->
            </div>
        </div>
    </div>

    <!-- Back to Top -->
    <button class="btn btn-primary position-fixed" 
            style="bottom: 20px; left: 20px; border-radius: 50%; width: 50px; height: 50px; display: none; z-index: 998;"
            id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- Popup Instrução iOS -->
<div id="iosInstallPopup" class="ios-install-popup">
    <div class="ios-install-content">
        <button class="ios-install-close" onclick="closeIosPopup()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="ios-install-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        
        <h3>Adicione à sua tela inicial!</h3>
        <p class="ios-install-subtitle">Tenha acesso rápido ao cardápio</p>
        
        <div class="ios-install-steps">
            <div class="ios-step">
                <div class="ios-step-number">1</div>
                <div class="ios-step-text">
                    Toque em <span class="ios-share-icon"><i class="fas fa-share-square"></i></span> <strong>Compartilhar</strong>
                </div>
            </div>
            
            <div class="ios-step">
                <div class="ios-step-number">2</div>
                <div class="ios-step-text">
                    Role e toque em<br>
                    <strong>"Adicionar à Tela de Início"</strong>
                    <span class="ios-add-icon"><i class="fas fa-plus-square"></i></span>
                </div>
            </div>
        </div>
        
        <button class="ios-install-btn" onclick="closeIosPopup()">
            <i class="fas fa-check me-2"></i>OK, Entendi!
        </button>
        
        <label class="ios-dont-show">
            <input type="checkbox" id="iosDontShowAgain" onchange="setIosDontShow()">
            <span>Não mostrar novamente</span>
        </label>
    </div>
</div>

    <!-- Footer MEDIZ -->
    <footer class="mediz-footer">
        <div class="container">
            <p class="mb-1">&copy; <?= date('Y') ?> <strong><?= e($farmacia['nome']) ?></strong>. Todos os direitos reservados.</p>
            <p class="mb-0">
                Desenvolvido por <a href="https://mediz.digital" target="_blank">MEDIZ.DIGITAL</a>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== WRAPPER DO PIXEL FARMAPRO =====
        const FPPixel = {
            isActive() {
                return typeof FarmaProPixel !== 'undefined';
            },
            track(event, data = {}) {
                if (this.isActive()) {
                    try {
                        switch(event) {
                            case 'PageView':
                                FarmaProPixel.pageView();
                                break;
                            case 'ViewContent':
                                FarmaProPixel.viewContent(data);
                                break;
                            case 'AddToCart':
                                FarmaProPixel.addToCart(data);
                                break;
                            case 'InitiateCheckout':
                                FarmaProPixel.initiateCheckout(data);
                                break;
                            case 'Purchase':
                                FarmaProPixel.purchase(data);
                                break;
                            case 'Contact':
                                FarmaProPixel.contact(data);
                                break;
                            case 'Lead':
                                FarmaProPixel.lead(data);
                                break;
                            case 'Contact':
                                FarmaProPixel.contact(data);
                                break;
                            case 'Lead':
                                FarmaProPixel.lead(data);
                                break;
                        }
                        console.log('📊 Pixel FarmaPro:', event, data);
                    } catch (e) {
                        console.warn('Pixel error:', e);
                    }
                }
            }
        };

        // Dispara PageView automaticamente
        document.addEventListener('DOMContentLoaded', () => {
            FPPixel.track('PageView');
        });

        // ===== SISTEMA DE CARRINHO MELHORADO =====
        const cart = {
            items: JSON.parse(localStorage.getItem('cart') || '[]'),
            
            add(id, name, price, image = '', temTamanhos = false) {
                console.log('Adicionando produto:', { id, name, price, image, temTamanhos });
                
                // Se o produto tem tamanhos, mostrar modal de seleção
                if (temTamanhos) {
                    this.showSizeModal(id, name, price, image);
                    return;
                }
                
                const existing = this.items.find(item => item.id === parseInt(id) && !item.tamanho_id);
                
                if (existing) {
                    existing.quantity += 1;
                } else {
                    this.items.push({ 
                        id: parseInt(id), 
                        name: name, 
                        price: parseFloat(price), 
                        image: image, 
                        quantity: 1,
                        tamanho_id: null,
                        tamanho_nome: null
                    });
                }
                
                this.update();
                this.save();
                this.showNotification(`${name} adicionado ao carrinho!`, 'success');
                
                // Pixel: AddToCart
                FPPixel.track('AddToCart', {
                    content_id: id.toString(),
                    content_name: name,
                    value: parseFloat(price),
                    num_items: 1
                });
            },
            
            addWithSize(id, name, price, image, tamanhoId, tamanhoNome, precoAdicional = 0) {
                console.log('Adicionando produto com tamanho:', { id, name, price, image, tamanhoId, tamanhoNome, precoAdicional });
                
                const finalPrice = parseFloat(price) + parseFloat(precoAdicional);
                const nameWithSize = `${name} - ${tamanhoNome}`;
                
                const existing = this.items.find(item => 
                    item.id === parseInt(id) && 
                    item.tamanho_id === parseInt(tamanhoId)
                );
                
                if (existing) {
                    existing.quantity += 1;
                } else {
                    this.items.push({ 
                        id: parseInt(id), 
                        name: nameWithSize, 
                        price: finalPrice, 
                        image: image, 
                        quantity: 1,
                        tamanho_id: parseInt(tamanhoId),
                        tamanho_nome: tamanhoNome,
                        produto_nome_original: name
                    });
                }
                
                this.update();
                this.save();
                this.showNotification(`${nameWithSize} adicionado ao carrinho!`, 'success');
                
                // Pixel: AddToCart com tamanho
                FPPixel.track('AddToCart', {
                    content_id: id.toString(),
                    content_name: nameWithSize,
                    value: finalPrice,
                    num_items: 1
                });
            },
            
            showSizeModal(productId, productName, productPrice, productImage) {
                // Buscar tamanhos do produto
                fetch(`get_product_sizes.php?id=${productId}`)
                    .then(response => response.json())
                    .then(sizes => {
                        if (sizes.length === 0) {
                            this.showNotification('Este produto não possui tamanhos configurados!', 'warning');
                            return;
                        }
                        
                        this.createSizeModal(productId, productName, productPrice, productImage, sizes);
                    })
                    .catch(error => {
                        console.error('Erro ao buscar tamanhos:', error);
                        this.showNotification('Erro ao carregar tamanhos do produto!', 'error');
                    });
            },
            
            createSizeModal(productId, productName, productPrice, productImage, sizes) {
                // Remove modal existente se houver
                const existingModal = document.getElementById('sizeModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                const modalHTML = `
                    <div class="delivery-modal show" id="sizeModal">
                        <div class="delivery-modal-content">
                            <div class="delivery-modal-header">
                                <h4><i class="fas fa-ruler me-2"></i>Escolha o Tamanho</h4>
                                <button class="delivery-modal-close" onclick="cart.closeSizeModal()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="delivery-modal-body">
                                <div class="text-center mb-3">
                                    <h5>${productName}</h5>
                                    <p class="text-muted">Selecione o tamanho desejado:</p>
                                </div>
                                <div class="size-options">
                                    ${sizes.map(size => `
                                        <div class="size-option mb-2" data-size-id="${size.id}">
                                            <div class="card border-primary" style="cursor: pointer;">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>${size.nome}</strong>
                                                            ${size.descricao ? `<br><small class="text-muted">${size.descricao}</small>` : ''}
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-primary">
                                                                R$ ${(parseFloat(productPrice) + parseFloat(size.preco_adicional || 0)).toFixed(2).replace('.', ',')}
                                                            </div>
                                                            ${size.preco_adicional > 0 ? `<small class="text-success">+R$ ${parseFloat(size.preco_adicional).toFixed(2).replace('.', ',')}</small>` : ''}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                document.body.style.overflow = 'hidden';
                
                // Adicionar eventos de clique nos tamanhos
                sizes.forEach(size => {
                    const sizeElement = document.querySelector(`[data-size-id="${size.id}"]`);
                    sizeElement.addEventListener('click', () => {
                        this.addWithSize(
                            productId, 
                            productName, 
                            productPrice, 
                            productImage, 
                            size.id, 
                            size.nome, 
                            size.preco_adicional || 0
                        );
                        this.closeSizeModal();
                    });
                });
            },
            
            closeSizeModal() {
                const modal = document.getElementById('sizeModal');
                if (modal) {
                    modal.remove();
                    document.body.style.overflow = '';
                }
            },
            
            remove(id) {
                console.log('Removendo item:', id);
                this.items = this.items.filter(item => item.id !== parseInt(id));
                this.update();
                this.save();
                this.showNotification('Item removido do carrinho!', 'success');
            },
            
            updateQuantity(id, quantity, tamanhoId = null) {
                console.log('Atualizando quantidade:', id, quantity, tamanhoId);
                const parsedQuantity = parseInt(quantity);
                
                if (parsedQuantity <= 0) {
                    this.remove(id, tamanhoId);
                    return;
                }
                
                const item = this.items.find(item => 
                    item.id === parseInt(id) && 
                    (tamanhoId ? item.tamanho_id === parseInt(tamanhoId) : !item.tamanho_id)
                );
                
                if (item) {
                    item.quantity = parsedQuantity;
                    this.update();
                    this.save();
                }
            },
            
            remove(id, tamanhoId = null) {
                console.log('Removendo item:', id, tamanhoId);
                this.items = this.items.filter(item => 
                    !(item.id === parseInt(id) && 
                      (tamanhoId ? item.tamanho_id === parseInt(tamanhoId) : !item.tamanho_id))
                );
                this.update();
                this.save();
                this.showNotification('Item removido do carrinho!', 'success');
            },
            
           update() {
                const container = document.getElementById('cartItems');
                const footer = document.getElementById('cartFooter');
                const emptyCart = document.getElementById('emptyCart');
                const itemCount = document.getElementById('cartItemCount');
                
                const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
                const subtotalPrice = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                
                // Calcular entrega - VALORES PHP CORRIGIDOS
                const valorEntregaGratis = <?= !empty($farmacia['valor_entrega_gratis']) ? number_format($farmacia['valor_entrega_gratis'], 2, '.', '') : 'null' ?>;
                const taxaEntrega = <?= !empty($farmacia['taxa_entrega']) ? number_format($farmacia['taxa_entrega'], 2, '.', '') : 'null' ?>;
                
                let custoEntrega = 0;
                let entregaGratis = false;
                
                // DEBUG: Verificar valores recebidos do PHP
                console.log('Valores do PHP:', {
                    valorEntregaGratis: valorEntregaGratis,
                    taxaEntrega: taxaEntrega,
                    subtotalPrice: subtotalPrice,
                    tipo_valorEntregaGratis: typeof valorEntregaGratis,
                    tipo_taxaEntrega: typeof taxaEntrega
                });
                
                // NOVA LÓGICA DE CÁLCULO
                if (subtotalPrice > 0) {
                    if (taxaEntrega !== null && taxaEntrega > 0) {
                        // Há taxa de entrega configurada
                        if (valorEntregaGratis !== null && valorEntregaGratis > 0) {
                            // Há valor mínimo para entrega grátis
                            if (subtotalPrice >= valorEntregaGratis) {
                                entregaGratis = true;
                                custoEntrega = 0;
                            } else {
                                entregaGratis = false;
                                custoEntrega = taxaEntrega;
                            }
                        } else {
                            // Só há taxa, sem entrega grátis
                            entregaGratis = false;
                            custoEntrega = taxaEntrega;
                        }
                    } else {
                        // Não há taxa configurada - entrega sempre grátis
                        entregaGratis = true;
                        custoEntrega = 0;
                    }
                }
                
                const totalPrice = subtotalPrice + custoEntrega;
                
                // DEBUG: Log do resultado do cálculo
                console.log('Resultado do cálculo:', {
                    custoEntrega: custoEntrega,
                    entregaGratis: entregaGratis,
                    totalPrice: totalPrice
                });
                
                // Update badges
                const badges = [
                    document.getElementById('cartToggleBadge')
                ].filter(el => el !== null);
                
                badges.forEach(el => {
                    if (totalItems > 0) {
                        el.style.display = 'inline-block';
                        el.textContent = totalItems;
                    } else {
                        el.style.display = 'none';
                    }
                });
                
                // Update item count
                if (itemCount) {
                    itemCount.textContent = totalItems;
                }
                
                // Update cart content
                if (this.items.length === 0) {
                    if (emptyCart) emptyCart.style.display = 'block';
                    if (footer) footer.style.display = 'none';
                    return;
                }
                
                if (emptyCart) emptyCart.style.display = 'none';
                if (footer) footer.style.display = 'block';
                
                // Criar HTML do carrinho
                if (container) {
                   container.innerHTML = this.items.map(item => `
                    <div class="cart-item" data-item-id="${item.id}" data-tamanho-id="${item.tamanho_id || ''}">
                        <div class="d-flex align-items-start">
                            <img src="${this.getImageSrc(item.image)}" 
                                 class="me-3 rounded" 
                                 style="width: 60px; height: 60px; object-fit: cover; flex-shrink: 0;"
                                 alt="${this.escapeHtml(item.name)}"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZjhmOWZhIiBzdHJva2U9IiNkZWUyZTYiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4PSIxOCIgeT0iMTgiPgo8cGF0aCBkPSJNMTIgMkwyIDdWMTdMMTIgMjJMMjIgMTdWN0wxMiAyWiIgc3Ryb2tlPSIjNmM3NTdkIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4KPC9zdmc+'">
                            
                            <div class="flex-grow-1">
                                <div class="fw-bold text-truncate mb-1" style="max-width: 250px;" title="${this.escapeHtml(item.name)}">
                                    ${this.escapeHtml(item.name)}
                                </div>
                                ${item.tamanho_nome ? `<div class="small text-info mb-1"><i class="fas fa-ruler me-1"></i>Tamanho: ${item.tamanho_nome}</div>` : ''}
                                <div class="text-primary fw-semibold mb-2">
                                    R$ ${item.price.toFixed(2).replace('.', ',')}
                                </div>
                                    
                                   <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="cart.updateQuantity(${item.id}, ${item.quantity - 1}, ${item.tamanho_id || 'null'})"
                                                    title="Diminuir quantidade"
                                                    ${item.quantity <= 1 ? 'disabled' : ''}>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="form-control form-control-sm text-center" 
                                                   style="width: 70px;" 
                                                   value="${item.quantity}" 
                                                   min="1" 
                                                   max="99"
                                                   onchange="cart.updateQuantity(${item.id}, this.value, ${item.tamanho_id || 'null'})"
                                                   title="Quantidade">
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="cart.updateQuantity(${item.id}, ${item.quantity + 1}, ${item.tamanho_id || 'null'})"
                                                    title="Aumentar quantidade">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="cart.confirmRemove(${item.id}, '${this.escapeQuotes(item.name)}', ${item.tamanho_id || 'null'})"
                                                title="Remover item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="small text-muted">
                                        Subtotal: <strong>R$ ${(item.price * item.quantity).toFixed(2).replace('.', ',')}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
                
                // Atualizar resumo do carrinho - SEÇÃO CORRIGIDA
                const subtotalElement = document.getElementById('cartSubtotal');
                const deliveryElement = document.getElementById('cartDelivery');
                const deliveryTextElement = document.getElementById('cartDeliveryText');
                const deliveryValueElement = document.getElementById('cartDeliveryValue');
                const totalElement = document.getElementById('cartTotal');
                
                if (subtotalElement) {
                    subtotalElement.textContent = `R$ ${subtotalPrice.toFixed(2).replace('.', ',')}`;
                }
                
                // LÓGICA CORRIGIDA PARA EXIBIÇÃO DA ENTREGA
                if (deliveryElement && deliveryTextElement) {
                    // Se há configuração de entrega (qualquer uma das duas)
                    if (valorEntregaGratis !== null || taxaEntrega !== null) {
                        deliveryElement.style.display = 'flex';
                        
                        if (entregaGratis && valorEntregaGratis !== null) {
                            // Entrega grátis conquistada
                            deliveryTextElement.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Entrega Grátis!</span>';
                            if (deliveryValueElement) {
                                deliveryValueElement.textContent = 'R$ 0,00';
                                deliveryValueElement.className = 'fw-semibold text-success';
                            }
                        } else if (custoEntrega > 0) {
                            // Há taxa de entrega
                            deliveryTextElement.innerHTML = '<span class="text-muted">Taxa de Entrega</span>';
                            if (deliveryValueElement) {
                                deliveryValueElement.textContent = `R$ ${custoEntrega.toFixed(2).replace('.', ',')}`;
                                deliveryValueElement.className = 'fw-semibold text-warning';
                            }
                        } else {
                            // Entrega grátis por padrão (sem configurações específicas)
                            deliveryTextElement.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Entrega Grátis</span>';
                            if (deliveryValueElement) {
                                deliveryValueElement.textContent = 'R$ 0,00';
                                deliveryValueElement.className = 'fw-semibold text-success';
                            }
                        }
                        
                        // DEBUG: Log para verificar valores
                        console.log('Debug Entrega:', {
                            valorEntregaGratis,
                            taxaEntrega, 
                            subtotalPrice, 
                            custoEntrega, 
                            entregaGratis
                        });
                        
                    } else {
                        // Nenhuma configuração de entrega - ocultar
                        deliveryElement.style.display = 'none';
                    }
                }
                
                if (totalElement) {
                    totalElement.textContent = `R$ ${totalPrice.toFixed(2).replace('.', ',')}`;
                }
                
                // Mostrar quanto falta para entrega grátis - LÓGICA CORRIGIDA
                const freeDeliveryNotice = document.getElementById('freeDeliveryNotice');
                if (freeDeliveryNotice && valorEntregaGratis !== null && taxaEntrega !== null && !entregaGratis && subtotalPrice > 0) {
                    const faltaPara = valorEntregaGratis - subtotalPrice;
                    if (faltaPara > 0) {
                        freeDeliveryNotice.style.display = 'block';
                        freeDeliveryNotice.innerHTML = `
                            <div class="alert alert-info py-2 mb-2">
                                <small><i class="fas fa-info-circle me-1"></i>
                                Faltam <strong>R$ ${faltaPara.toFixed(2).replace('.', ',')}</strong> para ganhar entrega grátis!</small>
                            </div>
                        `;
                    } else {
                        freeDeliveryNotice.style.display = 'none';
                    }
                } else if (freeDeliveryNotice) {
                    freeDeliveryNotice.style.display = 'none';
                }
            },
            
           confirmRemove(id, name, tamanhoId = null) {
                if (confirm(`Deseja remover "${name}" do carrinho?`)) {
                    this.remove(id, tamanhoId);
                }
            },
            
            getImageSrc(image) {
                if (image && image.trim() !== '' && image !== 'undefined') {
                    return image;
                }
                
                return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZjhmOWZhIiBzdHJva2U9IiNkZWUyZTYiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4PSIxOCIgeT0iMTgiPgo8cGF0aCBkPSJNMTIgMkwyIDdWMTdMMTIgMjJMMjIgMTdWN0wxMiAyWiIgc3Ryb2tlPSIjNmM3NTdkIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4KPC9zdmc+';
            },
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },
            
            escapeQuotes(text) {
                return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
            },
            
            toggle() {
                const cartContainer = document.getElementById('cartContainer');
                if (cartContainer) {
                    cartContainer.classList.toggle('show');
                }
            },
            
            save() {
                try {
                    localStorage.setItem('cart', JSON.stringify(this.items));
                } catch (error) {
                    console.error('Erro ao salvar carrinho:', error);
                    this.showNotification('Erro ao salvar carrinho', 'error');
                }
            },
            
            clear() {
                if (confirm('Deseja limpar todo o carrinho?')) {
                    this.items = [];
                    this.update(); // Isso vai limpar a exibição também
                    this.save();
                    this.showNotification('Carrinho limpo!', 'success');
                    
                    // FORÇAR LIMPEZA VISUAL ADICIONAL
                    const container = document.getElementById('cartItems');
                    const footer = document.getElementById('cartFooter');
                    const emptyCart = document.getElementById('emptyCart');
                    
                    if (container) {
                        container.innerHTML = '';
                    }
                    
                    if (footer) {
                        footer.style.display = 'none';
                    }
                    
                    if (emptyCart) {
                        emptyCart.style.display = 'block';
                    }
                    
                    // Limpar badges
                    const badges = [
                        document.getElementById('cartToggleBadge')
                    ].filter(el => el !== null);
                    
                    badges.forEach(el => {
                        el.style.display = 'none';
                        el.textContent = '0';
                    });
                }
            },
            
            showNotification(message, type = 'success') {
                document.querySelectorAll('.custom-notification').forEach(el => el.remove());
                
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} position-fixed custom-notification`;
                notification.style.cssText = `
                    top: 90px; 
                    right: 20px; 
                    z-index: 1060; 
                    min-width: 300px;
                    max-width: 400px;
                    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                    border-radius: 15px;
                    animation: slideInRight 0.3s ease;
                    border: none;
                `;
                
                const icons = {
                    'success': 'check-circle',
                    'warning': 'exclamation-triangle',
                    'error': 'times-circle'
                };
                
                const icon = icons[type] || 'info-circle';
                
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${icon} me-2"></i>
                        <span class="flex-grow-1">${message}</span>
                        <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.style.animation = 'slideOutRight 0.3s ease';
                        setTimeout(() => {
                            if (notification.parentElement) {
                                notification.remove();
                            }
                        }, 300);
                    }
                }, 4000);
            }
        };

       // ===== SISTEMA DE ENTREGA COMPLETO =====
const delivery = {
    data: {},
    isOpening: false,
    
    init() {
        this.loadSavedData();
        this.setupFormHandlers();
    },
    
    loadSavedData() {
        try {
            const saved = localStorage.getItem('deliveryData');
            if (saved) {
                this.data = JSON.parse(saved);
                console.log('Dados de entrega carregados:', this.data);
            }
        } catch (error) {
            console.error('Erro ao carregar dados salvos:', error);
        }
    },
    
    saveData() {
        try {
            localStorage.setItem('deliveryData', JSON.stringify(this.data));
        } catch (error) {
            console.error('Erro ao salvar dados:', error);
        }
    },
    
    setupFormHandlers() {
        // Handler para mostrar campo de troco
        const paymentSelect = document.getElementById('paymentMethod');
        const changeGroup = document.getElementById('changeGroup');
        
        if (paymentSelect && changeGroup) {
            paymentSelect.addEventListener('change', () => {
                changeGroup.style.display = paymentSelect.value === 'dinheiro' ? 'block' : 'none';
            });
        }
        
        // Máscara para telefone
        const phoneInput = document.getElementById('customerPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    if (value.length < 14) {
                        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    }
                }
                e.target.value = value;
            });
        }
        
        // Máscara para valor do troco
        const changeInput = document.getElementById('changeFor');
        if (changeInput) {
            changeInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toFixed(2);
                value = 'R$ ' + value.replace('.', ',');
                e.target.value = value;
            });
        }
    },
    
    openModal() {
        console.log('🎯 openModal chamado!');
        console.log('📦 Items no carrinho:', cart.items.length);
        
        if (this.isOpening) {
            console.log('⚠️ Modal já está abrindo, ignorando...');
            return;
        }
        
        if (cart.items.length === 0) {
            console.log('⚠️ Carrinho vazio');
            cart.showNotification('Adicione produtos ao carrinho primeiro!', 'warning');
            return;
        }
        
        this.isOpening = true;
        
        // Pixel: InitiateCheckout
        const checkoutTotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const checkoutItems = cart.items.reduce((sum, item) => sum + item.quantity, 0);
        FPPixel.track('InitiateCheckout', {
            value: checkoutTotal,
            num_items: checkoutItems
        });
        
        console.log('📝 Populando formulário...');
        this.populateForm();
        
        const modal = document.getElementById('deliveryModal');
        console.log('🎭 Modal encontrado:', modal);
        
        if (modal) {
            console.log('✅ Abrindo modal...');
            
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(0, 0, 0, 0.7) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 9999 !important;
                opacity: 1 !important;
                visibility: visible !important;
            `;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                this.isOpening = false;
            }, 1000);
            
            setTimeout(() => {
                const firstInput = modal.querySelector('input:not([readonly])');
                if (firstInput) firstInput.focus();
            }, 300);
        } else {
            console.error('❌ Modal não encontrado!');
            this.isOpening = false;
        }
    },
    
    closeModal() {
        console.log('🚪 Fechando modal...', new Date().getTime());
        
        if (this.isOpening) {
            console.log('⚠️ Modal ainda abrindo, ignorando fechamento');
            return;
        }
        
        const modal = document.getElementById('deliveryModal');
        if (modal) {
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
            
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('show');
                modal.style.cssText = '';
            }, 300);
            
            document.body.style.overflow = '';
            console.log('✅ Modal fechado');
        }
    },
    
    populateForm() {
        const fields = [
            'customerName', 'customerPhone', 'deliveryAddress', 
            'houseNumber', 'complement', 'neighborhood', 
            'paymentMethod', 'changeFor', 'observations'
        ];
        
        let hasData = false;
        
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element && this.data[field]) {
                element.value = this.data[field];
                hasData = true;
            }
        });
        
        const indicator = document.getElementById('savedInfoIndicator');
        if (indicator) {
            indicator.style.display = hasData ? 'block' : 'none';
        }
        
        const changeGroup = document.getElementById('changeGroup');
        if (changeGroup && this.data.paymentMethod === 'dinheiro') {
            changeGroup.style.display = 'block';
        }
    },
    
    validateForm() {
        const required = ['customerName', 'customerPhone', 'deliveryAddress', 'houseNumber', 'neighborhood', 'paymentMethod'];
        const errors = [];
        
        required.forEach(field => {
            const element = document.getElementById(field);
            if (!element || !element.value.trim()) {
                errors.push(this.getFieldLabel(field));
                if (element) {
                    element.style.borderColor = '#dc3545';
                    setTimeout(() => {
                        element.style.borderColor = '';
                    }, 3000);
                }
            }
        });
        
        if (errors.length > 0) {
            const message = `Por favor, preencha os campos: ${errors.join(', ')}`;
            cart.showNotification(message, 'warning');
            return false;
        }
        
        return true;
    },
    
    getFieldLabel(field) {
        const labels = {
            'customerName': 'Nome Completo',
            'customerPhone': 'Telefone',
            'deliveryAddress': 'Endereço',
            'houseNumber': 'Número',
            'neighborhood': 'Bairro',
            'paymentMethod': 'Forma de Pagamento'
        };
        return labels[field] || field;
    },
    
    collectFormData() {
        const fields = [
            'customerName', 'customerPhone', 'deliveryAddress', 
            'houseNumber', 'complement', 'neighborhood', 
            'paymentMethod', 'changeFor', 'observations'
        ];
        
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                this.data[field] = element.value.trim();
            }
        });
        
        this.saveData();
    },
    
    submitOrder() {
        if (!this.validateForm()) {
            return;
        }
        
        this.collectFormData();
        
        // Verificar se deve usar Evolution API ou WhatsApp tradicional
        const usarEvolutionAPI = <?= isset($farmacia['usar_evolution_api']) && $farmacia['usar_evolution_api'] ? 'true' : 'false' ?>;
        
        if (usarEvolutionAPI) {
            this.enviarViaEvolutionAPI();
        } else {
            this.sendToWhatsApp();
        }
    },
    
    async enviarViaEvolutionAPI() {
        try {
            // Mostrar loading
            cart.showNotification('📤 Enviando pedido via Evolution API...', 'info');
            
            // Preparar dados do pedido
            const dadosPedido = {
                token: '<?= $farmacia['qr_code_token'] ?>',
                nome: this.data.customerName,
                telefone: this.data.customerPhone,
                endereco: this.data.deliveryAddress,
                numero: this.data.houseNumber,
                complemento: this.data.complement || '',
                bairro: this.data.neighborhood,
                pagamento: this.data.paymentMethod,
                troco: this.data.changeFor || '',
                observacoes: this.data.observations || '',
                itens: cart.items.map(item => ({
                    id: item.id,
                    nome: item.name,
                    preco: item.price,
                    quantidade: item.quantity,
                    tamanho_id: item.tamanho_id || null,
                    tamanho_nome: item.tamanho_nome || null
                }))
            };
            
            // Enviar para a API
            const response = await fetch('processar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosPedido)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Fechar modal e mostrar sucesso
                this.closeModal();
                cart.showNotification('✅ Pedido enviado automaticamente via Evolution API!', 'success');
                
                // Perguntar se quer limpar carrinho após 2 segundos
                setTimeout(() => {
                    if (confirm('Pedido enviado com sucesso! 🎉\n\nDeseja limpar o carrinho?')) {
                        cart.clear();
                    }
                }, 2000);
                
            } else {
                throw new Error(result.message || 'Erro desconhecido');
            }
            
        } catch (error) {
            console.error('Erro Evolution API:', error);
            cart.showNotification('❌ Erro no envio automático: ' + error.message, 'error');
            
            // Fallback para WhatsApp tradicional
            if (confirm('Falha no envio automático.\n\nDeseja abrir WhatsApp manualmente?')) {
                this.sendToWhatsApp();
            }
        }
    },
    
    // FUNÇÃO MELHORADA E BONITA PARA WHATSAPP
    sendToWhatsApp(skip = false) {
        const subtotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Calcular entrega
        const valorEntregaGratis = <?= isset($farmacia['valor_entrega_gratis']) && $farmacia['valor_entrega_gratis'] ? $farmacia['valor_entrega_gratis'] : 'null' ?>;
        const taxaEntrega = <?= isset($farmacia['taxa_entrega']) && $farmacia['taxa_entrega'] ? $farmacia['taxa_entrega'] : 'null' ?>;
        
        let custoEntrega = 0;
        let entregaGratis = false;
        
        if (valorEntregaGratis && taxaEntrega && subtotal > 0) {
            if (subtotal >= valorEntregaGratis) {
                entregaGratis = true;
                custoEntrega = 0;
            } else {
                custoEntrega = taxaEntrega;
            }
        }
        
        const total = subtotal + custoEntrega;
        const agora = new Date();
        const dataFormatada = agora.toLocaleDateString('pt-BR');
        const horaFormatada = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        
        // Mensagem formatada de forma compatível (ASCII)
        let mensagem = '';
        
        mensagem += '*NOVO PEDIDO - FARMÁCIA*\n';
        mensagem += '------------------------------\n\n';
        mensagem += `Data: ${dataFormatada}\n`;
        mensagem += `Horário: ${horaFormatada}\n`;
        mensagem += `Origem: Catálogo Digital\n\n`;
        
        if (!skip) {
            mensagem += '*DADOS DO CLIENTE*\n';
            mensagem += '--------------------\n';
            mensagem += `Nome: ${this.data.customerName}\n`;
            mensagem += `Telefone: ${this.data.customerPhone}\n\n`;
            
            mensagem += '*ENDEREÇO DE ENTREGA*\n';
            mensagem += '--------------------\n';
            mensagem += `Endereço: ${this.data.deliveryAddress}, ${this.data.houseNumber}\n`;
            
            if (this.data.complement) {
                mensagem += `Complemento: ${this.data.complement}\n`;
            }
            
            mensagem += `Bairro: ${this.data.neighborhood}\n\n`;
        } else {
            mensagem += '*Envio rápido sem dados de entrega*\n\n';
        }
        
        mensagem += '*PRODUTOS SOLICITADOS*\n';
        mensagem += '--------------------\n';
        
        cart.items.forEach((item, index) => {
            const itemSubtotal = item.price * item.quantity;
            const numero = String(index + 1).padStart(2, '0');
            mensagem += `\n${numero}. *${item.name}*\n`;
            
            if (item.tamanho_nome) {
                mensagem += `    Tamanho: ${item.tamanho_nome}\n`;
            }
            
            mensagem += `    Quantidade: ${item.quantity} unidade${item.quantity > 1 ? 's' : ''}\n`;
            mensagem += `    Valor unitário: R$ ${item.price.toFixed(2).replace('.', ',')}\n`;
            mensagem += `    Subtotal: R$ ${itemSubtotal.toFixed(2).replace('.', ',')}\n`;
            mensagem += '    ------------------------\n';
        });
        
        mensagem += '\n*RESUMO FINANCEIRO*\n';
        mensagem += '--------------------\n';
        
        mensagem += `Subtotal dos produtos: R$ ${subtotal.toFixed(2).replace('.', ',')}\n`;
        
        if (valorEntregaGratis && taxaEntrega) {
            if (entregaGratis) {
                mensagem += `Entrega: GRÁTIS\n`;
                mensagem += `    (Pedido acima de R$ ${valorEntregaGratis.toFixed(2).replace('.', ',')})\n`;
            } else if (custoEntrega > 0) {
                mensagem += `Taxa de entrega: R$ ${custoEntrega.toFixed(2).replace('.', ',')}\n`;
                mensagem += `    (Grátis a partir de R$ ${valorEntregaGratis.toFixed(2).replace('.', ',')})\n`;
            }
        }
        
        mensagem += '------------------------------\n';
        mensagem += `*TOTAL GERAL: R$ ${total.toFixed(2).replace('.', ',')}*\n`;
        mensagem += '------------------------------\n\n';
        
        if (!skip) {
            mensagem += '*FORMA DE PAGAMENTO*\n';
            mensagem += '--------------------\n';
            
            const formasPagamento = {
                'dinheiro': 'Dinheiro',
                'cartao_debito': 'Cartão de Débito',
                'cartao_credito': 'Cartão de Crédito',
                'pix': 'PIX',
                'transferencia': 'Transferência Bancária'
            };
            
            mensagem += `${formasPagamento[this.data.paymentMethod] || this.data.paymentMethod}\n`;
            
            if (this.data.paymentMethod === 'dinheiro' && this.data.changeFor) {
                mensagem += `Troco para: ${this.data.changeFor}\n`;
            }
            mensagem += '\n';
        } else {
            mensagem += '*Observação:* Cliente optou por enviar sem informar dados. Por favor, confirme endereço e forma de pagamento pelo chat.\n\n';
        }
        
        if (!skip && this.data.observations && this.data.observations.trim()) {
            mensagem += '*OBSERVAÇÕES*\n';
            mensagem += '--------------------\n';
            mensagem += `${this.data.observations}\n\n`;
        }
        
        mensagem += '------------------------------\n';
        mensagem += '*EM QUANTO TEMPO ESTARÁ*\n';
        mensagem += '*DISPONÍVEL PARA ENTREGA?*\n';
        mensagem += '------------------------------\n\n';
        
        mensagem += '_Obrigado pela preferência!_\n';
        mensagem += '_Pedido gerado automaticamente pelo catálogo digital_';
        
         // Enviar para WhatsApp
        const numeroWhatsApp = '<?= preg_replace('/[^0-9]/', '', $farmacia['whatsapp'] ?? '') ?>';
        
        // Pixel: Contact (pedido enviado via WhatsApp)
        FPPixel.track('Contact', {
            value: total,
            contact_type: 'whatsapp'
        });
        
        // Pixel: Purchase (conversão de venda)
        FPPixel.track('Purchase', {
            value: total,
            currency: 'BRL',
            content_name: 'Pedido via WhatsApp',
            num_items: cart.items.reduce((sum, item) => sum + item.quantity, 0)
        });
        
        if (numeroWhatsApp) {
            const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensagem)}`;
            
            // Abrir WhatsApp em nova aba
            window.open(urlWhatsApp, '_blank');
            
            // Fechar modal
            this.closeModal();
            
            // ✅ NOVO: Limpar carrinho automaticamente após 1 segundo
            setTimeout(() => {
                // Log para debug
                console.log('🧹 Iniciando limpeza automática do carrinho...');
                console.log('📦 Itens antes da limpeza:', cart.items.length);
                
                // Limpar array de itens
                cart.items = [];
                
                // Atualizar interface
                cart.update();
                
                // Salvar no localStorage
                cart.save();
                
                // Log após limpeza
                console.log('✅ Carrinho limpo com sucesso!');
                console.log('📦 Itens após limpeza:', cart.items.length);
                
                // Notificação de sucesso
                cart.showNotification('✅ Pedido enviado! Carrinho limpo automaticamente.', 'success');
                
            }, 1000); // Aguarda 1 segundo para garantir que o WhatsApp foi aberto
            
        } else {
            cart.showNotification('❌ WhatsApp não configurado para esta farmácia!', 'error');
        }
    },
    
    sendNow() {
        if (cart.items.length === 0) {
            cart.showNotification('Adicione produtos ao carrinho primeiro!', 'warning');
            return;
        }
        this.closeModal();
        this.sendToWhatsApp(true);
    }
};
        
        // ===== SISTEMA DE BUSCA E FILTROS =====
        const search = {
            currentFilter: 'all',
            
            init() {
                const searchInput = document.getElementById('productSearch');
                const filterChips = document.querySelectorAll('.filter-chip');
                
                if (searchInput) {
                    searchInput.addEventListener('input', () => this.filter());
                    
                    const placeholders = [
                        '🔍 Buscar produtos...',
                        '💊 Procurar medicamentos...',
                        '🍼 Encontrar leites...',
                        '🔎 Digite o nome do produto...'
                    ];
                    let placeholderIndex = 0;
                    
                    setInterval(() => {
                        if (searchInput === document.activeElement) return;
                        placeholderIndex = (placeholderIndex + 1) % placeholders.length;
                        searchInput.placeholder = placeholders[placeholderIndex];
                    }, 3000);
                }
                
                filterChips.forEach(chip => {
                    chip.addEventListener('click', () => {
                        filterChips.forEach(c => c.classList.remove('active'));
                        chip.classList.add('active');
                        this.currentFilter = chip.dataset.filter;
                        this.filter();
                        
                        if ('vibrate' in navigator) {
                            navigator.vibrate(50);
                        }
                    });
                });
            },
            
            clear() {
                const searchInput = document.getElementById('productSearch');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                    this.filter();
                }
            },

            filter() {
                const searchTerm = document.getElementById('productSearch')?.value.toLowerCase().trim() || '';
                
                // Toggle Icons
                const searchIcon = document.getElementById('searchIcon');
                const clearBtn = document.getElementById('clearSearchBtn');
                
                if (searchTerm) {
                    if(searchIcon) searchIcon.style.display = 'none';
                    if(clearBtn) clearBtn.style.display = 'block';
                } else {
                    if(searchIcon) searchIcon.style.display = 'block';
                    if(clearBtn) clearBtn.style.display = 'none';
                }
                
                // Se busca vazia e filtro 'todos', volta para home
                if (searchTerm === '' && this.currentFilter === 'all') {
                    resetNavigation();
                    return;
                }

                // Ocultar outras views
                document.getElementById('view-root').style.display = 'none';
                document.getElementById('view-category').style.display = 'none';
                const promoSection = document.getElementById('promocoes-section');
                if(promoSection) promoSection.style.display = 'none';
                
                // Mostrar container de resultados
                const resultsContainer = document.getElementById('view-search-results');
                resultsContainer.style.display = 'grid'; // Usa grid layout
                resultsContainer.innerHTML = '';
                
                let visibleCount = 0;
                
                // Filtrar array global de produtos
                const filtered = ALL_PRODUCTS.filter(p => {
                    // Filtro de Texto
                    if (searchTerm) {
                        const name = p.nome.toLowerCase();
                        const desc = p.descricao ? p.descricao.toLowerCase() : '';
                        
                        const searchWords = searchTerm.split(' ');
                        const matches = searchWords.every(word => 
                            name.includes(word) || desc.includes(word)
                        );
                        if (!matches) return false;
                    }
                    
                    // Filtro de Chips (Categorias/Tags)
                    if (this.currentFilter !== 'all') {
                        if (this.currentFilter === 'promocao' && !p.promocao) return false;
                        if (this.currentFilter === 'destaque' && !p.destaque) return false;
                        if (this.currentFilter === 'leite' && !p.is_leite) return false;
                    }
                    
                    return true;
                });
                
                // Renderizar Resultados
                if (filtered.length > 0) {
                    renderProducts(filtered, resultsContainer);
                    
                    // Adicionar cabeçalho de resultados se necessário
                    if (this.currentFilter !== 'all' || searchTerm) {
                        const title = searchTerm ? `Resultados para "${searchTerm}"` : 
                                      this.currentFilter === 'promocao' ? 'Ofertas' :
                                      this.currentFilter === 'leite' ? 'Leites' :
                                      this.currentFilter === 'destaque' ? 'Destaques' : 'Produtos';
                                      
                        resultsContainer.insertAdjacentHTML('afterbegin', 
                            `<div class="col-12 mb-3"><h4 class="text-primary border-bottom pb-2">${title}</h4></div>`
                        );
                    }
                } else {
                    resultsContainer.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Nenhum produto encontrado</h5>
                            <p class="text-muted">Tente termos diferentes ou remova os filtros.</p>
                            <button class="btn btn-outline-primary mt-3" onclick="resetNavigation()">
                                Voltar para o Início
                            </button>
                        </div>
                    `;
                }
                
                this.updateResultsCount(filtered.length, ALL_PRODUCTS.length);
            },
            
            updateResultsCount(visible, total) {
                let countElement = document.getElementById('searchResults');
                if (!countElement) {
                    countElement = document.createElement('div');
                    countElement.id = 'searchResults';
                    countElement.className = 'small text-muted mt-2';
                    const searchContainer = document.querySelector('.search-container .row');
                    if (searchContainer) {
                        searchContainer.appendChild(countElement);
                    }
                }
                
                if (visible !== total) {
                    countElement.innerHTML = `<i class="fas fa-filter me-1"></i>Mostrando ${visible} de ${total} produtos`;
                    countElement.style.display = 'block';
                } else {
                    countElement.style.display = 'none';
                }
            }
        };

        // ===== ZOOM DE IMAGEM =====
        function openImageZoom(container) {
            const img = container.querySelector('img');
            if (!img) return;
            
            const overlay = document.createElement('div');
            overlay.className = 'image-zoom-overlay';
            overlay.onclick = () => closeImageZoom(overlay);
            
            const zoomedImg = document.createElement('img');
            zoomedImg.src = img.src;
            zoomedImg.alt = img.alt;
            
            overlay.appendChild(zoomedImg);
            document.body.appendChild(overlay);
            
            setTimeout(() => overlay.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
            
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    closeImageZoom(overlay);
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }

        function closeImageZoom(overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            
            setTimeout(() => {
                if (overlay.parentElement) {
                    overlay.remove();
                }
            }, 300);
        }

        // ===== NAVEGAÇÃO POR CATEGORIA =====
        function updateActiveCategory() {
            // Not needed in new layout
        }

        // ===== INICIALIZAÇÃO =====
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 Inicializando sistema...');
            
            try {
                console.log('🛒 Inicializando carrinho...');
                cart.update();
                console.log('✅ Carrinho inicializado');
            } catch (error) {
                console.error('❌ Erro no carrinho:', error);
            }

            try {
                console.log('🔍 Inicializando busca...');
                search.init();
                console.log('✅ Busca inicializada');
            } catch (error) {
                console.error('❌ Erro na busca:', error);
            }

            try {
                console.log('🚚 Inicializando entrega...');
                delivery.init();
                console.log('✅ Entrega inicializada');
            } catch (error) {
                console.error('❌ Erro na entrega:', error);
            }
            
            // Back to top button
            const backToTop = document.getElementById('backToTop');
            let isScrolling = false;
            
            window.addEventListener('scroll', () => {
                if (!isScrolling) {
                    isScrolling = true;
                    requestAnimationFrame(() => {
                        if (backToTop) {
                            backToTop.style.display = window.pageYOffset > 300 ? 'block' : 'none';
                        }
                        isScrolling = false;
                    });
                }
            });
            
            if (backToTop) {
                backToTop.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
            
            // Fechar modais e carrinho quando clicar fora
            document.addEventListener('click', (e) => {
                // Fechar carrinho
                const cartContainer = document.getElementById('cartContainer');
                const cartButtons = document.querySelectorAll('.cart-toggle, [onclick*="cart.toggle"]');
                
                let isCartButton = false;
                cartButtons.forEach(btn => {
                    if (btn.contains(e.target)) isCartButton = true;
                });
                
                if (cartContainer && 
                    !cartContainer.contains(e.target) && 
                    !isCartButton && 
                    cartContainer.classList.contains('show')) {
                    cart.toggle();
                }
            });
            
            console.log('✅ Sistema inicializado com sucesso!');
        });

        // ===== ANALYTICS E MONITORAMENTO =====
        const analytics = {
            track(event, data = {}) {
                console.log('Analytics:', event, data);
            }
        };

        // ===== UTILITÁRIOS GLOBAIS =====
        window.pharmacyApp = {
            cart,
            delivery,
            search,
            analytics,
            version: '2.0.0'
        };
    </script>
     <script>
    (function() {
        // Token atual da farmácia
        const FARMACIA_TOKEN = '<?= e($token) ?>';
        
        // ===== REGISTRO DO SERVICE WORKER =====
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('service-worker.js', {
                        scope: './'
                    });
                    console.log('✅ Service Worker registrado:', registration.scope);
                    
                    // Verifica atualizações
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('🔄 Nova versão encontrada...');
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                showUpdateToast();
                            }
                        });
                    });
                } catch (error) {
                    console.error('❌ Erro ao registrar Service Worker:', error);
                }
            });
        }
        
        // Toast de atualização disponível
        function showUpdateToast() {
            const toast = document.createElement('div');
            toast.className = 'pwa-toast';
            toast.innerHTML = `
                <span>🚀 Nova versão disponível!</span>
                <button onclick="window.location.reload()">Atualizar</button>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
        }
        
        // ===== PROMPT DE INSTALAÇÃO =====
        let deferredPrompt = null;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('📱 PWA pode ser instalado');
            
            // Mostra banner após 3 segundos
            setTimeout(() => {
                if (deferredPrompt) showInstallBanner();
            }, 3000);
        });
        
        function showInstallBanner() {
            // Não mostra se já está instalado
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('📱 App já está em modo standalone');
                return;
            }
            
            // Não mostra se já dispensou hoje
            const dismissKey = 'pwa-install-dismissed-' + FARMACIA_TOKEN;
            if (localStorage.getItem(dismissKey) === new Date().toDateString()) {
                return;
            }
            
            // Cria o banner
            const banner = document.createElement('div');
            banner.id = 'pwaInstallBanner';
            banner.className = 'pwa-install-banner';
            banner.innerHTML = `
                <div class="pwa-banner-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="pwa-banner-text">
                    <strong>Instalar App</strong>
                    <span>Acesse mais rápido direto da tela inicial!</span>
                </div>
                <button class="pwa-banner-btn" onclick="window.installPWA()">
                    Instalar
                </button>
                <button class="pwa-banner-close" onclick="window.dismissInstallBanner()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(banner);
            
            // Animação de entrada
            requestAnimationFrame(() => {
                banner.classList.add('show');
            });
        }
        
        // Função global para instalar
        window.installPWA = async function() {
            if (!deferredPrompt) {
                console.log('❌ Prompt de instalação não disponível');
                return;
            }
            
            // Mostra o prompt nativo
            deferredPrompt.prompt();
            
            // Aguarda resposta
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`📱 Resultado: ${outcome}`);
            
            deferredPrompt = null;
            window.dismissInstallBanner();
            
            if (outcome === 'accepted') {
                showSuccessToast('🎉 App instalado com sucesso!');
            }
        };
        
        // Função global para dispensar banner
        window.dismissInstallBanner = function() {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) {
                banner.classList.remove('show');
                banner.classList.add('hide');
                setTimeout(() => banner.remove(), 300);
            }
            
            // Salva que dispensou hoje (por token)
            const dismissKey = 'pwa-install-dismissed-' + FARMACIA_TOKEN;
            localStorage.setItem(dismissKey, new Date().toDateString());
        };
        
        function showSuccessToast(message) {
            const toast = document.createElement('div');
            toast.className = 'pwa-success-toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Detecta instalação
        window.addEventListener('appinstalled', () => {
            console.log('✅ PWA instalado com sucesso!');
            deferredPrompt = null;
        });
        
        // Log se já está em standalone
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('📱 Rodando como PWA instalado');
        }
    })();
    </script>
    <script>
// ========== POPUP iOS ==========
(function() {
    // Detecta se é iOS
    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }
    
    // Detecta se já está em modo standalone (já instalado)
    function isStandalone() {
        return window.navigator.standalone === true || 
               window.matchMedia('(display-mode: standalone)').matches;
    }
    
    // Detecta se é Safari (único navegador que permite instalar PWA no iOS)
    function isSafari() {
        return /Safari/.test(navigator.userAgent) && !/Chrome|CriOS|FxiOS/.test(navigator.userAgent);
    }
    
    // Verifica se deve mostrar o popup
    function shouldShowIosPopup() {
        // Só mostra se for iOS
        if (!isIOS()) return false;
        
        // Não mostra se já está instalado
        if (isStandalone()) return false;
        
        // Não mostra se usuário marcou "não mostrar novamente"
        if (localStorage.getItem('ios-popup-never') === 'true') return false;
        
        // Não mostra se já mostrou hoje
        const lastShown = localStorage.getItem('ios-popup-date');
        if (lastShown === new Date().toDateString()) return false;
        
        return true;
    }
    
    // Mostra o popup
    function showIosPopup() {
        const popup = document.getElementById('iosInstallPopup');
        if (popup) {
            popup.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Salva que mostrou hoje
            localStorage.setItem('ios-popup-date', new Date().toDateString());
        }
    }
    
    // Fecha o popup (função global)
    window.closeIosPopup = function() {
        const popup = document.getElementById('iosInstallPopup');
        if (popup) {
            popup.classList.remove('show');
            document.body.style.overflow = '';
        }
    };
    
    // Marca para não mostrar novamente (função global)
    window.setIosDontShow = function() {
        const checkbox = document.getElementById('iosDontShowAgain');
        if (checkbox && checkbox.checked) {
            localStorage.setItem('ios-popup-never', 'true');
        } else {
            localStorage.removeItem('ios-popup-never');
        }
    };
    
    // Fecha ao clicar fora
    document.addEventListener('click', function(e) {
        const popup = document.getElementById('iosInstallPopup');
        const content = popup?.querySelector('.ios-install-content');
        
        if (popup && popup.classList.contains('show') && !content?.contains(e.target)) {
            closeIosPopup();
        }
    });
    
    // Inicializa após carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        if (shouldShowIosPopup()) {
            // Mostra após 3 segundos
            setTimeout(showIosPopup, 3000);
        }
    });
    
    // Log para debug
    if (isIOS()) {
        console.log('🍎 Dispositivo iOS detectado');
        console.log('📱 Safari:', isSafari());
        console.log('📲 Standalone:', isStandalone());
        console.log('🔔 Deve mostrar popup:', shouldShowIosPopup());
    }
})();
</script>

<script>
// ========== SISTEMA DE NAVEGAÇÃO INTERNA + CONFIRMAÇÃO DE SAÍDA ==========
(function() {
    let backPressCount = 0;
    let backPressTimer = null;
    const exitToast = document.getElementById('exitToast');
    let currentView = 'home'; // home, category, product
    
    // Detecta se está em modo PWA
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                         window.navigator.standalone === true;
    
    // Função para verificar se está na tela principal
    function isOnHomeView() {
        const viewRoot = document.getElementById('view-root');
        const viewCategory = document.getElementById('view-category');
        const productModal = document.getElementById('productDetailsModal');
        
        const isHomeVisible = viewRoot && viewRoot.style.display !== 'none';
        const isCategoryHidden = !viewCategory || viewCategory.style.display === 'none';
        const isModalClosed = !productModal || !productModal.classList.contains('show');
        
        return isHomeVisible && isCategoryHidden && isModalClosed;
    }
    
    // Intercepta abertura de categoria
    const originalOpenCategory = window.openCategory;
    if (originalOpenCategory) {
        window.openCategory = function(catId) {
            originalOpenCategory(catId);
            currentView = 'category';
            if (isStandalone) {
                history.pushState({view: 'category', catId: catId}, '');
            }
        };
    }
    
    // Intercepta reset de navegação
    const originalResetNavigation = window.resetNavigation;
    if (originalResetNavigation) {
        window.resetNavigation = function() {
            originalResetNavigation();
            currentView = 'home';
            backPressCount = 0; // Reseta contador ao voltar pra home
            if (exitToast) exitToast.classList.remove('show');
        };
    }
    
    // Sistema de navegação com botão voltar
    if (isStandalone) {
        console.log('📱 PWA detectado - Navegação interna ativada');
        
        // Adiciona estado inicial
        history.pushState({view: 'home'}, '');
        
        window.addEventListener('popstate', function(event) {
            event.preventDefault();
            
            // Se NÃO está na home, volta para navegação interna
            if (!isOnHomeView()) {
                console.log('↩️ Voltando na navegação interna...');
                
                // Fecha modal de produto se estiver aberto
                const productModal = document.getElementById('productDetailsModal');
                if (productModal && productModal.classList.contains('show')) {
                    if (typeof closeProductDetails === 'function') {
                        closeProductDetails();
                    }
                    history.pushState({view: currentView}, '');
                    return;
                }
                
                // Volta pra home se está em categoria
                const viewCategory = document.getElementById('view-category');
                if (viewCategory && viewCategory.style.display !== 'none') {
                    if (typeof resetNavigation === 'function') {
                        resetNavigation();
                    }
                    history.pushState({view: 'home'}, '');
                    return;
                }
            }
            
            // Se JÁ está na home, sistema de confirmação de saída
            backPressCount++;
            
            if (backPressCount === 1) {
                // Primeira vez - mostra toast
                if (exitToast) {
                    exitToast.classList.add('show');
                    
                    // Vibração
                    if ('vibrate' in navigator) {
                        navigator.vibrate(50);
                    }
                }
                
                // Reseta após 2 segundos
                clearTimeout(backPressTimer);
                backPressTimer = setTimeout(() => {
                    backPressCount = 0;
                    if (exitToast) exitToast.classList.remove('show');
                }, 2000);
                
                // Adiciona ao histórico para não sair
                history.pushState({view: 'home'}, '');
                
            } else if (backPressCount >= 2) {
                // Segunda vez - sai do app
                console.log('✅ Saindo do app...');
                clearTimeout(backPressTimer);
                if (exitToast) exitToast.classList.remove('show');
                
                // Fecha o app
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.close();
                }
            }
        });
    } else {
        console.log('🌐 Modo navegador - Navegação padrão');
    }
})();
</script>
</body>
</html>