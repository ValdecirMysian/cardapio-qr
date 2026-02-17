<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';
require_once 'classes/MotorCategorizacao.php';

verificarLogin();
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);
$motor = new MotorCategorizacao();

// Carregar categorias do banco (Restrição Estrita)
// Ordenamos pelo tamanho do nome para que categorias genéricas (ex: "Higiene")
// venham antes de categorias compostas (ex: "Fraldas e Higiene") na busca
$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY LENGTH(nome), nome");
$stmt->execute();
$categoriasBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);

// VERIFICAÇÃO E CRIAÇÃO AUTOMÁTICA DA CATEGORIA 'PERFUMARIA'
$perfumariaExiste = false;
foreach ($categoriasBanco as $cat) {
    if (stripos($cat['nome'], 'Perfumaria') !== false) {
        $perfumariaExiste = true;
        break;
    }
}

if (!$perfumariaExiste) {
    try {
        // Tenta inserir como categoria global com descrição e ícone
        $pdo->exec("INSERT INTO categorias (nome, descricao, icone) VALUES ('Perfumaria', 'Perfumes, maquiagens e cosméticos', 'spray-can')");
        
        // Recarrega as categorias
        $stmt->execute();
        $categoriasBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Se falhar (ex: exige farmacia_id), tenta com ID da farmácia atual
        if (isset($farmacia['id'])) {
            try {
                $stmtInsert = $pdo->prepare("INSERT INTO categorias (nome, descricao, icone, farmacia_id) VALUES ('Perfumaria', 'Perfumes, maquiagens e cosméticos', 'spray-can', ?)");
                $stmtInsert->execute([$farmacia['id']]);
                
                // Recarrega
                $stmt->execute();
                $categoriasBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {
                // Falha silenciosa: usuário terá que criar manualmente ou mapear para outra
            }
        }
    }
}

// -------------------------------------------------------------------------
// CRIAÇÃO AUTOMÁTICA DE CATEGORIAS ADICIONAIS
// -------------------------------------------------------------------------
$categoriasFaltantes = [
    'Dermocosméticos' => ['icone' => 'spa', 'desc' => 'Cuidados para a pele'],
    'Ortopedia' => ['icone' => 'crutch', 'desc' => 'Imobilizadores e auxílio'],
    'Primeiros Socorros' => ['icone' => 'first-aid', 'desc' => 'Curativos e cuidados básicos'],
    'Aparelhos' => ['icone' => 'stethoscope', 'desc' => 'Medidores e testes'],
    'Suplementos' => ['icone' => 'dumbbell', 'desc' => 'Vitaminas e nutrição'],
    'Leites e Fórmulas' => ['icone' => 'baby-bottle', 'desc' => 'Leites, fórmulas e compostos lácteos']
];

$categoriasNomesBanco = array_column($categoriasBanco, 'nome');
$categoriasNomesBancoLower = array_map('mb_strtolower', $categoriasNomesBanco);

foreach ($categoriasFaltantes as $nomeCat => $dadosCat) {
    if (!in_array(mb_strtolower($nomeCat), $categoriasNomesBancoLower)) {
        try {
            // Tenta inserir global
            $pdo->exec("INSERT INTO categorias (nome, descricao, icone) VALUES ('$nomeCat', '{$dadosCat['desc']}', '{$dadosCat['icone']}')");
        } catch (Exception $e) {
            // Tenta inserir por farmácia
            if (isset($farmacia['id'])) {
                try {
                    $stmtInsert = $pdo->prepare("INSERT INTO categorias (nome, descricao, icone, farmacia_id) VALUES (?, ?, ?, ?)");
                    $stmtInsert->execute([$nomeCat, $dadosCat['desc'], $dadosCat['icone'], $farmacia['id']]);
                } catch (Exception $e2) {}
            }
        }
    }
}

// Recarrega as categorias após possíveis inserções
$stmt->execute();
$categoriasBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);
// -------------------------------------------------------------------------

// Carregar tamanhos do banco (Para Fraldas e outros)
$tamanhosBanco = obterTamanhos($pdo);

