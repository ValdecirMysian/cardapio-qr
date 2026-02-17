<?php
// API SIMULADA PARA REVISÃO DE PRODUTOS
// Em um sistema real, isso buscaria do banco de dados

require_once 'classes/MotorCategorizacao.php';

header('Content-Type: application/json');

$motor = new MotorCategorizacao();
$action = $_GET['action'] ?? '';

if ($action === 'listar') {
    // Simula produtos vindos do banco
    // Em produção: SELECT * FROM produtos WHERE requer_revisao = 1
    $produtosSimulados = [
        ['id' => 1, 'nome' => 'DIPIRONA SODICA 500MG', 'grupo_csv' => '', 'categoria_atual' => 'medicamentos/outros', 'confianca' => 0.3, 'metodo' => 'fallback_dosagem', 'requer_revisao' => true],
        ['id' => 2, 'nome' => 'KIT PRESENTE NATAL', 'grupo_csv' => '', 'categoria_atual' => 'perfumaria/kits', 'confianca' => 0.4, 'metodo' => 'fallback_kit', 'requer_revisao' => true],
        ['id' => 3, 'nome' => 'COISA ALEATORIA', 'grupo_csv' => 'GRUPO_ESTRANHO', 'categoria_atual' => 'outros/outros', 'confianca' => 0.1, 'metodo' => 'fallback_padrao', 'requer_revisao' => true],
        ['id' => 4, 'nome' => 'FRALDA PAMPERS M', 'grupo_csv' => 'HIGIENE', 'categoria_atual' => 'higiene/oral', 'confianca' => 0.95, 'metodo' => 'mapeamento_csv_direto', 'requer_revisao' => false], // Exemplo de falso positivo que usuário pode querer corrigir
    ];

    echo json_encode(['success' => true, 'data' => $produtosSimulados]);

} elseif ($action === 'taxonomia') {
    echo json_encode(['success' => true, 'data' => $motor->getTaxonomia()]);

} elseif ($action === 'salvar_correcao') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input['id'] || !$input['nova_categoria']) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }

    // 1. Atualizaria no Banco de Dados (UPDATE produtos SET categoria = ...)
    // ...

    // 2. Retroalimentação (Aprendizado)
    $aprendeu = false;
    if (!empty($input['grupo_csv'])) {
        $aprendeu = $motor->aprenderCorrecao([
            'grupo_csv' => $input['grupo_csv'],
            'categoria_correta' => $input['nova_categoria']
        ]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Produto corrigido com sucesso!',
        'aprendizado' => $aprendeu ? 'Sistema aprendeu o novo padrão.' : 'Correção pontual salva.'
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
