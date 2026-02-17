<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';
require_once 'evolution_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos recebidos');
    }
    
    // Validar token da farmácia
    if (!isset($input['token']) || empty($input['token'])) {
        throw new Exception('Token da farmácia não fornecido');
    }
    
    // Buscar farmácia pelo token
    $stmt = $pdo->prepare("SELECT * FROM farmacias WHERE qr_code_token = ?");
    $stmt->execute([$input['token']]);
    $farmacia = $stmt->fetch();
    
    if (!$farmacia) {
        throw new Exception('Farmácia não encontrada');
    }
    
    // Validar dados do cliente
    $dadosObrigatorios = ['nome', 'telefone', 'endereco', 'numero', 'bairro', 'pagamento'];
    foreach ($dadosObrigatorios as $campo) {
        if (!isset($input[$campo]) || empty(trim($input[$campo]))) {
            throw new Exception("Campo obrigatório '$campo' não fornecido");
        }
    }
    
    // Validar itens do carrinho
    if (!isset($input['itens']) || empty($input['itens'])) {
        throw new Exception('Nenhum item no carrinho');
    }
    
    // Registrar pedido no banco
    $pedido_id = registrarPedidoBanco($pdo, $farmacia['id'], $input);
    
    if (!$pedido_id) {
        throw new Exception('Erro ao salvar pedido no banco de dados');
    }
    
    // Preparar mensagem para WhatsApp (usar a mesma lógica do cardápio atual)
    $mensagem = formatarMensagemPedido($input, $farmacia);
    
    // Enviar via Evolution API
    $resultado = enviarPedidoWhatsApp($farmacia['whatsapp'], $mensagem, $input);
    
    if ($resultado['success']) {
        // Atualizar status do pedido
        $stmt = $pdo->prepare("UPDATE pedidos_whatsapp SET status_pedido = 'confirmado' WHERE id = ?");
        $stmt->execute([$pedido_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido enviado com sucesso!',
            'pedido_id' => $pedido_id
        ]);
    } else {
        // Marcar pedido como erro
        $stmt = $pdo->prepare("UPDATE pedidos_whatsapp SET status_pedido = 'cancelado' WHERE id = ?");
        $stmt->execute([$pedido_id]);
        
        throw new Exception('Erro ao enviar pedido: ' . $resultado['message']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function formatarMensagemPedido($dados, $farmacia) {
    // Usar a mesma formatação que já existe no JavaScript do cardápio
    // Mas agora processada no servidor
    $mensagem = "🏥 *NOVO PEDIDO - FARMÁCIA* 🏥\n";
    $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $agora = new DateTime();
    $mensagem .= "📅 *Data:* " . $agora->format('d/m/Y') . "\n";
    $mensagem .= "🕒 *Horário:* " . $agora->format('H:i') . "\n";
    $mensagem .= "📱 *Via:* Catálogo Digital\n\n";
    
    // Dados do cliente
    $mensagem .= "👤 *DADOS DO CLIENTE*\n";
    $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
    $mensagem .= "📋 *Nome:* " . $dados['nome'] . "\n";
    $mensagem .= "📞 *Telefone:* " . $dados['telefone'] . "\n\n";
    
    // Endereço
    $mensagem .= "🏠 *ENDEREÇO DE ENTREGA*\n";
    $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
    $mensagem .= "📍 *Endereço:* " . $dados['endereco'] . ", " . $dados['numero'] . "\n";
    if (!empty($dados['complemento'])) {
        $mensagem .= "🏢 *Complemento:* " . $dados['complemento'] . "\n";
    }
    $mensagem .= "🏘️ *Bairro:* " . $dados['bairro'] . "\n\n";
    
    // Produtos
    $mensagem .= "🛒 *PRODUTOS SOLICITADOS*\n";
    $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
    
    foreach ($dados['itens'] as $index => $item) {
        $numero = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        $itemSubtotal = $item['preco'] * $item['quantidade'];
        
        $mensagem .= "\n" . $numero . "️⃣ *" . $item['nome'] . "*\n";
        $mensagem .= "    📦 Quantidade: " . $item['quantidade'] . " unidade" . ($item['quantidade'] > 1 ? 's' : '') . "\n";
        $mensagem .= "    💰 Valor unitário: R$ " . number_format($item['preco'], 2, ',', '.') . "\n";
        $mensagem .= "    💵 Subtotal: R$ " . number_format($itemSubtotal, 2, ',', '.') . "\n";
        $mensagem .= "    ─────────────────────────────\n";
    }
    
    // Resumo financeiro
    $subtotal = array_sum(array_map(function($item) {
        return $item['preco'] * $item['quantidade'];
    }, $dados['itens']));
    
    $mensagem .= "\n💰 *RESUMO FINANCEIRO*\n";
    $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
    $mensagem .= "🛍️ *Subtotal dos produtos:* R$ " . number_format($subtotal, 2, ',', '.') . "\n";
    
    // Calcular entrega
    $custoEntrega = 0;
    if (!empty($farmacia['taxa_entrega']) && !empty($farmacia['valor_entrega_gratis'])) {
        if ($subtotal >= $farmacia['valor_entrega_gratis']) {
            $mensagem .= "🚚 *Entrega:* GRÁTIS ✅\n";
            $mensagem .= "    _(Pedido acima de R$ " . number_format($farmacia['valor_entrega_gratis'], 2, ',', '.') . ")_\n";
        } else {
            $custoEntrega = $farmacia['taxa_entrega'];
            $mensagem .= "🚚 *Taxa de entrega:* R$ " . number_format($custoEntrega, 2, ',', '.') . "\n";
            $mensagem .= "    _(Grátis a partir de R$ " . number_format($farmacia['valor_entrega_gratis'], 2, ',', '.') . ")_\n";
        }
    }
    
    $total = $subtotal + $custoEntrega;
    $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $mensagem .= "🏆 *TOTAL GERAL: R$ " . number_format($total, 2, ',', '.') . "* 🏆\n";
    $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Forma de pagamento
    $mensagem .= "💳 *FORMA DE PAGAMENTO*\n";
    $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
    $formasPagamento = [
        'dinheiro' => '💵 Dinheiro',
        'cartao_debito' => '💳 Cartão de Débito',
        'cartao_credito' => '💳 Cartão de Crédito', 
        'pix' => '📱 PIX',
        'transferencia' => '🏦 Transferência Bancária'
    ];
    $mensagem .= $formasPagamento[$dados['pagamento']] . "\n";
    
    if ($dados['pagamento'] === 'dinheiro' && !empty($dados['troco'])) {
        $mensagem .= "💰 *Troco para:* " . $dados['troco'] . "\n";
    }
    $mensagem .= "\n";
    
    // Observações
    if (!empty($dados['observacoes'])) {
        $mensagem .= "📝 *OBSERVAÇÕES*\n";
        $mensagem .= "▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪▪\n";
        $mensagem .= $dados['observacoes'] . "\n\n";
    }
    
    $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $mensagem .= "⏰ *EM QUANTO TEMPO ESTARÁ*\n";
    $mensagem .= "*DISPONÍVEL PARA ENTREGA?*\n";
    $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $mensagem .= "✨ _Obrigado pela preferência!_\n";
    $mensagem .= "🤖 _Pedido gerado automaticamente pelo catálogo digital_";
    
    return $mensagem;
}
?>