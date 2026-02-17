<?php
/**
 * evolution_config.php
 * Configurações da Evolution API para sistema de cardápio QR
 */

// Configurações da Evolution API
define('EVOLUTION_API_URL', 'https://evolution.probotfarmapro.online');
define('EVOLUTION_API_KEY', 'CCC739B01549-49D1-9410-146E6D111A5E');
define('EVOLUTION_INSTANCE_NAME', 'cardapio_farmapro');

/**
 * Enviar pedido via WhatsApp através da Evolution API - Dinâmico por farmácia
 */
function enviarPedidoWhatsApp($numeroDestino, $mensagemPedido, $configFarmacia) {
    try {
        $url = $configFarmacia['evolution_api_url'] . '/message/sendText/' . $configFarmacia['evolution_instance_name'];
        
        $payload = [
            'number' => $numeroDestino,
            'text' => $mensagemPedido
        ];
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $configFarmacia['evolution_api_key']
        ];
        
        $payload = [
            'number' => $numeroDestino,
            'text' => $mensagemPedido
        ];
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . EVOLUTION_API_KEY
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'Pedido enviado com sucesso via Evolution API',
                'http_code' => $httpCode,
                'response' => json_decode($response, true)
            ];
        } else {
            throw new Exception('Erro HTTP ' . $httpCode . ': ' . $response);
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Registrar pedido no banco de dados
 */
function registrarPedidoBanco($pdo, $farmacia_id, $dadosPedido) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pedidos_whatsapp 
            (farmacia_id, cliente_nome, cliente_telefone, endereco_entrega, 
             itens_pedido, valor_total, forma_pagamento, observacoes, 
             status_pedido, data_criacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
        ");
        
        $endereco_completo = $dadosPedido['endereco'] . ', ' . $dadosPedido['numero'];
        if (!empty($dadosPedido['complemento'])) {
            $endereco_completo .= ', ' . $dadosPedido['complemento'];
        }
        $endereco_completo .= ' - ' . $dadosPedido['bairro'];
        
        $stmt->execute([
            $farmacia_id,
            $dadosPedido['nome'],
            $dadosPedido['telefone'],
            $endereco_completo,
            json_encode($dadosPedido['itens']),
            $dadosPedido['total'],
            $dadosPedido['pagamento'],
            $dadosPedido['observacoes'] ?? ''
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log('Erro ao registrar pedido: ' . $e->getMessage());
        return false;
    }
}
?>