// Identificar IDs dos tamanhos padrão de fraldas
$idsTamanhosPadrao = [];
$tamanhosPadraoNomes = ['RN', 'P', 'M', 'G', 'XG', 'XXG', 'SXG'];
foreach ($tamanhosBanco as $t) {
    if (in_array(strtoupper($t['nome']), $tamanhosPadraoNomes)) {
        $idsTamanhosPadrao[] = $t['id'];
    }
}

// Mapa de correspondência flexível (Motor -> Banco)
$mapaCategorias = [
    'bebes' => ['infantil', 'bebê', 'bebe', 'criança', 'fralda', 'fr', 'fral'],
    'higiene' => ['higiene', 'pessoal', 'banho', 'corpo', 'cabelo', 'desodorante', 'sabonete', 'pasta', 'dente', 'bucal', 'escova', 'fio dental', 'absorvente', 'papel higienico', 'barbear', 'gillette'],
    'medicamentos' => ['medicamento', 'farmacia', 'etico', 'generico', 'referencia', 'similares'],
    'leites_formulas' => ['leite', 'formula', 'composto lacteo', 'nan', 'aptamil', 'nestogeno', 'enfamil', 'mucilon', 'ninho', 'neslac'],
    'perfumaria' => ['perfume', 'beleza', 'cosmetico', 'maquiagem', 'colonia', 'fragrancia', 'tintura', 'coloracao'],
    'dermocosmeticos' => ['dermo', 'pele', 'rosto', 'solar', 'protetor', 'anti-idade', 'rugas'],
    'ortopedia' => ['ortopedia', 'aparelho', 'muleta', 'joelheira', 'meia compressiva', 'tipoia'],
    'suplementos' => ['suplemento', 'vitamina', 'whey', 'academia', 'complexo b', 'calcio', 'omega 3', 'mineral'],
    'primeiros_socorros' => ['curativo', 'band-aid', 'gaze', 'esparadrapo', 'algodao', 'agua oxigenada'],
    'aparelhos' => ['pressao', 'termometro', 'glicose', 'inalador', 'nebulizador', 'teste']
];

$mensagem = '';
$produtos_processados = [];

