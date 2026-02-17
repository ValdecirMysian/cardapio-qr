<?php
/**
 * PAINEL DE GERENCIAMENTO - CARDÁPIO QR
 * Sistema Mediz Digital
 * 
 * Versão corrigida com:
 * - Tratamento de erros robusto
 * - Verificação de farmácia não encontrada
 * - Campos EAN e SKU para integração com ERP
 * - Estrutura de POST unificada
 * - Logs de integração no menu
 */

ini_set('display_errors', 0); // Desativar em produção
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

// ═══════════════════════════════════════════════════════════
// VERIFICAÇÕES INICIAIS
// ═══════════════════════════════════════════════════════════

// Verificar se o usuário está logado
verificarLogin();

// Obter farmácia do usuário logado
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);

// Verificação crítica: farmácia deve existir
if (!$farmacia) {
    session_destroy();
    header("Location: ../login.php?msg=erro_critico_conta");
    exit("Erro crítico: Conta não encontrada ou erro de sistema. Por favor, faça login novamente.");
}

// Imagem de capa
$imagem_capa_atual = isset($farmacia['imagem_capa']) ? $farmacia['imagem_capa'] : null;
if (empty($imagem_capa_atual)) {
    $g = glob('uploads/capa_farmacia_' . $farmacia['id'] . '.*');
    if (!empty($g)) { $imagem_capa_atual = $g[0]; }
}

// Obter categorias e tamanhos
$categorias = obterCategorias($pdo);
$tamanhos_disponiveis = obterTamanhos($pdo);

// Variáveis de mensagem
$erro_msg = null;
$erro_farmacia = null;

