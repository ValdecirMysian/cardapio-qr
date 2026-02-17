<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

// --- CONFIGURAÇÕES ---
$RATE_LIMIT = 100; // Requisições por minuto
$RATE_LIMIT_TIME = 60; // Segundos

// Variáveis de resposta
$response = [
    "message" => "",
    "updated" => 0,
    "not_found" => [],
    "errors" => []
];
$status_code = 200;

// Função auxiliar de resposta
function sendResponse($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

// 1. ENDPOINT DE PING (Para teste de conectividade)
if (isset($_GET['action']) && $_GET['action'] === 'ping') {
    sendResponse(200, ["status" => "online", "time" => date('c')]);
}

// 2. RATE LIMITING (Segurança Básica)
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Limpar logs antigos (opcional, para não inchar o banco)
    // $pdo->query("DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_logs WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$ip, $RATE_LIMIT_TIME]);
    $request_count = $stmt->fetchColumn();

    if ($request_count > $RATE_LIMIT) {
        sendResponse(429, ["message" => "Muitas requisicoes. Tente novamente em 1 minuto."]);
    }
} catch (Exception $e) {
    // Falha silenciosa no rate limit para não parar a API
}

// 3. CAPTURA DE DADOS (Híbrido GET/POST)
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Fallback para GET via Query String
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $data = ['token' => $_GET['token'], 'produtos' => []];
    if (isset($_GET['produtos'])) {
        $prods = json_decode($_GET['produtos'], true);
        if (is_array($prods)) $data['produtos'] = $prods;
    } elseif (isset($_GET['ean']) || isset($_GET['sku_externo'])) {
        $data['produtos'][] = [
            'ean' => $_GET['ean'] ?? null,
            'sku_externo' => $_GET['sku_externo'] ?? null,
            'preco' => $_GET['preco'] ?? 0,
            'estoque' => $_GET['estoque'] ?? 0
        ];
    }
}

// Fallback para POST Form-Data
if (!$data && !empty($_POST)) {
    $data = $_POST;
    if (isset($data['json'])) {
        $decoded = json_decode($data['json'], true);
        if ($decoded) $data = $decoded;
    }
}

if (!$data) {
    sendResponse(400, ["message" => "Dados invalidos ou ausentes."]);
}

// 4. AUTENTICAÇÃO
if (empty($data['token'])) {
    sendResponse(401, ["message" => "Token nao fornecido."]);
}

$stmt = $pdo->prepare("SELECT id FROM farmacias WHERE qr_code_token = ?");
$stmt->execute([$data['token']]);
$farmacia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$farmacia) {
    sendResponse(401, ["message" => "Token invalido."]);
}

$farmacia_id = $farmacia['id'];
$produtos = $data['produtos'] ?? [];

if (empty($produtos)) {
    sendResponse(200, ["message" => "Nenhum produto para atualizar.", "updated" => 0]);
}

// 5. PROCESSAMENTO DE PRODUTOS
$updated_count = 0;
$not_found = [];
$errors = [];

foreach ($produtos as $index => $item) {
    // Validação de Preço Negativo
    $preco = isset($item['preco']) ? floatval($item['preco']) : 0.0;
    if ($preco < 0) {
        $errors[] = "Item #$index: Preco negativo nao permitido.";
        continue;
    }

    $estoque_qtd = isset($item['estoque']) ? intval($item['estoque']) : 0;
    $estoque_disponivel = ($estoque_qtd > 0) ? 1 : 0;
    
    $ean = $item['ean'] ?? null;
    $sku = $item['sku_externo'] ?? null;

    if (empty($ean) && empty($sku)) {
        $errors[] = "Item #$index: EAN ou SKU ausente.";
        continue;
    }

    // Construção Dinâmica da Query (Resolve o problema do NULL)
    $conditions = [];
    $params = [$preco, $estoque_disponivel];
    
    // Prioridade de busca: Se enviou EAN, busca por EAN. Se enviou SKU, busca por SKU.
    $where_parts = [];
    
    if (!empty($ean)) {
        $where_parts[] = "ean = ?";
        $params[] = $ean;
    }
    if (!empty($sku)) {
        $where_parts[] = "sku_externo = ?";
        $params[] = $sku;
    }

    $params[] = $farmacia_id;
    
    $sql = "UPDATE produtos SET preco = ?, estoque_disponivel = ? 
            WHERE (" . implode(" OR ", $where_parts) . ") AND farmacia_id = ?";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $updated_count++;
        } else {
            // Se não atualizou nada, verifica se o produto existe
            // Isso diferencia "Produto não encontrado" de "Produto já estava atualizado"
            // Para simplificar e performance, vamos assumir como not_found se rowCount = 0
            // Mas idealmente faríamos um SELECT antes. 
            // Como rowCount retorna 0 se os valores forem IGUAIS, vamos adicionar ao not_found
            // apenas se quisermos ser estritos.
            // O cliente pediu "not_found", então vamos adicionar.
            // Nota: MySQL retorna 0 se os dados forem idênticos.
            $not_found[] = $ean ?: $sku;
        }
    } catch (PDOException $e) {
        $errors[] = "Erro DB Item " . ($ean ?: $sku) . ": " . $e->getMessage();
    }
}

// 6. LOGGING (Assíncrono na medida do possível)
try {
    $logSql = "INSERT INTO api_logs (farmacia_id, endpoint, method, request_data, response_data, status_code, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $response_payload = [
        "message" => "Processamento concluido.",
        "updated" => $updated_count,
        "not_found_or_identical" => $not_found, // Nome mais honesto pois rowCount=0 pode ser dados iguais
        "errors" => $errors
    ];
    
    $pdo->prepare($logSql)->execute([
        $farmacia_id, 'api_v1.php', $_SERVER['REQUEST_METHOD'], 
        substr($raw_input, 0, 1000), // Limitar tamanho
        json_encode($response_payload), 200, $_SERVER['REMOTE_ADDR'] ?? null
    ]);
} catch (Exception $e) {}

sendResponse(200, $response_payload);
?>