// PROCESSAMENTO DO UPLOAD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo_csv'])) {
    if ($_FILES['arquivo_csv']['error'] == 0) {
        $arquivo = $_FILES['arquivo_csv']['tmp_name'];
        
        if (($handle = fopen($arquivo, "r")) !== FALSE) {
            $line = fgets($handle);
            $delimitador = (strpos($line, ';') !== false) ? ';' : ',';
            rewind($handle);
            
            $header = fgetcsv($handle, 1000, $delimitador);
            
            $colMap = [];
            foreach ($header as $index => $colName) {
                $colName = strtolower(trim($colName));
                if (strpos($colName, 'nome') !== false || strpos($colName, 'produto') !== false || strpos($colName, 'descricao') !== false) $colMap['nome'] = $index;
                if (strpos($colName, 'grupo') !== false || strpos($colName, 'categoria') !== false || strpos($colName, 'secao') !== false) $colMap['grupo'] = $index;
                if (strpos($colName, 'ean') !== false || strpos($colName, 'barras') !== false || strpos($colName, 'gtin') !== false) $colMap['ean'] = $index;
                if (strpos($colName, 'preco') !== false || strpos($colName, 'valor') !== false || strpos($colName, 'venda') !== false) $colMap['preco'] = $index;
                if (strpos($colName, 'estoque') !== false || strpos($colName, 'saldo') !== false || strpos($colName, 'qtd') !== false) $colMap['estoque'] = $index;
            }

            if (!isset($colMap['nome'])) $colMap['nome'] = 0;
            if (!isset($colMap['grupo'])) $colMap['grupo'] = 1;
            
            while (($data = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
                if (empty($data[$colMap['nome']])) continue;

                $nome = mb_convert_encoding($data[$colMap['nome']], 'UTF-8', 'ISO-8859-1, UTF-8');
                $grupo = isset($colMap['grupo']) && isset($data[$colMap['grupo']]) ? mb_convert_encoding($data[$colMap['grupo']], 'UTF-8', 'ISO-8859-1, UTF-8') : '';
                $ean = isset($colMap['ean']) && isset($data[$colMap['ean']]) ? preg_replace('/[^0-9]/', '', $data[$colMap['ean']]) : '';
                
                $precoRaw = isset($colMap['preco']) && isset($data[$colMap['preco']]) ? $data[$colMap['preco']] : '0';
                $precoRaw = str_replace(['R$', ' ', '.'], '', $precoRaw);
                $precoRaw = str_replace(',', '.', $precoRaw);
                $preco = (float)$precoRaw;
                
                $estoque = isset($colMap['estoque']) && isset($data[$colMap['estoque']]) ? (int)preg_replace('/[^0-9]/', '', $data[$colMap['estoque']]) : 0;
                
                // 1. MOTOR DE CATEGORIZAÇÃO
                $resultado = $motor->categorizarProduto([
                    'nome' => $nome,
                    'grupo_csv' => $grupo,
                    'ean' => $ean
                ]);

                // 2. LOGICA DE NEGÓCIO: MEDICAMENTOS
                $isMedicamento = false;
                $mostrarNoCardapio = 1; 
                $tarjaSugerida = 'sem_tarja';

                if (stripos($resultado['categoria'] ?? '', 'medicamento') !== false || 
                    stripos($resultado['subcategoria'] ?? '', 'medicamento') !== false ||
                    stripos($grupo, 'medicamento') !== false ||
                    stripos($grupo, 'etico') !== false ||
                    stripos($grupo, 'generico') !== false ||
                    stripos($grupo, 'similar') !== false) {
                    
                    $isMedicamento = true;
                    $mostrarNoCardapio = 0; 
                    
                    if (stripos($nome, 'controlado') !== false || stripos($grupo, 'controlado') !== false) {
                        $tarjaSugerida = 'preta';
                    } elseif (stripos($nome, 'antibiotico') !== false) {
                        $tarjaSugerida = 'vermelha';
                    }
                }

                // 3. LOGICA DE NEGÓCIO: FRALDAS E TAMANHOS
                $tamanhosSugeridos = []; // Agora é array
                $isFralda = false;
                
                // Verifica se é fralda (incluindo abreviações comuns de farmácia como "FR" ou "FRAL")
                $nomeUpper = mb_strtoupper($nome);
                if (stripos($resultado['categoria'] ?? '', 'fralda') !== false || 
                    stripos($nome, 'fralda') !== false ||
                    preg_match('/\bFR\b/i', $nome) || // FR como palavra isolada
                    preg_match('/\bFR\.\b/i', $nome) || // FR. com ponto
                    stripos($nome, 'FRAL') !== false) {
                    
                    $isFralda = true;
                    // Por padrão, se é fralda, sugere TODOS os tamanhos padrão
                    $tamanhosSugeridos = $idsTamanhosPadrao;
                }

                // 3.1 LOGICA DE NEGÓCIO: LEITES (Flag is_leite)
                $isLeite = 0;
                if (stripos($resultado['categoria'] ?? '', 'leite') !== false || 
                    stripos($resultado['categoria'] ?? '', 'formula') !== false ||
                    stripos($nome, 'leite') !== false || 
                    stripos($nome, 'formula') !== false ||
                    stripos($nome, 'nan') !== false ||
                    stripos($nome, 'aptamil') !== false ||
                    stripos($nome, 'nestogeno') !== false ||
                    stripos($nome, 'enfamil') !== false) {
                    
                    $isLeite = 1;
                }

                // 4. MAPEAMENTO FINAL PARA CATEGORIA DO BANCO (LÓGICA MELHORADA)
                $categoriaId = null;
                
                // Tentativa 1: Match da Subcategoria (Mais específico)
                if (!empty($resultado['subcategoria'])) {
                    foreach ($categoriasBanco as $catBanco) {
                        if (stripos($catBanco['nome'], $resultado['subcategoria']) !== false) {
                            $categoriaId = $catBanco['id'];
                            break;
                        }
                    }
                }

                // Tentativa 2: Match da Categoria Principal via Mapa
                if (!$categoriaId && !empty($resultado['categoria_principal'])) {
                    $chave = strtolower($resultado['categoria_principal']);
                    if (isset($mapaCategorias[$chave])) {
                        foreach ($mapaCategorias[$chave] as $keyword) {
                            foreach ($categoriasBanco as $catBanco) {
                                if (stripos($catBanco['nome'], $keyword) !== false) {
                                    $categoriaId = $catBanco['id'];
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Tentativa 3: Match direto com o nome da categoria do motor
                if (!$categoriaId) {
                    foreach ($categoriasBanco as $catBanco) {
                        if (stripos($catBanco['nome'], $resultado['categoria_principal'] ?? '') !== false) {
                            $categoriaId = $catBanco['id'];
                            break;
                        }
                    }
                }
                
                // Fallback
                if (!$categoriaId) {
                    foreach ($categoriasBanco as $catBanco) {
                        if (stripos($catBanco['nome'], 'geral') !== false || stripos($catBanco['nome'], 'outros') !== false || stripos($catBanco['nome'], 'diversos') !== false) {
                            $categoriaId = $catBanco['id'];
                            break;
                        }
                    }
                    if (!$categoriaId && !empty($categoriasBanco)) {
                        $categoriaId = $categoriasBanco[0]['id'];
                    }
                }

                $produtos_processados[] = [
                    'nome' => $nome,
                    'grupo_csv' => $grupo,
                    'ean' => $ean,
                    'preco' => $preco,
                    'estoque' => $estoque,
                    'categoria_id' => $categoriaId,
                    'tamanhos_sugeridos' => $tamanhosSugeridos, // Array de IDs
                    'is_fralda' => $isFralda,
                    'is_leite' => $isLeite, // Flag para Leites e Fórmulas
                    'mostrar_no_cardapio' => $mostrarNoCardapio,
                    'is_medicamento' => $isMedicamento,
                    'tarja_sugerida' => $tarjaSugerida,
                    'confianca' => $resultado['confianca'] ?? 0,
                    'motor_cat_debug' => $resultado['categoria'] ?? 'N/A' // Debug
                ];
            }
            fclose($handle);
            $_SESSION['import_temp'] = $produtos_processados;
        }
    } else {
        $mensagem = '<div class="alert alert-danger">Erro no upload do arquivo. Código: '.$_FILES['arquivo_csv']['error'].'</div>';
    }
}

// CONFIRMAÇÃO DA IMPORTAÇÃO (SALVAR NO BANCO)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_importacao']) && isset($_SESSION['import_temp'])) {
    
    $countSucesso = 0;
    $countErro = 0;
    
    $sqlProduto = "INSERT INTO produtos 
        (farmacia_id, categoria_id, nome, preco, estoque_disponivel, mostrar_no_cardapio, ean, 
         tarja, tem_tamanhos, is_leite, principio_ativo, registro_ms, indicacao, contra_indicacao, exige_receita, imagem) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtProduto = $pdo->prepare($sqlProduto);
    
    $sqlTamanho = "INSERT INTO produto_tamanhos (produto_id, tamanho_id, preco_adicional) VALUES (?, ?, 0.00)";
    $stmtTamanho = $pdo->prepare($sqlTamanho);

    // Dados do formulário
    $nomes = $_POST['nome'] ?? [];
    $categorias = $_POST['categoria'] ?? [];
    $precos = $_POST['preco'] ?? [];
    $estoques = $_POST['estoque'] ?? [];
    $visiveis = $_POST['visivel'] ?? []; 
    $tamanhosPost = $_POST['tamanhos'] ?? []; // Agora é array multidimensional
    $eans = $_POST['ean'] ?? [];
    $tarjas = $_POST['tarja'] ?? [];

    foreach ($_SESSION['import_temp'] as $i => $prodOriginal) {
        
        $nomeFinal = $nomes[$i] ?? $prodOriginal['nome'];
        $catFinal = $categorias[$i] ?? $prodOriginal['categoria_id'];
        $precoFinal = isset($precos[$i]) ? str_replace(',', '.', $precos[$i]) : $prodOriginal['preco'];
        $estoqueFinal = $estoques[$i] ?? $prodOriginal['estoque'];
        $eanFinal = $eans[$i] ?? $prodOriginal['ean'];
        $tarjaFinal = $tarjas[$i] ?? $prodOriginal['tarja_sugerida'];
        $visivelFinal = isset($visiveis[$i]) ? 1 : 0;
        
        // Flags
        $isLeiteFinal = isset($prodOriginal['is_leite']) ? $prodOriginal['is_leite'] : 0;
        
        // Tamanhos (array de IDs)
        $idsTamanhosFinais = isset($tamanhosPost[$i]) ? $tamanhosPost[$i] : [];
        $temTamanhos = !empty($idsTamanhosFinais) ? 1 : 0;

        $exigeReceita = ($tarjaFinal == 'vermelha' || $tarjaFinal == 'preta') ? 1 : 0;
        if ($tarjaFinal == 'preta') {
            $visivelFinal = 0;
        }

        try {
                $imagem = null;
                // Usa o cache do proxy se existir, ou tenta baixar via proxy
                if (!empty($eanFinal)) {
                    $cacheFile = 'uploads/cache_img/' . $eanFinal . '.jpg';
                    $novoNome = "uploads/" . time() . "_" . $eanFinal . ".jpg";
                    
                    if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
                        // Copia do cache
                        if (copy($cacheFile, $novoNome)) {
                            $imagem = $novoNome;
                        }
                    } else {
                        // Tenta forçar o proxy (local request)
                         $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                         $urlProxy = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/buscar_imagem_proxy.php?ean=" . $eanFinal . "&nome=" . urlencode($nomeFinal);
                         
                         $conteudo = @file_get_contents($urlProxy);
                        if ($conteudo && strlen($conteudo) > 1000) {
                            if (file_put_contents($novoNome, $conteudo)) {
                                $imagem = $novoNome;
                            }
                        }
                    }
                }

                $stmtProduto->execute([
                    $farmacia['id'],
                    $catFinal,
                    $nomeFinal,
                    $precoFinal,
                    $estoqueFinal,
                    $visivelFinal,
                    $eanFinal,
                    $tarjaFinal,
                    $temTamanhos,
                    $isLeiteFinal,
                    '', '', '', '', $exigeReceita,
                    $imagem 
                ]);
            
            $novoId = $pdo->lastInsertId();
            
            if ($temTamanhos) {
                foreach ($idsTamanhosFinais as $tamId) {
                    $stmtTamanho->execute([$novoId, $tamId]);
                }
            }
            
            $countSucesso++;
            
        } catch (PDOException $e) {
            error_log("Erro importação linha $i: " . $e->getMessage());
            $countErro++;
        }
    }
    
    $mensagem = "<div class='alert alert-success'>
        Importação concluída!<br>
        <strong>$countSucesso</strong> produtos importados com sucesso.<br>
        " . ($countErro > 0 ? "<strong>$countErro</strong> erros." : "") . "
        <br><a href='index.php' class='btn btn-success mt-2'>Ir para o Cardápio</a>
    </div>";
    
    unset($_SESSION['import_temp']);
    $produtos_processados = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Importar Produtos CSV - <?php echo htmlspecialchars($farmacia['nome']); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .row-medicamento { background-color: #fff3cd; } 
        .row-oculto { opacity: 0.7; background-color: #f8f9fa; }
        .table-input { min-width: 80px; }
        .select-categoria { min-width: 150px; }
        .badge-anvisa { font-size: 0.7em; }
        .dropdown-menu-tamanhos { max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-csv text-success"></i> Importação de Produtos</h2>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>

    <?php echo $mensagem; ?>

    <?php if (empty($produtos_processados) && empty($mensagem)): ?>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-upload me-2"></i> 1. Carregar Arquivo CSV</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Selecione o arquivo (.csv):</label>
                    <input type="file" class="form-control" name="arquivo_csv" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-cog me-1"></i> Processar Arquivo
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($produtos_processados)): ?>
    <form action="" method="POST" id="formImportacao">
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-primary"><i class="fas fa-list-check me-2"></i> 2. Revisão Pré-Importação</h5>
                </div>
                <span class="badge bg-secondary rounded-pill"><?php echo count($produtos_processados); ?> itens</span>
            </div>
        </div>
        
        <div class="alert alert-warning m-3 border-0 bg-warning bg-opacity-10">
            <div class="d-flex">
                <i class="fas fa-exclamation-triangle text-warning me-3 fa-2x"></i>
                <div>
                    <strong>Regras Aplicadas:</strong>
                    <ul class="mb-0 small">
                        <li><strong>Fraldas:</strong> Marcadas com todos os tamanhos padrão (RN a XXG) selecionados.</li>
                        <li><strong>Categorias:</strong> Mapeamento inteligente para categorias do seu sistema.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
            <table class="table table-hover table-bordered mb-0 align-middle text-sm">
                <thead class="table-light sticky-top" style="z-index: 10;">
                    <tr>
                        <th width="30%">Produto</th>
                        <th width="20%">Categoria (Sistema)</th>
                        <th width="15%">Tamanho / Tarja</th>
                        <th width="10%">Preço (R$)</th>
                        <th width="10%">Estoque</th>
                        <th width="5%" class="text-center"><i class="fas fa-eye"></i></th>
                        <th width="10%">EAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_processados as $i => $prod): ?>
                        <?php 
                            $rowClass = '';
                            if ($prod['is_medicamento']) $rowClass = 'row-medicamento';
                            if (!$prod['mostrar_no_cardapio']) $rowClass .= ' row-oculto';
                        ?>
                        <tr class="<?php echo $rowClass; ?>" id="row-<?php echo $i; ?>">
                            <td>
                                <!-- Badges Dinâmicos -->
                                <div class="d-flex align-items-center mb-1">
                                    <?php if (!empty($prod['ean'])): ?>
                                <div class="me-2 position-relative" style="width: 40px; height: 40px; min-width: 40px;">
                                    <!-- Tentativa 1: Imagem Padrão de Categoria (Fallback Imediato) -->
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center" 
                                         style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; z-index: 1;">
                                         <i class="fas fa-box text-muted"></i>
                                    </div>
                                    
                                    <!-- Tentativa via Proxy Inteligente (OFF -> OBF -> Google) -->
                                    <img src="buscar_imagem_proxy.php?ean=<?php echo $prod['ean']; ?>&nome=<?php echo urlencode($prod['nome']); ?>&v=<?php echo time(); ?>" 
                                         class="img-fluid rounded border shadow-sm position-relative" 
                                         style="width: 100%; height: 100%; object-fit: cover; z-index: 2;"
                                         loading="lazy"
                                         onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>
                                    <input type="text" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0" name="nome[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($prod['nome']); ?>">
                                </div>
                                
                                <span class="badge bg-danger badge-anvisa badge-medicamento <?php echo $prod['is_medicamento'] ? '' : 'd-none'; ?>">Medicamento</span>
                                <span class="badge bg-info badge-anvisa badge-fralda <?php echo $prod['is_fralda'] ? '' : 'd-none'; ?>">Fralda</span>
                                <span class="badge bg-warning text-dark badge-anvisa badge-leite <?php echo (isset($prod['is_leite']) && $prod['is_leite']) ? '' : 'd-none'; ?>" title="Regra ANVISA: Leites e fórmulas">Fórmula/Leite</span>
                                
                                <small class="d-block text-muted" style="font-size: 0.65em">Sugestão: <?php echo $prod['motor_cat_debug']; ?></small>
                            </td>
                            
                            <td>
                                <select class="form-select form-select-sm select-categoria" name="categoria[<?php echo $i; ?>]" onchange="atualizarLinha(this, <?php echo $i; ?>)">
                                    <?php foreach ($categoriasBanco as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                data-nome="<?php echo mb_strtolower($cat['nome']); ?>"
                                                <?php echo ($cat['id'] == $prod['categoria_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            
                            <td class="td-opcoes-especificas">
                                <!-- UI FRALDA -->
                                <div class="ui-fralda <?php echo $prod['is_fralda'] ? '' : 'd-none'; ?>">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Tamanhos
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-tamanhos p-2">
                                            <?php foreach ($tamanhosBanco as $tam): ?>
                                                <?php 
                                                    // Verifica se está nos sugeridos (que agora inclui todos os padrão)
                                                    $checked = in_array($tam['id'], $prod['tamanhos_sugeridos']) ? 'checked' : '';
                                                ?>
                                                <li>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="tamanhos[<?php echo $i; ?>][]" value="<?php echo $tam['id']; ?>" id="tam_<?php echo $i; ?>_<?php echo $tam['id']; ?>" <?php echo $checked; ?>>
                                                        <label class="form-check-label" for="tam_<?php echo $i; ?>_<?php echo $tam['id']; ?>">
                                                            <?php echo htmlspecialchars($tam['nome']); ?>
                                                        </label>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <!-- Inputs hidden para garantir envio de flags se JS falhar ou não alterar -->
                                    <input type="hidden" name="is_fralda[<?php echo $i; ?>]" class="input-is-fralda" value="<?php echo $prod['is_fralda']; ?>">
                                </div>

                                <!-- UI MEDICAMENTO -->
                                <div class="ui-medicamento <?php echo $prod['is_medicamento'] ? '' : 'd-none'; ?>">
                                    <select class="form-select form-select-sm" name="tarja[<?php echo $i; ?>]">
                                        <option value="sem_tarja" <?php echo ($prod['tarja_sugerida'] == 'sem_tarja') ? 'selected' : ''; ?>>Sem Tarja</option>
                                        <option value="amarela" <?php echo ($prod['tarja_sugerida'] == 'amarela') ? 'selected' : ''; ?>>Genérico</option>
                                        <option value="vermelha" <?php echo ($prod['tarja_sugerida'] == 'vermelha') ? 'selected' : ''; ?>>Vermelha</option>
                                        <option value="preta" <?php echo ($prod['tarja_sugerida'] == 'preta') ? 'selected' : ''; ?>>Preta</option>
                                    </select>
                                    <input type="hidden" name="is_medicamento[<?php echo $i; ?>]" class="input-is-medicamento" value="<?php echo $prod['is_medicamento']; ?>">
                                </div>

                                <!-- UI DEFAULT -->
                                <div class="ui-default <?php echo (!$prod['is_fralda'] && !$prod['is_medicamento']) ? '' : 'd-none'; ?>">
                                    <span class="text-muted small">-</span>
                                </div>
                                
                                <!-- Flag Leite Hidden -->
                                <input type="hidden" name="is_leite[<?php echo $i; ?>]" class="input-is-leite" value="<?php echo $prod['is_leite']; ?>">
                            </td>
                            
                            <td>
                                <input type="text" class="form-control form-control-sm table-input" name="preco[<?php echo $i; ?>]" value="<?php echo number_format($prod['preco'], 2, ',', '.'); ?>">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm table-input" name="estoque[<?php echo $i; ?>]" value="<?php echo $prod['estoque']; ?>">
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="visivel[<?php echo $i; ?>]" value="1" 
                                           <?php echo $prod['mostrar_no_cardapio'] ? 'checked' : ''; ?>>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm table-input" name="ean[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($prod['ean']); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-white py-3 text-end sticky-bottom">
            <a href="importar_csv.php" class="btn btn-outline-danger me-2">Cancelar</a>
            <input type="hidden" name="confirmar_importacao" value="1">
            <button type="button" class="btn btn-success btn-lg" onclick="submeterImportacao()">
                <i class="fas fa-check-circle me-1"></i> Confirmar Importação
            </button>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function submeterImportacao() {
    if(confirm('Tem certeza que deseja importar estes produtos?')) {
        document.getElementById('formImportacao').submit();
    }
}

function atualizarLinha(selectElement, index) {
    // 1. Identificar a categoria selecionada
    var option = selectElement.options[selectElement.selectedIndex];
    var nomeCategoria = option.getAttribute('data-nome') || '';
    
    // 2. Definir flags baseadas no nome da categoria
    var isFralda = (nomeCategoria.indexOf('fralda') !== -1 || nomeCategoria.indexOf('bebe') !== -1 || nomeCategoria.indexOf('infantil') !== -1);
    // Refinando regra de fralda: se for bebe/infantil, pode não ser fralda, mas vamos assumir que o usuário quer ver opções de tamanho se selecionar algo infantil que tenha tamanhos.
    // Melhor: Só ativa se tiver "fralda" no nome da categoria OU se o usuário selecionar explicitamente.
    // Ajuste: A categoria "Fraldas e Higiene" contém "fralda". A categoria "Seção Infantil" não.
    // Vamos ser mais específicos:
    isFralda = (nomeCategoria.indexOf('fralda') !== -1);

    var isMedicamento = (
        nomeCategoria.indexOf('medicamento') !== -1 || 
        nomeCategoria.indexOf('farmacia') !== -1 || 
        nomeCategoria.indexOf('etico') !== -1 || 
        nomeCategoria.indexOf('generico') !== -1 || 
        nomeCategoria.indexOf('referencia') !== -1
    );

    var isLeite = (
        nomeCategoria.indexOf('leite') !== -1 || 
        nomeCategoria.indexOf('formula') !== -1 ||
        nomeCategoria.indexOf('lacteo') !== -1
    );

    // 3. Selecionar elementos da linha
    var row = document.getElementById('row-' + index);
    
    // Badges
    var badgeFralda = row.querySelector('.badge-fralda');
    var badgeMedicamento = row.querySelector('.badge-medicamento');
    var badgeLeite = row.querySelector('.badge-leite');

    // UI Containers
    var uiFralda = row.querySelector('.ui-fralda');
    var uiMedicamento = row.querySelector('.ui-medicamento');
    var uiDefault = row.querySelector('.ui-default');

    // Hidden Inputs
    var inputIsFralda = row.querySelector('.input-is-fralda');
    var inputIsMedicamento = row.querySelector('.input-is-medicamento');
    var inputIsLeite = row.querySelector('.input-is-leite');

    // 4. Atualizar Badges e Inputs
    if (isFralda) {
        badgeFralda.classList.remove('d-none');
        inputIsFralda.value = '1';
    } else {
        badgeFralda.classList.add('d-none');
        inputIsFralda.value = '0';
    }

    if (isMedicamento) {
        badgeMedicamento.classList.remove('d-none');
        row.classList.add('row-medicamento');
        inputIsMedicamento.value = '1';
    } else {
        badgeMedicamento.classList.add('d-none');
        row.classList.remove('row-medicamento');
        inputIsMedicamento.value = '0';
    }

    if (isLeite) {
        badgeLeite.classList.remove('d-none');
        inputIsLeite.value = '1';
    } else {
        badgeLeite.classList.add('d-none');
        inputIsLeite.value = '0';
    }

    // 5. Atualizar Interface (Prioridade: Fralda > Medicamento > Default)
    // Esconder tudo primeiro
    uiFralda.classList.add('d-none');
    uiMedicamento.classList.add('d-none');
    uiDefault.classList.add('d-none');

    if (isFralda) {
        uiFralda.classList.remove('d-none');
        
        // Resetar select de tarja se existir
        var selectTarja = row.querySelector('select[name^="tarja"]');
        if (selectTarja) selectTarja.value = 'sem_tarja';
        
        // Ativar tamanhos padrão
        var checkboxes = row.querySelectorAll('input[type="checkbox"][name^="tamanhos"]');
        checkboxes.forEach(cb => cb.checked = true);

    } else if (isMedicamento) {
        uiMedicamento.classList.remove('d-none');
        
        // Desmarcar tamanhos
        var checkboxes = row.querySelectorAll('input[type="checkbox"][name^="tamanhos"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Se já tinha tarja sugerida (ex: amarela), mantém. Se não, reseta.
        // Como o JS não sabe a tarja original do backend, vamos deixar o usuário escolher.
    } else {
        uiDefault.classList.remove('d-none');
        
        // Resetar tudo
        var selectTarja = row.querySelector('select[name^="tarja"]');
        if (selectTarja) selectTarja.value = 'sem_tarja';
        
        var checkboxes = row.querySelectorAll('input[type="checkbox"][name^="tamanhos"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
}
</script>
</body>
</html>
