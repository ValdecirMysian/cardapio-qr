<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisão de Categorização - Inteligência Farmácia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card-produto { transition: all 0.3s; border-left: 5px solid transparent; }
        .card-produto:hover { transform: translateY(-2px); shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .confianca-alta { border-left-color: #28a745; }
        .confianca-media { border-left-color: #ffc107; }
        .confianca-baixa { border-left-color: #dc3545; }
        .badge-metodo { font-size: 0.8em; opacity: 0.8; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-robot text-primary"></i> Supervisão da IA</h1>
            <p class="text-muted small">Ensine o sistema corrigindo as falhas de categorização.</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-secondary active" onclick="filtrar('todos')">Todos</button>
            <button class="btn btn-outline-danger" onclick="filtrar('revisao')">⚠️ Requer Revisão</button>
        </div>
    </div>

    <!-- Filtros e Status -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle fs-4 me-3"></i>
                <div>
                    <strong>Como funciona:</strong> Ao corrigir um produto abaixo, o sistema pode 
                    <strong>aprender</strong> o padrão (ex: vincular o Grupo CSV à nova categoria) 
                    para não errar nas próximas importações.
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div id="lista-produtos" class="row g-3">
        <!-- Preenchido via JS -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Carregando produtos...</p>
        </div>
    </div>
</div>

<!-- Modal de Correção -->
<div class="modal fade" id="modalCorrecao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Corrigir Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="produto_id">
                <input type="hidden" id="produto_grupo_csv">
                
                <p>Produto: <strong id="produto_nome"></strong></p>
                <p class="text-muted small">Grupo CSV Original: <span id="display_grupo"></span></p>

                <div class="mb-3">
                    <label class="form-label">Selecione a Categoria Correta:</label>
                    <select class="form-select" id="select_categoria" size="10">
                        <!-- Options via JS -->
                    </select>
                </div>

                <div class="form-check bg-light p-3 rounded border">
                    <input class="form-check-input" type="checkbox" id="check_aprender" checked>
                    <label class="form-check-label" for="check_aprender">
                        <strong>Ensinar o sistema?</strong>
                        <div class="small text-muted">Se marcado, o sistema vai vincular o grupo CSV a essa categoria para sempre.</div>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarCorrecao()">Salvar e Aprender</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let produtos = [];
    let taxonomia = {};
    const modalCorrecao = new bootstrap.Modal(document.getElementById('modalCorrecao'));

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        carregarTaxonomia();
        carregarProdutos();
    });

    async function carregarTaxonomia() {
        const res = await fetch('api_revisao.php?action=taxonomia');
        const json = await res.json();
        taxonomia = json.data;
        popularSelectCategorias();
    }

    async function carregarProdutos() {
        const res = await fetch('api_revisao.php?action=listar');
        const json = await res.json();
        produtos = json.data;
        renderizarProdutos(produtos);
    }

    function popularSelectCategorias() {
        const select = document.getElementById('select_categoria');
        select.innerHTML = '';

        for (const [catKey, catData] of Object.entries(taxonomia)) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = catData.label;

            for (const [subKey, subData] of Object.entries(catData.subcategorias)) {
                const option = document.createElement('option');
                option.value = `${catKey}/${subKey}`;
                option.textContent = subData.label;
                optgroup.appendChild(option);
            }
            select.appendChild(optgroup);
        }
        // Adiciona Outros
        const optOutros = document.createElement('optgroup');
        optOutros.label = "Outros";
        const op = document.createElement('option');
        op.value = "outros/outros";
        op.textContent = "Outros / Não Identificado";
        optOutros.appendChild(op);
        select.appendChild(optOutros);
    }

    function renderizarProdutos(lista) {
        const container = document.getElementById('lista-produtos');
        container.innerHTML = '';

        lista.forEach(p => {
            const confiancaClass = p.confianca >= 0.7 ? 'confianca-alta' : (p.confianca >= 0.3 ? 'confianca-media' : 'confianca-baixa');
            const badgeClass = p.confianca >= 0.7 ? 'bg-success' : (p.confianca >= 0.3 ? 'bg-warning text-dark' : 'bg-danger');
            
            const html = `
                <div class="col-md-6 col-lg-4">
                    <div class="card card-produto ${confiancaClass} h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge ${badgeClass}">${Math.round(p.confianca * 100)}% Confiança</span>
                                <span class="badge bg-light text-dark border badge-metodo">${p.metodo}</span>
                            </div>
                            <h5 class="card-title text-truncate" title="${p.nome}">${p.nome}</h5>
                            <p class="card-text small text-muted mb-1">Grupo CSV: <strong>${p.grupo_csv || '-'}</strong></p>
                            
                            <div class="alert alert-secondary py-2 mt-3 mb-3">
                                <small>Categoria Atual:</small><br>
                                <strong>${p.categoria_atual}</strong>
                            </div>

                            <div class="d-grid gap-2">
                                ${p.requer_revisao ? 
                                    `<button class="btn btn-danger btn-sm" onclick="abrirModal(${p.id})">
                                        <i class="fas fa-exclamation-triangle"></i> Revisar Agora
                                    </button>` : 
                                    `<button class="btn btn-outline-primary btn-sm" onclick="abrirModal(${p.id})">
                                        <i class="fas fa-edit"></i> Alterar
                                    </button>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    function abrirModal(id) {
        const p = produtos.find(item => item.id === id);
        document.getElementById('produto_id').value = p.id;
        document.getElementById('produto_grupo_csv').value = p.grupo_csv;
        document.getElementById('produto_nome').textContent = p.nome;
        document.getElementById('display_grupo').textContent = p.grupo_csv || '(Vazio)';
        
        // Seleciona a categoria atual no select
        document.getElementById('select_categoria').value = p.categoria_atual;

        // Se não tem grupo CSV, desabilita a opção de "aprender" pois não tem o que mapear
        document.getElementById('check_aprender').disabled = !p.grupo_csv;
        document.getElementById('check_aprender').checked = !!p.grupo_csv;

        modalCorrecao.show();
    }

    async function salvarCorrecao() {
        const id = document.getElementById('produto_id').value;
        const novaCategoria = document.getElementById('select_categoria').value;
        const grupoCsv = document.getElementById('produto_grupo_csv').value;
        const aprender = document.getElementById('check_aprender').checked;

        const payload = {
            id: id,
            nova_categoria: novaCategoria,
            grupo_csv: aprender ? grupoCsv : null // Só manda o grupo se for pra aprender
        };

        const res = await fetch('api_revisao.php?action=salvar_correcao', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message + '\n' + json.aprendizado);
            modalCorrecao.hide();
            // Recarrega a lista (simulada)
            // Na vida real recarregaria do banco. Aqui vou atualizar o array local só pra ver o efeito
            const p = produtos.find(item => item.id == id);
            p.categoria_atual = novaCategoria;
            p.requer_revisao = false;
            p.confianca = 1.0; // Revisado por humano = 100%
            p.metodo = 'manual_revisado';
            renderizarProdutos(produtos);
        } else {
            alert('Erro: ' + json.message);
        }
    }

    function filtrar(tipo) {
        if (tipo === 'todos') {
            renderizarProdutos(produtos);
        } else {
            renderizarProdutos(produtos.filter(p => p.requer_revisao));
        }
    }
</script>

</body>
</html>