// ═══════════════════════════════════════════════════════════
// PROCESSAMENTO DE FORMULÁRIOS (POST)
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    
    $acao = $_POST['acao'];
    
    try {
        
        // ─────────────────────────────────────────────────────
        // AÇÃO: ADICIONAR PRODUTO
        // ─────────────────────────────────────────────────────
        if ($acao == 'adicionar_produto') {
            
            $nome = trim($_POST['nome']);
            $categoria_id = $_POST['categoria'];
            $preco = $_POST['preco'];
            $descricao = $_POST['descricao'] ?? '';
            
            // Campos para integração com ERP
            $ean = !empty($_POST['ean']) ? trim($_POST['ean']) : null;
            $sku_externo = !empty($_POST['sku_externo']) ? trim($_POST['sku_externo']) : null;
            
            // Campos ANVISA para medicamentos
            $principio_ativo = $_POST['principio_ativo'] ?? '';
            $registro_ms = $_POST['registro_ms'] ?? '';
            $indicacao = $_POST['indicacao'] ?? '';
            $contra_indicacao = $_POST['contra_indicacao'] ?? '';
            $tarja = $_POST['tarja'] ?? 'sem_tarja';
            $exige_receita = isset($_POST['exige_receita']) ? 1 : 0;
            $mostrar_no_cardapio = isset($_POST['mostrar_no_cardapio']) ? 1 : 0;
            $is_leite = isset($_POST['is_leite']) ? 1 : 0;
            $tem_tamanhos = isset($_POST['tem_tamanhos']) ? 1 : 0;
            
            // Validações específicas para ANVISA
            if ($tarja == 'vermelha' || $tarja == 'preta') {
                $exige_receita = 1;
            }
            
            if ($tarja == 'preta') {
                $mostrar_no_cardapio = 0;
            }
            
            // Valida campos de medicamento
            $erros = validarCamposMedicamento([
                'tarja' => $tarja,
                'principio_ativo' => $principio_ativo,
                'exige_receita' => $exige_receita,
                'mostrar_no_cardapio' => $mostrar_no_cardapio
            ]);

            // Ignora erros de validação ANVISA se for Sem Tarja
            if ($tarja == 'sem_tarja') {
                $erros = [];
            }
            
            if (!empty($erros)) {
                $erro_msg = implode('<br>', $erros);
            } else {
                // Demais campos
                $promocao = isset($_POST['promocao']) ? 1 : 0;
                $destaque = isset($_POST['destaque']) ? 1 : 0;
                $estoque = isset($_POST['estoque_disponivel']) ? 1 : 0;
                
                // Upload de imagem
                $imagem = null;
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                    $imagem = fazerUploadImagem($_FILES['imagem']);
                }
                
                try {
                    // Inserir produto com EAN e SKU
                    $stmt = $pdo->prepare("
                        INSERT INTO produtos 
                        (farmacia_id, categoria_id, nome, descricao, principio_ativo, registro_ms, indicacao, contra_indicacao, 
                        tarja, exige_receita, mostrar_no_cardapio, preco, imagem, promocao, destaque, estoque_disponivel, 
                        is_leite, tem_tamanhos, ean, sku_externo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $farmacia['id'], $categoria_id, $nome, $descricao, $principio_ativo, $registro_ms, $indicacao, 
                        $contra_indicacao, $tarja, $exige_receita, $mostrar_no_cardapio, 
                        $preco, $imagem, $promocao, $destaque, $estoque, $is_leite, $tem_tamanhos, $ean, $sku_externo
                    ]);
                    
                    $produto_id = $pdo->lastInsertId();
                    
                    // Salvar tamanhos se foram selecionados
                    if ($tem_tamanhos && isset($_POST['tamanhos_selecionados'])) {
                        $tamanhos_selecionados = $_POST['tamanhos_selecionados'];
                        $precos_adicionais = $_POST['precos_adicionais'] ?? [];
                        salvarTamanhosProduto($pdo, $produto_id, $tamanhos_selecionados, $precos_adicionais);
                    }
                    
                    header("Location: index.php?msg=produto_adicionado");
                    exit();
                    
                } catch (PDOException $e) {
                    error_log("Erro ao adicionar produto: " . $e->getMessage());
                    if ($imagem && file_exists($imagem)) {
                        @unlink($imagem);
                    }
                    $erro_msg = "Erro ao adicionar produto. Tente novamente.";
                }
            }
        }
        
        // ─────────────────────────────────────────────────────
        // AÇÃO: ATUALIZAR FARMÁCIA
        // ─────────────────────────────────────────────────────
        elseif ($acao == 'atualizar_farmacia') {
            
            $nome = trim($_POST['nome_farmacia']);
            $endereco = $_POST['endereco'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $whatsapp = $_POST['whatsapp'] ?? '';
            $valor_entrega_gratis = !empty($_POST['valor_entrega_gratis']) ? $_POST['valor_entrega_gratis'] : null;
            $taxa_entrega = !empty($_POST['taxa_entrega']) ? $_POST['taxa_entrega'] : null;
            
            // Campos do Pixel FarmaPro
            $pixel_id = isset($_POST['pixel_id']) ? trim($_POST['pixel_id']) : '';
            if (empty($pixel_id)) { $pixel_id = null; }
            $pixel_ativo = isset($_POST['pixel_ativo']) ? 1 : 0;
            
            if (empty($nome)) {
                $erro_farmacia = "O nome da farmácia é obrigatório";
            } else {
                // Processar imagem de capa
                $imagem_capa_nova = null;
                if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == 0) {
                    $imagem_capa_nova = fazerUploadImagem($_FILES['imagem_capa']);
                }
                $remover_imagem_capa = isset($_POST['remover_imagem_capa']);

                $imagem_capa_final = $farmacia['imagem_capa'] ?? null;
                $capa_canon = null;
                
                if (!empty($imagem_capa_nova)) {
                    $ext = pathinfo($imagem_capa_nova, PATHINFO_EXTENSION);
                    $capa_canon = 'uploads/capa_farmacia_' . $farmacia['id'] . '.' . $ext;
                    if (file_exists($capa_canon)) { @unlink($capa_canon); }
                    if (!@rename($imagem_capa_nova, $capa_canon)) { 
                        @copy($imagem_capa_nova, $capa_canon); 
                        @unlink($imagem_capa_nova); 
                    }
                    $imagem_capa_final = $capa_canon;
                }
                
                if ($remover_imagem_capa) {
                    if (!empty($farmacia['imagem_capa']) && file_exists($farmacia['imagem_capa'])) { 
                        excluirImagem($farmacia['imagem_capa']); 
                    }
                    $gdel = glob('uploads/capa_farmacia_' . $farmacia['id'] . '.*');
                    if (!empty($gdel)) { 
                        foreach ($gdel as $p) { @unlink($p); } 
                    }
                    $imagem_capa_final = null;
                } elseif (!empty($capa_canon)) {
                    if (!empty($farmacia['imagem_capa']) && file_exists($farmacia['imagem_capa'])) { 
                        excluirImagem($farmacia['imagem_capa']); 
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // PROCESSAR LOGO (com geração automática de ícones PWA)
                // ═══════════════════════════════════════════════════════════
                $logo_nova = null;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $logo_nova = fazerUploadImagem($_FILES['logo']);
                }
                $remover_logo = isset($_POST['remover_logo']);
                
                $logo_final = $farmacia['logo'] ?? null;
                $logo_canon = null;
                
                if (!empty($logo_nova)) {
                    $ext = pathinfo($logo_nova, PATHINFO_EXTENSION);
                    $logo_canon = 'uploads/logo_farmacia_' . $farmacia['id'] . '.' . $ext;
                    if (file_exists($logo_canon)) { @unlink($logo_canon); }
                    if (!@rename($logo_nova, $logo_canon)) { 
                        @copy($logo_nova, $logo_canon); 
                        @unlink($logo_nova); 
                    }
                    $logo_final = $logo_canon;
                    
                    // GERAR ÍCONES PWA AUTOMATICAMENTE
                    try {
                        require_once 'icon-generator.php';
                        $icon_dir = 'icons/farmacia_' . $farmacia['id'];
                        if (!is_dir($icon_dir)) {
                            @mkdir($icon_dir, 0755, true);
                        }
                        
                        // Gerar ícones em todos os tamanhos necessários
                        $icon_sizes = [72, 96, 128, 144, 152, 192, 384, 512];
                        foreach ($icon_sizes as $size) {
                            gerarIconePWA($logo_canon, $icon_dir . '/icon-' . $size . 'x' . $size . '.png', $size);
                        }
                    } catch (Exception $e) {
                        // Falha silenciosa - ícones continuam usando padrão Mediz
                        error_log("Erro ao gerar ícones PWA: " . $e->getMessage());
                    }
                }
                
                if ($remover_logo) {
                    if (!empty($farmacia['logo']) && file_exists($farmacia['logo'])) { 
                        excluirImagem($farmacia['logo']); 
                    }
                    $gdel = glob('uploads/logo_farmacia_' . $farmacia['id'] . '.*');
                    if (!empty($gdel)) { 
                        foreach ($gdel as $p) { @unlink($p); } 
                    }
                    // Remover pasta de ícones personalizados
                    $icon_dir = 'icons/farmacia_' . $farmacia['id'];
                    if (is_dir($icon_dir)) {
                        $files = glob($icon_dir . '/*');
                        foreach ($files as $file) { if(is_file($file)) @unlink($file); }
                        @rmdir($icon_dir);
                    }
                    $logo_final = null;
                } elseif (!empty($logo_canon)) {
                    if (!empty($farmacia['logo']) && file_exists($farmacia['logo'])) { 
                        excluirImagem($farmacia['logo']); 
                    }
                }

                // Verificar colunas existentes no banco
                $cols = [];
                try {
                    $cst = $pdo->query("SHOW COLUMNS FROM farmacias");
                    if ($cst) {
                        foreach ($cst->fetchAll(PDO::FETCH_COLUMN) as $c) { $cols[$c] = true; }
                    }
                } catch (Exception $e) {}

                try {
                    $sql = "UPDATE farmacias SET nome = ?, endereco = ?, telefone = ?, whatsapp = ?";
                    $params = [$nome, $endereco, $telefone, $whatsapp];

                    if (isset($cols['valor_entrega_gratis'])) { $sql .= ", valor_entrega_gratis = ?"; $params[] = $valor_entrega_gratis; }
                    if (isset($cols['taxa_entrega'])) { $sql .= ", taxa_entrega = ?"; $params[] = $taxa_entrega; }
                    if (isset($cols['pixel_id'])) { $sql .= ", pixel_id = ?"; $params[] = $pixel_id; }
                    if (isset($cols['pixel_ativo'])) { $sql .= ", pixel_ativo = ?"; $params[] = $pixel_ativo; }
                    if (isset($cols['imagem_capa'])) { $sql .= ", imagem_capa = ?"; $params[] = $imagem_capa_final; }
                    if (isset($cols['logo'])) { $sql .= ", logo = ?"; $params[] = $logo_final; }
                    
                    // Campos Evolution API
                    if (isset($cols['usar_evolution_api'])) { 
                        $usar_evolution = isset($_POST['usar_evolution_api']) ? 1 : 0;
                        $sql .= ", usar_evolution_api = ?"; 
                        $params[] = $usar_evolution; 
                    }
                    if (isset($cols['evolution_instance_name'])) { 
                        $sql .= ", evolution_instance_name = ?"; 
                        $params[] = !empty($_POST['evolution_instance_name']) ? $_POST['evolution_instance_name'] : null; 
                    }
                    if (isset($cols['evolution_api_key'])) { 
                        $sql .= ", evolution_api_key = ?"; 
                        $params[] = !empty($_POST['evolution_api_key']) ? $_POST['evolution_api_key'] : null; 
                    }
                    if (isset($cols['evolution_api_url'])) { 
                        $sql .= ", evolution_api_url = ?"; 
                        $params[] = !empty($_POST['evolution_api_url']) ? $_POST['evolution_api_url'] : null; 
                    }
                    if (isset($cols['feed_apenas_promocao'])) { 
                        $feed_promocao = isset($_POST['feed_apenas_promocao']) ? 1 : 0;
                        $sql .= ", feed_apenas_promocao = ?"; 
                        $params[] = $feed_promocao; 
                    }

                    $sql .= " WHERE id = ? AND usuario_id = ?";
                    $params[] = $farmacia['id'];
                    $params[] = $_SESSION['user_id'];

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    header("Location: index.php?msg=farmacia_atualizada");
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == '23000') {
                        if (strpos($e->getMessage(), 'pixel_id') !== false) {
                            $erro_farmacia = "Erro: Este Pixel ID já está em uso por outra farmácia.";
                        } else {
                            $erro_farmacia = "Erro: Dados duplicados no sistema.";
                        }
                    } else {
                        error_log("Erro ao atualizar farmácia: " . $e->getMessage());
                        $erro_farmacia = "Ocorreu um erro ao salvar as configurações. Tente novamente.";
                    }
                }
            }
        }
        
    } catch (Throwable $e) {
        error_log("ERRO FATAL NO POST: " . $e->getMessage());
        $erro_msg = "Erro interno no servidor. Tente novamente.";
    }
}

