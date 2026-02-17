<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o usuário está logado
verificarLogin();

// Obter farmácia do usuário logado
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);

// Verificar se foi fornecido um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?msg=erro_id_invalido");
    exit();
}

$produto_id = $_GET['id'];

// Obter produto e verificar se pertence à farmácia do usuário
$produto = obterProduto($pdo, $produto_id, $farmacia['id']);

if (!$produto) {
    header("Location: index.php?msg=erro_produto_nao_encontrado");
    exit();
}

// Obter categorias e tamanhos
$categorias = obterCategorias($pdo);
$tamanhos_disponiveis = obterTamanhos($pdo);
$tamanhos_produto = obterTamanhosProduto($pdo, $produto_id);

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $categoria_id = $_POST['categoria'];
    $preco = $_POST['preco'];
    $descricao = $_POST['descricao'] ?? '';
    
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
        $exige_receita = 1; // Força exigir receita para tarjas vermelha e preta
    }
    
    if ($tarja == 'preta') {
        $mostrar_no_cardapio = 0; // Nunca mostra tarja preta no cardápio
    }
    
    // Valida campos de medicamento
    $erros = validarCamposMedicamento([
        'tarja' => $tarja,
        'principio_ativo' => $principio_ativo,
        'exige_receita' => $exige_receita,
        'mostrar_no_cardapio' => $mostrar_no_cardapio
    ]);
    
    $has_error = false;
    
    if (!empty($erros)) {
        $has_error = true;
        $erro_msg = implode('<br>', $erros);
    }
    
    if (!$has_error) {
        $promocao = isset($_POST['promocao']) ? 1 : 0;
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        $estoque = isset($_POST['estoque_disponivel']) ? 1 : 0;
        $ean = $_POST['ean'] ?? null;
        $sku_externo = $_POST['sku_externo'] ?? null;
        
        // Verificar se uma nova imagem foi enviada
        $imagem = $produto['imagem']; // Manter a imagem atual por padrão
        
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            // Fazer upload da nova imagem
            $nova_imagem = fazerUploadImagem($_FILES['imagem']);
            
            if ($nova_imagem) {
                // Excluir a imagem antiga se existir
                if (!empty($produto['imagem'])) {
                    excluirImagem($produto['imagem']);
                }
                $imagem = $nova_imagem;
            }
        }
        
        // Atualizar produto
        $stmt = $pdo->prepare("
            UPDATE produtos 
            SET nome = ?, categoria_id = ?, descricao = ?, principio_ativo = ?, registro_ms = ?,
                indicacao = ?, contra_indicacao = ?, tarja = ?, exige_receita = ?, 
                mostrar_no_cardapio = ?, preco = ?, imagem = ?, promocao = ?, 
                destaque = ?, estoque_disponivel = ?, is_leite = ?, tem_tamanhos = ?,
                ean = ?, sku_externo = ?
            WHERE id = ? AND farmacia_id = ?
        ");
        $stmt->execute([
            $nome, $categoria_id, $descricao, $principio_ativo, $registro_ms, $indicacao, 
            $contra_indicacao, $tarja, $exige_receita, $mostrar_no_cardapio, 
            $preco, $imagem, $promocao, $destaque, $estoque, $is_leite, $tem_tamanhos,
            $ean, $sku_externo,
            $produto_id, $farmacia['id']
        ]);
        
        // Salvar tamanhos
        if ($tem_tamanhos && isset($_POST['tamanhos_selecionados'])) {
            $tamanhos_selecionados = $_POST['tamanhos_selecionados'];
            $precos_adicionais = $_POST['precos_adicionais'] ?? [];
            salvarTamanhosProduto($pdo, $produto_id, $tamanhos_selecionados, $precos_adicionais);
        } else {
            // Se não tem tamanhos, remove todos os tamanhos existentes
            salvarTamanhosProduto($pdo, $produto_id, []);
        }
        
        // Redirecionar para a página principal
        header("Location: index.php?msg=produto_editado");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Produto - <?php echo htmlspecialchars($produto['nome']); ?></title>
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
        
        .footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .preview-img {
            max-height: 200px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .badge-leite {
            background-color: #17a2b8;
            color: white;
        }
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-arrow-left me-1"></i> Voltar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
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
    
    <!-- Mensagens de erro -->
    <?php if (isset($erro_msg)): ?>
    <div class="container mt-3">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $erro_msg; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Conteúdo principal -->
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Editar Produto</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo ($categoria['id'] == $produto['categoria_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="preco" class="form-label">Preço (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="preco" name="preco" value="<?php echo $produto['preco']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ean" class="form-label">Código de Barras (EAN)</label>
                            <input type="text" class="form-control" id="ean" name="ean" value="<?php echo htmlspecialchars($produto['ean'] ?? ''); ?>" placeholder="Ex: 789...">
                            <div class="form-text">Para integração com sistema de caixa (opcional)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="sku_externo" class="form-label">Código Interno (SKU)</label>
                            <input type="text" class="form-control" id="sku_externo" name="sku_externo" value="<?php echo htmlspecialchars($produto['sku_externo'] ?? ''); ?>" placeholder="ID do sistema">
                            <div class="form-text">Seu código no sistema ERP (opcional)</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    </div>
                    
                    <!-- Accordion para Organização -->
                    <div class="accordion mb-4" id="accordionEditarProduto">
                        
                        <!-- Seção 1: Medicamento (ANVISA) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAnvisa">
                                <button class="accordion-button collapsed bg-light text-warning" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnvisa" aria-expanded="false" aria-controls="collapseAnvisa">
                                    <i class="fas fa-prescription-bottle-alt me-2"></i> <strong>Informações de Medicamento (ANVISA)</strong>
                                </button>
                            </h2>
                            <div id="collapseAnvisa" class="accordion-collapse collapse" aria-labelledby="headingAnvisa" data-bs-parent="#accordionEditarProduto">
                                <div class="accordion-body">
                                    <div class="alert alert-info py-2">
                                        <small><i class="fas fa-info-circle me-1"></i> Preencha apenas se for medicamento.</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="principio_ativo" class="form-label">Princípio Ativo</label>
                                            <input type="text" class="form-control" id="principio_ativo" name="principio_ativo" 
                                                   value="<?php echo htmlspecialchars($produto['principio_ativo'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="registro_ms" class="form-label">Registro MS (ANVISA)</label>
                                            <input type="text" class="form-control" id="registro_ms" name="registro_ms" 
                                                   placeholder="Ex: 1.0000.0000.000-0"
                                                   value="<?php echo htmlspecialchars($produto['registro_ms'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tarja" class="form-label">Tarja</label>
                                            <select class="form-select" id="tarja" name="tarja">
                                                <option value="sem_tarja" <?php echo (isset($produto['tarja']) && $produto['tarja'] == 'sem_tarja') ? 'selected' : ''; ?>>Sem Tarja (Venda Livre)</option>
                                                <option value="amarela" <?php echo (isset($produto['tarja']) && $produto['tarja'] == 'amarela') ? 'selected' : ''; ?>>Tarja Amarela (Genérico)</option>
                                                <option value="vermelha" <?php echo (isset($produto['tarja']) && $produto['tarja'] == 'vermelha') ? 'selected' : ''; ?>>Tarja Vermelha (Prescrição)</option>
                                                <option value="preta" <?php echo (isset($produto['tarja']) && $produto['tarja'] == 'preta') ? 'selected' : ''; ?>>Tarja Preta (Controlado)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="indicacao" class="form-label">Indicação</label>
                                        <textarea class="form-control" id="indicacao" name="indicacao" rows="2"><?php echo htmlspecialchars($produto['indicacao'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contra_indicacao" class="form-label">Contraindicação</label>
                                        <textarea class="form-control" id="contra_indicacao" name="contra_indicacao" rows="2"><?php echo htmlspecialchars($produto['contra_indicacao'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="exige_receita" name="exige_receita" 
                                                       <?php echo (isset($produto['exige_receita']) && $produto['exige_receita']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="exige_receita">Exige Receita</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="mostrar_no_cardapio" name="mostrar_no_cardapio" 
                                                       <?php echo (!isset($produto['mostrar_no_cardapio']) || $produto['mostrar_no_cardapio']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="mostrar_no_cardapio">Mostrar no Cardápio</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <script>
                                        // Script para controle automático das opções com base na tarja
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const tarjaSelect = document.getElementById('tarja');
                                            const exigeReceitaCheck = document.getElementById('exige_receita');
                                            const mostrarCardapioCheck = document.getElementById('mostrar_no_cardapio');
                                            
                                            function updateOptions() {
                                                const tarjaValue = tarjaSelect.value;
                                                
                                                // Define se exige receita baseado na tarja
                                                if (tarjaValue === 'vermelha' || tarjaValue === 'preta') {
                                                    exigeReceitaCheck.checked = true;
                                                    exigeReceitaCheck.disabled = true;
                                                } else {
                                                    exigeReceitaCheck.disabled = false;
                                                }
                                                
                                                // Define visualização no cardápio baseado na tarja
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
                                            
                                            if(tarjaSelect) {
                                                tarjaSelect.addEventListener('change', updateOptions);
                                                updateOptions();
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 2: Tamanhos e Variações -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTamanhos">
                                <button class="accordion-button collapsed bg-light text-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTamanhos" aria-expanded="false" aria-controls="collapseTamanhos">
                                    <i class="fas fa-ruler me-2"></i> <strong>Tamanhos e Variações</strong>
                                </button>
                            </h2>
                            <div id="collapseTamanhos" class="accordion-collapse collapse" aria-labelledby="headingTamanhos" data-bs-parent="#accordionEditarProduto">
                                <div class="accordion-body">
                                    <!-- Leite -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_leite" name="is_leite" 
                                               <?php echo (isset($produto['is_leite']) && $produto['is_leite']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_leite">
                                            <i class="fas fa-baby-bottle me-1 text-info"></i> É Leite/Fórmula Infantil
                                        </label>
                                        <div id="nbcal-warning" class="alert alert-danger mt-2 py-2" style="display: <?php echo (isset($produto['is_leite']) && $produto['is_leite']) ? 'block' : 'none'; ?>;">
                                            <small><i class="fas fa-exclamation-triangle me-1"></i> <strong>Atenção (NBCAL):</strong> É proibido realizar promoções comerciais de fórmulas infantis para lactentes (0 a 6 meses). Sujeito a multa pela ANVISA.</small>
                                        </div>
                                    </div>

                                    <!-- Tamanhos -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="tem_tamanhos" name="tem_tamanhos" 
                                               <?php echo (isset($produto['tem_tamanhos']) && $produto['tem_tamanhos']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="tem_tamanhos">
                                            <i class="fas fa-ruler me-1"></i> Ativar Seleção de Tamanhos
                                        </label>
                                        <div class="form-text">Para produtos com variações (P, M, G).</div>
                                    </div>
                                    
                                    <div id="tamanhos_container" style="display: <?php echo (isset($produto['tem_tamanhos']) && $produto['tem_tamanhos']) ? 'block' : 'none'; ?>;" class="border rounded p-3 bg-light">
                                        <h6 class="text-success mb-3">Selecione os tamanhos:</h6>
                                        
                                        <?php
                                        // Criar array dos tamanhos do produto para facilitar verificação
                                        $tamanhos_produto_ids = [];
                                        $precos_tamanhos = [];
                                        foreach ($tamanhos_produto as $tp) {
                                            $tamanhos_produto_ids[] = $tp['id'];
                                            $precos_tamanhos[$tp['id']] = $tp['preco_adicional'];
                                        }
                                        ?>
                                        
                                        <div class="row">
                                            <?php foreach ($tamanhos_disponiveis as $tamanho): ?>
                                            <?php 
                                            $is_selected = in_array($tamanho['id'], $tamanhos_produto_ids);
                                            $preco_adicional = isset($precos_tamanhos[$tamanho['id']]) ? $precos_tamanhos[$tamanho['id']] : '';
                                            ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input tamanho-checkbox" 
                                                           type="checkbox" 
                                                           id="tamanho_<?php echo $tamanho['id']; ?>" 
                                                           name="tamanhos_selecionados[]" 
                                                           value="<?php echo $tamanho['id']; ?>"
                                                           <?php echo $is_selected ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="tamanho_<?php echo $tamanho['id']; ?>">
                                                        <?php echo htmlspecialchars($tamanho['nome']); ?>
                                                    </label>
                                                </div>
                                                <div class="mt-1 ms-4 tamanho-preco" style="display: <?php echo $is_selected ? 'block' : 'none'; ?>;">
                                                    <input type="number" step="0.01" class="form-control form-control-sm" 
                                                           name="precos_adicionais[<?php echo $tamanho['id']; ?>]" 
                                                           placeholder="+ Preço (R$)"
                                                           value="<?php echo $preco_adicional; ?>">
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const temTamanhosCheck = document.getElementById('tem_tamanhos');
                                            const tamanhosContainer = document.getElementById('tamanhos_container');
                                            const tamanhoCheckboxes = document.querySelectorAll('.tamanho-checkbox');
                                            const isLeiteCheck = document.getElementById('is_leite');
                                            const nbcalWarning = document.getElementById('nbcal-warning');
                                            const promocaoCheck = document.getElementById('promocao');

                                            if (isLeiteCheck) {
                                                isLeiteCheck.addEventListener('change', function() {
                                                    if (this.checked) {
                                                        nbcalWarning.style.display = 'block';
                                                        if (promocaoCheck) {
                                                            promocaoCheck.checked = false;
                                                            promocaoCheck.disabled = true;
                                                        }
                                                    } else {
                                                        nbcalWarning.style.display = 'none';
                                                        if (promocaoCheck) {
                                                            promocaoCheck.disabled = false;
                                                        }
                                                    }
                                                });
                                                
                                                // Trigger on load
                                                if (isLeiteCheck.checked && promocaoCheck) {
                                                    promocaoCheck.disabled = true;
                                                }
                                            }
                                            
                                            if(temTamanhosCheck) {
                                                temTamanhosCheck.addEventListener('change', function() {
                                                    if (this.checked) {
                                                        tamanhosContainer.style.display = 'block';
                                                    } else {
                                                        tamanhosContainer.style.display = 'none';
                                                        tamanhoCheckboxes.forEach(cb => {
                                                            cb.checked = false;
                                                            const container = cb.closest('.col-md-6');
                                                            if(container) container.querySelector('.tamanho-preco').style.display = 'none';
                                                        });
                                                    }
                                                });
                                                
                                                tamanhoCheckboxes.forEach(checkbox => {
                                                    checkbox.addEventListener('change', function() {
                                                        const container = this.closest('.col-md-6');
                                                        if(container) {
                                                            const precoDiv = container.querySelector('.tamanho-preco');
                                                            if (this.checked) {
                                                                precoDiv.style.display = 'block';
                                                            } else {
                                                                precoDiv.style.display = 'none';
                                                                precoDiv.querySelector('input').value = '';
                                                            }
                                                        }
                                                    });
                                                });
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>

                    </div> <!-- Fim Accordion -->

                    <div class="mb-3">
                        <label for="imagem" class="form-label">Imagem do Produto</label>
                        <?php if (!empty($produto['imagem']) && file_exists($produto['imagem'])): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="preview-img">
                            <p class="text-muted">Imagem atual</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                        <div class="form-text">Deixe em branco para manter a imagem atual.</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promocao" name="promocao" <?php echo $produto['promocao'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="promocao">
                                    Em promoção
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="destaque" name="destaque" <?php echo $produto['destaque'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destaque">
                                    Produto em destaque
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="estoque_disponivel" name="estoque_disponivel" <?php echo $produto['estoque_disponivel'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="estoque_disponivel">
                                    Disponível em estoque
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
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
            <p class="coming-soon">Versão Demo - Em Desenvolvimento</p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>