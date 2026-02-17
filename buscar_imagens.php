<?php
session_start();
require_once 'config/database.php';
require_once 'functions.php';

verificarLogin();

$mensagem = '';

// Processar Upload Manual ou URL
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['produto_id'];
    $url = $_POST['imagem_url'] ?? '';
    
    if (isset($_FILES['imagem_file']) && $_FILES['imagem_file']['error'] == 0) {
        // Upload de Arquivo
        $ext = pathinfo($_FILES['imagem_file']['name'], PATHINFO_EXTENSION);
        $novoNome = "uploads/" . time() . "_" . $id . "." . $ext;
        if (move_uploaded_file($_FILES['imagem_file']['tmp_name'], $novoNome)) {
            $stmt = $pdo->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
            $stmt->execute([$novoNome, $id]);
            $mensagem = "Imagem atualizada com sucesso!";
        }
    } elseif (!empty($url)) {
        // Download de URL
        $conteudo = @file_get_contents($url);
        if ($conteudo) {
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            if (!$ext) $ext = 'jpg'; // Default
            // Limpar query strings da extensão
            $ext = explode('?', $ext)[0];
            
            $novoNome = "uploads/" . time() . "_" . $id . "." . $ext;
            file_put_contents($novoNome, $conteudo);
            
            $stmt = $pdo->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
            $stmt->execute([$novoNome, $id]);
            $mensagem = "Imagem baixada com sucesso!";
        } else {
            $mensagem = "Erro ao baixar imagem da URL.";
        }
    }
}

// Processar Busca Automática (AJAX)
if (isset($_GET['acao']) && $_GET['acao'] == 'buscar_auto') {
    // Desabilitar exibição de erros no output JSON
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    
    // Limpar qualquer output anterior
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Type: application/json');
    
    try {
        if (!isset($_GET['ean']) || empty($_GET['ean'])) {
            throw new Exception('EAN não fornecido');
        }
        
        $ean = $_GET['ean'];
        
        // Tentativa 1: Open Food Facts (Gratuito)
        $url = "https://world.openfoodfacts.org/api/v0/product/" . $ean . ".json";
        
        // Configuração de Contexto SSL para evitar problemas com certificados
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
            "http" => array(
                "header" => "User-Agent: CardapioDigital/1.0\r\n",
                "ignore_errors" => true, // Ignorar erros 404/403 para não quebrar o script
                "timeout" => 5 // Timeout para não travar
            )
        );
        
        $response = @file_get_contents($url, false, stream_context_create($arrContextOptions));
        
        if ($response === false) {
            // Se file_get_contents falhar, tenta cURL
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'CardapioDigital/1.0');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);
            }
        }
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['product']['image_url'])) {
                echo json_encode(['sucesso' => true, 'url' => $data['product']['image_url'], 'fonte' => 'OpenFoodFacts']);
                exit;
            }
        }
        
        // Tentativa 2: Open Beauty Facts (Cosméticos)
        $url = "https://world.openbeautyfacts.org/api/v0/product/" . $ean . ".json";
        $response = @file_get_contents($url, false, stream_context_create($arrContextOptions));
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['product']['image_url'])) {
                echo json_encode(['sucesso' => true, 'url' => $data['product']['image_url'], 'fonte' => 'OpenBeautyFacts']);
                exit;
            }
        }

        echo json_encode(['sucesso' => false, 'msg' => 'Imagem não encontrada nas bases públicas.']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'msg' => 'Erro interno: ' . $e->getMessage()]);
    }
    exit;
}

// Listar produtos sem imagem
$stmt = $pdo->query("SELECT * FROM produtos WHERE imagem IS NULL OR imagem = '' ORDER BY id DESC LIMIT 50");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Gestão de Imagens</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-camera text-primary"></i> Gestor de Imagens</h2>
        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-info"><?= $mensagem ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Produtos sem Imagem (Últimos 50)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>EAN</th>
                            <th>Ações de Busca</th>
                            <th>Upload / URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $prod): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($prod['nome']) ?></strong><br>
                                <small class="text-muted">Cat: ID <?= $prod['categoria_id'] ?></small>
                            </td>
                            <td><?= $prod['ean'] ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="https://www.google.com/search?tbm=isch&q=<?= urlencode($prod['ean'] . ' ' . $prod['nome']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fab fa-google"></i> Google
                                    </a>
                                    <?php if ($prod['ean']): ?>
                                    <button onclick="buscarAuto('<?= $prod['ean'] ?>', <?= $prod['id'] ?>)" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-magic"></i> Auto
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div id="status-<?= $prod['id'] ?>" class="small mt-1 text-muted"></div>
                            </td>
                            <td>
                                <form action="" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                    <input type="hidden" name="produto_id" value="<?= $prod['id'] ?>">
                                    <input type="text" name="imagem_url" id="url-<?= $prod['id'] ?>" class="form-control form-control-sm" placeholder="Cole URL da imagem" style="width: 150px;">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function buscarAuto(ean, id) {
    const statusDiv = document.getElementById('status-' + id);
    const urlInput = document.getElementById('url-' + id);
    
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    
    // Ajuste para chamar sem .php se o servidor reescreve
    fetch('buscar_imagens?acao=buscar_auto&ean=' + ean)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Achou! (' + data.fonte + ')</span>';
                urlInput.value = data.url;
                // Opcional: Auto-submit
                // urlInput.form.submit();
            } else {
                statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> Não encontrado.</span>';
            }
        })
        .catch(err => {
            statusDiv.innerHTML = '<span class="text-danger">Erro na busca.</span>';
        });
}
</script>

</body>
</html>