// ═══════════════════════════════════════════════════════════
// CARREGAMENTO DE DADOS PARA EXIBIÇÃO
// ═══════════════════════════════════════════════════════════

// Obter produtos da farmácia
$produtos = obterProdutos($pdo, $farmacia['id']);

// Agrupar produtos por categoria
$produtos_por_categoria = agruparProdutosPorCategoria($produtos);

// URL do QR Code
$qr_code_url = gerarUrlQrCode($farmacia['qr_code_token']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Gerenciador de Cardápio - <?php echo htmlspecialchars($farmacia['nome']); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/favicons.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7B68EE;
            --primary-dark: #6A5ACD;
            --light: #F8F9FA;
            --dark: #212529;
            --success: #28A745;
            --danger: #DC3545;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--dark);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        .navbar {
            background-color: var(--primary);
        }
        
        .navbar-brand, .nav-link {
            color: white !important;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .produto-card {
            transition: transform 0.3s;
            height: 100%;
        }
        
        .produto-card:hover {
            transform: translateY(-5px);
        }
        
        .produto-img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        
        .qr-code-container {
            text-align: center;
            padding: 20px;
        }
        
        .footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .tarja-badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: white;
        }
        
        .tarja-sem { background-color: #6c757d; }
        .tarja-amarela { background-color: #ffc107; color: black; }
        .tarja-vermelha { background-color: #dc3545; }
        .tarja-preta { background-color: #343a40; }
        .badge-leite { background-color: #17a2b8; color: white; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-qrcode me-2"></i>
                Cardápio QR - <?php echo htmlspecialchars($farmacia['nome']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="estoque_rapido.php">
                            <i class="fas fa-bolt me-1"></i> Estoque Rápido
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs_integracao.php">
                            <i class="fas fa-history me-1"></i> Logs API
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="importar_csv.php">
                            <i class="fas fa-file-import me-1"></i> Importar CSV
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="revisao_produtos.php">
                            <i class="fas fa-check-double me-1"></i> Revisão IA
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="buscar_imagens">
                            <i class="fas fa-camera me-1"></i> Gestor Imagens
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#configFarmaciaModal">
                            <i class="fas fa-cog me-1"></i> Configurações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mensagens de alerta -->
    <?php if (isset($_GET['msg']) || isset($erro_msg) || isset($erro_farmacia)): ?>
    <div class="alert-message">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'produto_adicionado'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> Produto adicionado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'produto_excluido'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> Produto excluído com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'produto_editado'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> Produto atualizado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'farmacia_atualizada'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> Informações da farmácia atualizadas com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (isset($erro_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $erro_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (isset($erro_farmacia)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $erro_farmacia; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Conteúdo principal -->
    <div class="container mt-4">
        
        <!-- QR Code -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i> Seu QR Code</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6 qr-code-container">
                        <img src="<?php echo gerarQrCodeUrl($qr_code_url); ?>" 
                             alt="QR Code" class="img-fluid" id="qr-code-image">
                        <div id="qr-code-loading" class="text-center mt-2">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando QR Code...</p>
                        </div>
                        <div id="qr-code-error" class="alert alert-danger mt-2 d-none">
                            <i class="fas fa-exclamation-circle"></i> Erro ao carregar o QR Code. 
                            <button class="btn btn-sm btn-outline-danger ms-2" onclick="recarregarQR()">Tentar Novamente</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Instruções:</h5>
                        <p>1. Este é o QR Code que dá acesso ao cardápio da sua farmácia.</p>
                        <p>2. Baixe este código e imprima para colocar em sua loja.</p>
                        <p>3. Os clientes podem escanear este código para ver todos os seus produtos.</p>
                        <p>4. A URL do seu cardápio é: <br>
                           <a href="<?php echo $qr_code_url; ?>" target="_blank"><?php echo $qr_code_url; ?></a>
                        </p>
                        <a href="<?php echo $qr_code_url; ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i> Ver Cardápio
                        </a>
                        <a href="download_qr.php?token=<?php echo $farmacia['qr_code_token']; ?>" class="btn btn-success ms-2">
                            <i class="fas fa-download me-1"></i> Baixar QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Banner Estoque Rápido -->
        <div class="card mb-4 bg-primary text-white border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="fas fa-bolt me-2"></i> Gerenciamento Rápido de Estoque</h5>
                    <p class="mb-0 small">Edite preços e disponibilidade de forma ágil.</p>
                </div>
                <a href="estoque_rapido.php" class="btn btn-light text-primary fw-bold">
                    Acessar Agora <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Adição de produtos -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Adicionar Novo Produto</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="adicionar_produto">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="categoria" class="form-label">Categoria *</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <?php 
                                // Carrega categorias do banco se houver, senão usa do JSON (fallback)
                                if (!empty($categorias)) {
                                    foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                    <?php endforeach; 
                                } else {
                                    // Fallback simples se tabela categorias estiver vazia
                                    echo '<option value="1">Geral</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="preco" class="form-label">Preço (R$) *</label>
                            <input type="number" step="0.01" class="form-control" id="preco" name="preco" required>
                        </div>
                    </div>
                    
                    <!-- Campos EAN e SKU para integração -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ean" class="form-label">
                                <i class="fas fa-barcode me-1"></i> Código de Barras (EAN)
                            </label>
                            <input type="text" class="form-control" id="ean" name="ean" placeholder="Ex: 7891234567890">
                            <div class="form-text">Para integração automática com seu sistema de caixa/ERP</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sku_externo" class="form-label">
                                <i class="fas fa-hashtag me-1"></i> Código Interno (SKU)
                            </label>
                            <input type="text" class="form-control" id="sku_externo" name="sku_externo" placeholder="Ex: PROD-001">
                            <div class="form-text">Código do produto no seu sistema de gestão</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                    </div>
                    
                    <!-- Campos ANVISA para medicamentos -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-prescription-bottle-alt me-2"></i> Informações de Medicamento (ANVISA)</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Os campos abaixo só são necessários para produtos classificados como medicamentos.
                            </div>
                            
                            <div class="mb-3">
                                <label for="principio_ativo" class="form-label">Princípio Ativo</label>
                                <input type="text" class="form-control" id="principio_ativo" name="principio_ativo">
                            </div>

                            <div class="mb-3">
                                <label for="registro_ms" class="form-label">Registro MS (ANVISA)</label>
                                <input type="text" class="form-control" id="registro_ms" name="registro_ms" placeholder="Ex: 1.0000.0000.000-0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="indicacao" class="form-label">Indicação</label>
                                <textarea class="form-control" id="indicacao" name="indicacao" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contra_indicacao" class="form-label">Contraindicação</label>
                                <textarea class="form-control" id="contra_indicacao" name="contra_indicacao" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tarja" class="form-label">Tarja do Medicamento</label>
                                <select class="form-select" id="tarja" name="tarja">
                                    <option value="sem_tarja">Sem Tarja (Medicamento de Venda Livre)</option>
                                    <option value="amarela">Tarja Amarela (Genéricos)</option>
                                    <option value="vermelha">Tarja Vermelha (Venda sob Prescrição)</option>
                                    <option value="preta">Tarja Preta (Controlados)</option>
                                </select>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="exige_receita" name="exige_receita">
                                <label class="form-check-label" for="exige_receita">
                                    Exige Receita Médica
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mostrar_no_cardapio" name="mostrar_no_cardapio" checked>
                                <label class="form-check-label" for="mostrar_no_cardapio">
                                    Mostrar no Cardápio Público
                                </label>
                                <div class="form-text text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> Atenção: Segundo a ANVISA, medicamentos tarjados não podem ser promovidos publicamente.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo para marcar produto como leite -->
                    <div class="card mb-4 border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-baby-bottle me-2"></i> Informações de Leites e Fórmulas</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_leite" name="is_leite">
                                <label class="form-check-label" for="is_leite">
                                    <i class="fas fa-check-circle me-1"></i> Este produto é um leite especial ou fórmula infantil
                                </label>
                                <div class="form-text">
                                    Marque esta opção para produtos como leites especiais, fórmulas infantis ou substitutos de leite.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo para configurar tamanhos do produto -->
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-ruler me-2"></i> Configuração de Tamanhos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="tem_tamanhos" name="tem_tamanhos">
                                <label class="form-check-label" for="tem_tamanhos">
                                    <i class="fas fa-check-circle me-1"></i> Este produto possui diferentes tamanhos
                                </label>
                                <div class="form-text">
                                    Marque esta opção para produtos como fraldas, roupas, ou qualquer item que tenha variações de tamanho.
                                </div>
                            </div>
                            
                            <div id="tamanhos_container" style="display: none;">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-list me-1"></i> Selecione os tamanhos disponíveis:
                                </h6>
                                <div class="row">
                                    <?php foreach ($tamanhos_disponiveis as $tamanho): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-light">
                                            <div class="card-body py-2">
                                                <div class="form-check">
                                                    <input class="form-check-input tamanho-checkbox" 
                                                           type="checkbox" 
                                                           id="tamanho_<?php echo $tamanho['id']; ?>" 
                                                           name="tamanhos_selecionados[]" 
                                                           value="<?php echo $tamanho['id']; ?>">
                                                    <label class="form-check-label fw-bold" for="tamanho_<?php echo $tamanho['id']; ?>">
                                                        <?php echo htmlspecialchars($tamanho['nome']); ?>
                                                    </label>
                                                    <?php if (!empty($tamanho['descricao'])): ?>
                                                    <div class="small text-muted">
                                                        <?php echo htmlspecialchars($tamanho['descricao']); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-2 tamanho-preco" style="display: none;">
                                                    <label class="form-label small">Preço adicional (R$)</label>
                                                    <input type="number" 
                                                           step="0.01" 
                                                           class="form-control form-control-sm" 
                                                           name="precos_adicionais[<?php echo $tamanho['id']; ?>]" 
                                                           placeholder="0.00">
                                                    <div class="form-text">Deixe 0 para usar o preço base</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="imagem" class="form-label">Imagem do Produto</label>
                        <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promocao" name="promocao">
                                <label class="form-check-label" for="promocao">
                                    Em promoção
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="destaque" name="destaque">
                                <label class="form-check-label" for="destaque">
                                    Produto em destaque
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="estoque_disponivel" name="estoque_disponivel" checked>
                                <label class="form-check-label" for="estoque_disponivel">
                                    Disponível em estoque
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Adicionar Produto
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Lista de produtos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Seus Produtos</h5>
            </div>
            <div class="card-body">
                <?php if (empty($produtos)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Você ainda não adicionou nenhum produto.
                </div>
                <?php else: ?>
                    <?php foreach ($produtos_por_categoria as $categoria => $prods): ?>
                    <h4 class="mt-4 mb-3"><?php echo htmlspecialchars($categoria); ?></h4>
                    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                        <?php foreach ($prods as $produto): ?>
                        <div class="col">
                            <div class="card h-100 produto-card">
                                <?php if (!empty($produto['imagem']) && file_exists($produto['imagem'])): ?>
                                <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" class="card-img-top produto-img" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                <?php else: ?>
                                <div class="card-img-top produto-img d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-image fa-4x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($produto['nome']); ?>
                                        <?php if ($produto['tarja'] != 'sem_tarja'): ?>
                                            <span class="tarja-badge tarja-<?php echo $produto['tarja']; ?>">
                                                <?php 
                                                    switch($produto['tarja']) {
                                                        case 'amarela': echo 'Genérico'; break;
                                                        case 'vermelha': echo 'Tarja Vermelha'; break;
                                                        case 'preta': echo 'Tarja Preta'; break;
                                                    }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($produto['is_leite']) && $produto['is_leite']): ?>
                                            <span class="badge badge-leite rounded-pill ms-1">
                                                <i class="fas fa-baby-bottle me-1"></i> Leite
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($produto['tem_tamanhos']) && $produto['tem_tamanhos']): ?>
                                            <span class="badge bg-success rounded-pill ms-1">
                                                <i class="fas fa-ruler me-1"></i> Tamanhos
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    
                                    <?php if (!empty($produto['ean'])): ?>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-barcode me-1"></i> EAN: <?php echo htmlspecialchars($produto['ean']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="card-text text-muted small">
                                        <?php echo !empty($produto['descricao']) ? htmlspecialchars(substr($produto['descricao'], 0, 80)) . '...' : 'Sem descrição'; ?>
                                    </p>
                                    
                                    <?php if (!empty($produto['principio_ativo'])): ?>
                                    <p class="card-text small">
                                        <strong>Princípio Ativo:</strong> <?php echo htmlspecialchars($produto['principio_ativo']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="card-text fw-bold text-primary">
                                        R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                    </p>
                                    
                                    <?php if ($produto['promocao']): ?>
                                    <span class="badge bg-danger">Promoção</span>
                                    <?php endif; ?>
                                    <?php if ($produto['destaque']): ?>
                                    <span class="badge bg-warning text-dark">Destaque</span>
                                    <?php endif; ?>
                                    <?php if (!$produto['estoque_disponivel']): ?>
                                    <span class="badge bg-secondary">Indisponível</span>
                                    <?php endif; ?>
                                    <?php if ($produto['exige_receita']): ?>
                                    <span class="badge bg-info">Exige Receita</span>
                                    <?php endif; ?>
                                    <?php if (!$produto['mostrar_no_cardapio']): ?>
                                    <span class="badge bg-dark">Oculto</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent border-top-0">
                                    <a href="editar_produto.php?id=<?php echo $produto['id']; ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="excluir_produto.php?id=<?php echo $produto['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de Configurações da Farmácia -->
    <div class="modal fade" id="configFarmaciaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-cog me-2"></i> Configurações da Farmácia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="atualizar_farmacia">
                    <div class="modal-body">
                        
                        <!-- Dados Básicos -->
                        <h6 class="text-primary mb-3"><i class="fas fa-store me-2"></i> Dados da Loja</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nome_farmacia" class="form-label">Nome da Farmácia *</label>
                                <input type="text" class="form-control" id="modal_nome_farmacia" name="nome_farmacia" value="<?php echo htmlspecialchars($farmacia['nome']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_endereco" class="form-label">Endereço</label>
                                <input type="text" class="form-control" id="modal_endereco" name="endereco" value="<?php echo htmlspecialchars($farmacia['endereco'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="modal_telefone" name="telefone" value="<?php echo htmlspecialchars($farmacia['telefone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_whatsapp" class="form-label">WhatsApp para Pedidos</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                    <input type="text" class="form-control" id="modal_whatsapp" name="whatsapp" placeholder="Ex: (11) 98765-4321" value="<?php echo htmlspecialchars($farmacia['whatsapp'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_imagem_capa" class="form-label">Imagem de Capa</label>
                            <input type="file" class="form-control" id="modal_imagem_capa" name="imagem_capa" accept="image/*">
                            <?php if (!empty($imagem_capa_atual) && file_exists($imagem_capa_atual)): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($imagem_capa_atual); ?>" alt="Capa atual" style="max-width: 200px; border-radius: 8px;">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="modal_remover_imagem_capa" name="remover_imagem_capa">
                                    <label class="form-check-label" for="modal_remover_imagem_capa">Remover imagem</label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="modal_logo" class="form-label">
                                <i class="fas fa-mobile-alt me-2"></i>Logo do App (PWA)
                                <span class="badge bg-success ms-2">NOVO</span>
                            </label>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Esta logo aparecerá como ícone quando seus clientes instalarem o app no celular. 
                                <strong>Tamanho ideal: 512x512 pixels</strong>
                            </p>
                            <input type="file" class="form-control" id="modal_logo" name="logo" accept="image/*">
                            <?php if (!empty($farmacia['logo']) && file_exists($farmacia['logo'])): ?>
                            <div class="mt-3">
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                                    <img src="<?php echo htmlspecialchars($farmacia['logo']); ?>" alt="Logo atual" style="width: 80px; height: 80px; object-fit: contain; border-radius: 12px; border: 2px solid #dee2e6;">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-success mb-1">
                                            <i class="fas fa-check-circle me-1"></i>Logo personalizada ativa
                                        </div>
                                        <small class="text-muted">Clientes verão este ícone ao instalar o app</small>
                                    </div>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="modal_remover_logo" name="remover_logo">
                                    <label class="form-check-label text-danger" for="modal_remover_logo">
                                        <i class="fas fa-trash me-1"></i>Remover logo personalizada (volta para logo Mediz)
                                    </label>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Sem logo personalizada.</strong> Atualmente o app usa a logo padrão Mediz. 
                                Faça upload da sua logo para personalizar!
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Configurações de Entrega -->
                        <h6 class="text-success mb-3"><i class="fas fa-truck me-2"></i> Entrega</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_valor_entrega_gratis" class="form-label">Valor Mínimo p/ Entrega Grátis</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" id="modal_valor_entrega_gratis" name="valor_entrega_gratis" 
                                           value="<?php echo $farmacia['valor_entrega_gratis'] ?? ''; ?>" placeholder="50.00">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_taxa_entrega" class="form-label">Taxa de Entrega</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" id="modal_taxa_entrega" name="taxa_entrega" 
                                           value="<?php echo $farmacia['taxa_entrega'] ?? ''; ?>" placeholder="5.00">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Integração API -->
                        <h6 class="text-secondary mb-3"><i class="fas fa-database me-2"></i> Integração com ERP</h6>
                        <div class="alert alert-light border">
                            <p class="mb-2"><strong>Token de Autenticação:</strong></p>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="apiTokenModal" value="<?php echo htmlspecialchars($farmacia['qr_code_token']); ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('apiTokenModal').value); alert('Token copiado!');">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <a href="logs_integracao.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-history me-1"></i> Ver Logs
                                </a>
                                <a href="teste_producao.html" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-vial me-1"></i> Testar API
                                </a>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Pixel FarmaPro -->
                        <h6 class="text-info mb-3"><i class="fas fa-chart-line me-2"></i> Pixel FarmaPro</h6>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="modal_pixel_ativo" name="pixel_ativo" 
                                   <?php echo (!empty($farmacia['pixel_ativo']) && $farmacia['pixel_ativo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="modal_pixel_ativo">Ativar rastreamento de conversões</label>
                        </div>
                        <div class="mb-3">
                            <label for="modal_pixel_id" class="form-label">Pixel ID</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-fingerprint"></i></span>
                                <input type="text" class="form-control" id="modal_pixel_id" name="pixel_id" 
                                       value="<?php echo htmlspecialchars($farmacia['pixel_id'] ?? ''); ?>" 
                                       placeholder="Ex: FP-B7A273E5F825">
                            </div>
                        </div>

                        <hr>

                        <!-- Evolution API (WhatsApp Automático) -->
                        <h6 class="text-primary mb-3"><i class="fas fa-robot me-2"></i> Evolution API (Automação)</h6>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="modal_usar_evolution" name="usar_evolution_api" 
                                   <?php echo (!empty($farmacia['usar_evolution_api']) && $farmacia['usar_evolution_api']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="modal_usar_evolution">Enviar pedidos automaticamente (API)</label>
                        </div>
                        
                        <div id="evolution_fields" style="display: <?php echo (!empty($farmacia['usar_evolution_api']) && $farmacia['usar_evolution_api']) ? 'block' : 'none'; ?>;">
                            <div class="mb-3">
                                <label class="form-label">URL da API</label>
                                <input type="url" class="form-control" name="evolution_api_url" 
                                       value="<?php echo htmlspecialchars($farmacia['evolution_api_url'] ?? 'https://evolution.probotfarmapro.online'); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome da Instância</label>
                                    <input type="text" class="form-control" name="evolution_instance_name" 
                                           value="<?php echo htmlspecialchars($farmacia['evolution_instance_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="evolution_api_key" 
                                           value="<?php echo htmlspecialchars($farmacia['evolution_api_key'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Integração Meta (Catálogo XML) -->
                        <h6 class="text-info mb-3"><i class="fab fa-facebook me-2"></i> Catálogo WhatsApp/Instagram</h6>
                        <div class="alert alert-light border">
                            <p class="mb-2 small">Link do Feed XML para o Gerenciador de Comércio:</p>
                            
                            <?php 
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                            $feedUrl = "$protocol://$host$uri/feed_meta.php?token=" . $farmacia['qr_code_token'] . "&ext=.xml";
                            $cleanFeedUrl = $feedUrl; // URL base para o JS
                            
                            if (isset($farmacia['feed_apenas_promocao']) && $farmacia['feed_apenas_promocao']) {
                                $feedUrl .= "&vitrine_whatsapp=1";
                            }
                            ?>
                            
                            <div class="input-group mb-2">
                                <input type="text" class="form-control font-monospace form-control-sm" id="feedUrl" value="<?php echo $feedUrl; ?>" readonly>
                                <button class="btn btn-outline-primary btn-sm" type="button" onclick="copiarFeedUrl()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkPromocao" name="feed_apenas_promocao" onchange="atualizarUrlFeed()" 
                                       <?php echo (isset($farmacia['feed_apenas_promocao']) && $farmacia['feed_apenas_promocao']) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="checkPromocao">
                                    <strong>Vitrine WhatsApp:</strong> Mostrar apenas promoções como "Em Estoque"
                                </label>
                            </div>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <p>Sistema de Cardápio QR Code para Farmácias &copy; <?php echo date('Y'); ?></p>
            <p class="small">Desenvolvido por <a href="https://mediz.digital" class="text-white">Mediz Digital</a></p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ══════════════════════════════════════════════════════
        // QR CODE
        // ══════════════════════════════════════════════════════
        const qrImage = document.getElementById('qr-code-image');
        const qrLoading = document.getElementById('qr-code-loading');
        const qrError = document.getElementById('qr-code-error');
        
        if (qrImage) {
            qrImage.style.display = 'none';
            qrLoading.style.display = 'block';
            qrError.classList.add('d-none');
            
            qrImage.onload = function() {
                qrLoading.style.display = 'none';
                qrError.classList.add('d-none');
                qrImage.style.display = 'block';
            };
            
            qrImage.onerror = function() {
                qrLoading.style.display = 'none';
                qrError.classList.remove('d-none');
                qrImage.style.display = 'none';
                setTimeout(tentarServicoAlternativo, 1000);
            };
        }
        
        // ══════════════════════════════════════════════════════
        // TAMANHOS DE PRODUTO
        // ══════════════════════════════════════════════════════
        const temTamanhosCheck = document.getElementById('tem_tamanhos');
        const tamanhosContainer = document.getElementById('tamanhos_container');
        const tamanhoCheckboxes = document.querySelectorAll('.tamanho-checkbox');
        
        if (temTamanhosCheck) {
            temTamanhosCheck.addEventListener('change', function() {
                tamanhosContainer.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    tamanhoCheckboxes.forEach(cb => {
                        cb.checked = false;
                        const precoDiv = cb.closest('.card').querySelector('.tamanho-preco');
                        if (precoDiv) precoDiv.style.display = 'none';
                    });
                }
            });
        }
        
        tamanhoCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const precoDiv = this.closest('.card').querySelector('.tamanho-preco');
                if (precoDiv) {
                    precoDiv.style.display = this.checked ? 'block' : 'none';
                    if (!this.checked) {
                        const input = precoDiv.querySelector('input');
                        if (input) input.value = '';
                    }
                }
            });
        });
        
        // ══════════════════════════════════════════════════════
        // ANVISA - CONTROLE DE TARJA
        // ══════════════════════════════════════════════════════
        const tarjaSelect = document.getElementById('tarja');
        const exigeReceitaCheck = document.getElementById('exige_receita');
        const mostrarCardapioCheck = document.getElementById('mostrar_no_cardapio');
        
        if (tarjaSelect) {
            function updateAnvisaOptions() {
                const tarjaValue = tarjaSelect.value;
                
                if (tarjaValue === 'vermelha' || tarjaValue === 'preta') {
                    exigeReceitaCheck.checked = true;
                    exigeReceitaCheck.disabled = true;
                } else {
                    exigeReceitaCheck.disabled = false;
                }
                
                if (tarjaValue === 'preta') {
                    mostrarCardapioCheck.checked = false;
                    mostrarCardapioCheck.disabled = true;
                } else if (tarjaValue === 'vermelha') {
                    mostrarCardapioCheck.disabled = false;
                } else {
                    mostrarCardapioCheck.checked = true;
                    mostrarCardapioCheck.disabled = false;
                }
            }
            
            tarjaSelect.addEventListener('change', updateAnvisaOptions);
            updateAnvisaOptions();
        }
    });

    // Funções globais para QR Code
    function recarregarQR() {
        const qrImage = document.getElementById('qr-code-image');
        const qrLoading = document.getElementById('qr-code-loading');
        const qrError = document.getElementById('qr-code-error');
        
        qrLoading.style.display = 'block';
        qrError.classList.add('d-none');
        qrImage.style.display = 'none';
        
        if (qrImage.src.includes('qrserver.com')) {
            qrImage.src = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" + 
                        encodeURIComponent('<?php echo $qr_code_url; ?>') + 
                        "&choe=UTF-8&chld=H|0&_t=" + new Date().getTime();
        } else {
            tentarServicoAlternativo();
        }
    }

    function tentarServicoAlternativo() {
        const qrImage = document.getElementById('qr-code-image');
        const qrLoading = document.getElementById('qr-code-loading');
        const qrError = document.getElementById('qr-code-error');
        
        qrLoading.style.display = 'block';
        qrError.classList.add('d-none');
        
        qrImage.src = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" + 
                    encodeURIComponent('<?php echo $qr_code_url; ?>') +
                    "&_t=" + new Date().getTime();
    }

    // Scripts para Evolution API e Meta
    document.addEventListener('DOMContentLoaded', function() {
        const evolutionCheck = document.getElementById('modal_usar_evolution');
        const evolutionFields = document.getElementById('evolution_fields');
        if(evolutionCheck){
            evolutionCheck.addEventListener('change', function(){
                evolutionFields.style.display = this.checked ? 'block' : 'none';
            });
        }
    });

    function atualizarUrlFeed() {
        const checkbox = document.getElementById('checkPromocao');
        const input = document.getElementById('feedUrl');
        const baseUrl = "<?php echo $cleanFeedUrl; ?>";
        
        if (checkbox.checked) {
            input.value = baseUrl + "&vitrine_whatsapp=1";
        } else {
            input.value = baseUrl;
        }
    }

    function copiarFeedUrl() {
        const input = document.getElementById('feedUrl');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            alert("Link copiado!");
        });
    }
    </script>

    <!-- Service Worker para PWA -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('service-worker.js')
                .then(registration => {
                    console.log('SW registrado:', registration);
                })
                .catch(error => {
                    console.log('SW falha:', error);
                });
        });
    }
    </script>
</body>
</html>