<?php
session_start();
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
require_once 'functions.php';

// Verificar login
verificarLogin();

// Obter farmácia
$farmacia = obterFarmacia($pdo, $_SESSION['user_id']);

// Buscar produtos (apenas campos essenciais para leveza)
$stmt = $pdo->prepare("
    SELECT id, nome, preco, estoque_disponivel, promocao, destaque, categoria_id, ean 
    FROM produtos 
    WHERE farmacia_id = ? 
    ORDER BY nome ASC
");
$stmt->execute([$farmacia['id']]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque Rápido - <?php echo htmlspecialchars($farmacia['nome']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7B68EE;
            --primary-dark: #6A5ACD;
            --bg-light: #f8f9fa;
        }
        
        body {
            background-color: var(--bg-light);
            padding-top: 80px; /* Espaço para o header fixo */
        }

        /* Header Fixo */
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: white;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border-radius: 50px;
            border: 2px solid #e0e0e0;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(123, 104, 238, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1.2rem;
        }

        /* Lista de Produtos */
        .product-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .product-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        .product-item:nth-child(even) {
            background-color: rgba(123, 104, 238, 0.05); /* Roxo bem clarinho */
        }

        .product-item:hover {
            background-color: #f0f0f5;
        }

        .product-name {
            flex: 1;
            font-weight: 600;
            color: #333;
            margin-right: 15px;
        }

        .product-options {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-right: 20px;
        }

        .option-check {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #555;
            cursor: pointer;
        }
        
        .option-check input {
            margin-right: 5px;
            cursor: pointer;
        }

        .product-price-container {
            width: 120px;
            margin-right: 20px;
            position: relative;
        }

        .product-price-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-weight: 500;
            text-align: right;
        }

        .product-price-input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .save-indicator {
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--success);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .save-indicator.visible {
            opacity: 1;
        }

        /* Toggle Switch Estilo iOS */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Botão Voltar */
        .btn-back {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .btn-back:hover {
            color: var(--primary-dark);
        }

        /* Filtros */
        .filter-select {
            border-radius: 50px;
            border: 2px solid #e0e0e0;
            padding: 12px 20px;
            cursor: pointer;
        }
        
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: none;
        }
    </style>
</head>
<body>

    <!-- Header Fixo -->
    <div class="fixed-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 mb-2 mb-md-0">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Buscar produto...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="filterSelect" class="form-select filter-select">
                        <option value="all">Todos os Produtos</option>
                        <option value="active">Apenas Ativos</option>
                        <option value="inactive">Apenas Inativos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="index.php" class="btn-back mb-0">
                <i class="fas fa-arrow-left me-2"></i> Voltar ao Dashboard
            </a>
            <a href="logs_integracao.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-history me-1"></i> Ver Logs de Integração
            </a>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 fw-bold text-secondary">Estoque Rápido</h4>
            <span class="badge bg-secondary rounded-pill"><?php echo count($produtos); ?> produtos</span>
        </div>

        <div class="product-list" id="productList">
            <?php if (empty($produtos)): ?>
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <p>Nenhum produto encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($produtos as $prod): ?>
                    <div class="product-item" 
                         data-id="<?php echo $prod['id']; ?>" 
                         data-name="<?php echo strtolower($prod['nome']); ?>"
                         data-active="<?php echo $prod['estoque_disponivel']; ?>">
                        
                        <div class="product-name">
                            <div><?php echo htmlspecialchars($prod['nome']); ?></div>
                            <?php if (!empty($prod['ean'])): ?>
                                <div class="text-muted small mt-1" style="font-size: 0.85rem;">
                                    <i class="fas fa-check-circle text-success me-1" title="Sincronizado com ERP"></i>
                                    EAN: <?php echo htmlspecialchars($prod['ean']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-price-container">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-transparent border-0 ps-0">R$</span>
                                <input type="number" 
                                       step="0.01" 
                                       class="form-control product-price-input" 
                                       value="<?php echo $prod['preco']; ?>"
                                       onblur="updatePrice(<?php echo $prod['id']; ?>, this)">
                            </div>
                            <i class="fas fa-check-circle save-indicator" id="save-<?php echo $prod['id']; ?>"></i>
                        </div>

                        <div class="product-options">
                            <label class="option-check" title="Em Promoção">
                                <input type="checkbox" 
                                       <?php echo $prod['promocao'] ? 'checked' : ''; ?> 
                                       onchange="toggleOption(<?php echo $prod['id']; ?>, 'promocao', this)">
                                Promo
                            </label>
                            <label class="option-check" title="Produto em Destaque">
                                <input type="checkbox" 
                                       <?php echo $prod['destaque'] ? 'checked' : ''; ?> 
                                       onchange="toggleOption(<?php echo $prod['id']; ?>, 'destaque', this)">
                                Destaque
                            </label>
                            <label class="option-check" title="Disponível em Estoque">
                                <input type="checkbox" 
                                       <?php echo $prod['estoque_disponivel'] ? 'checked' : ''; ?> 
                                       onchange="toggleStatus(<?php echo $prod['id']; ?>, this)">
                                Estoque
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="noResults" class="text-center p-5 text-muted d-none">
            <i class="fas fa-search fa-3x mb-3"></i>
            <p>Nenhum produto corresponde à sua busca.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- Lógica de Busca e Filtro ---
        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        const productItems = document.querySelectorAll('.product-item');
        const noResults = document.getElementById('noResults');

        function filterProducts() {
            const term = searchInput.value.toLowerCase();
            const filter = filterSelect.value;
            let visibleCount = 0;

            productItems.forEach(item => {
                const name = item.getAttribute('data-name');
                const isActive = item.getAttribute('data-active') == '1';
                
                let matchesSearch = name.includes(term);
                let matchesFilter = true;

                if (filter === 'active' && !isActive) matchesFilter = false;
                if (filter === 'inactive' && isActive) matchesFilter = false;

                if (matchesSearch && matchesFilter) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }

        // Debounce para busca
        let timeout = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(filterProducts, 300);
        });

        filterSelect.addEventListener('change', filterProducts);

        // --- Lógica de Atualização (API) ---

        async function updatePrice(id, input) {
            const newPrice = input.value;
            const indicator = document.getElementById(`save-${id}`);
            
            if (!newPrice) return;

            try {
                const response = await fetch('api_estoque.php?action=atualizar_preco', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, preco: newPrice })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mostrar indicador de salvo
                    indicator.classList.add('visible');
                    setTimeout(() => {
                        indicator.classList.remove('visible');
                    }, 2000);
                    input.style.borderColor = '#28a745'; // Verde momentâneo
                    setTimeout(() => input.style.borderColor = '#ddd', 1000);
                } else {
                    alert('Erro ao salvar preço: ' + (data.message || 'Erro desconhecido'));
                    input.style.borderColor = '#dc3545'; // Vermelho
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro de conexão ao salvar preço.');
            }
        }

        async function toggleOption(id, type, checkbox) {
            const isActive = checkbox.checked;
            const action = type === 'promocao' ? 'toggle_promocao' : 'toggle_destaque';

            try {
                const response = await fetch(`api_estoque.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, ativo: isActive })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    checkbox.checked = !isActive;
                    alert('Erro ao atualizar: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                checkbox.checked = !isActive;
                alert('Erro de conexão ao atualizar.');
            }
        }

        async function toggleStatus(id, checkbox) {
            const isActive = checkbox.checked;
            const item = checkbox.closest('.product-item');
            
            // Optimistic UI: Já atualizamos o atributo de dados para o filtro funcionar imediatamente
            item.setAttribute('data-active', isActive ? '1' : '0');

            try {
                const response = await fetch('api_estoque.php?action=toggle_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, ativo: isActive })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    // Reverter em caso de erro
                    checkbox.checked = !isActive;
                    item.setAttribute('data-active', !isActive ? '1' : '0');
                    alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                // Reverter
                checkbox.checked = !isActive;
                item.setAttribute('data-active', !isActive ? '1' : '0');
                alert('Erro de conexão ao atualizar status.');
            }
        }
    </script>
</body>
</html>
