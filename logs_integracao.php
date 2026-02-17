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

// Limpar logs antigos (opcional, ex: manter apenas últimos 30 dias ou 1000 registros)
// Aqui vamos apenas listar os últimos 100
$stmt = $pdo->prepare("
    SELECT * FROM api_logs 
    WHERE farmacia_id = ? 
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->execute([$farmacia['id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Integração - <?php echo htmlspecialchars($farmacia['nome']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7B68EE;
            --primary-dark: #6A5ACD;
        }
        body { background-color: #f8f9fa; }
        .navbar { background-color: var(--primary); }
        .navbar-brand, .nav-link { color: white !important; }
        .log-details { font-family: monospace; font-size: 0.85rem; background: #f1f1f1; padding: 10px; border-radius: 5px; }
        .status-badge { width: 80px; text-align: center; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-qrcode me-2"></i> Cardápio QR
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-history me-2 text-primary"></i> Logs de Integração</h2>
                <p class="text-muted">Histórico das últimas atualizações via API</p>
            </div>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Atualizar
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Data/Hora</th>
                                <th scope="col">Status</th>
                                <th scope="col">Resumo</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-exchange-alt fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">Nenhum registro de integração encontrado.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): 
                                    $request = json_decode($log['request_data'], true);
                                    $response = json_decode($log['response_data'], true);
                                    $statusClass = ($log['status_code'] >= 200 && $log['status_code'] < 300) ? 'bg-success' : 'bg-danger';
                                    
                                    // Resumo inteligente
                                    $resumo = "Requisição recebida";
                                    if (isset($response['updated'])) {
                                        $resumo = $response['updated'] . " itens atualizados";
                                    } elseif (isset($response['message'])) {
                                        $resumo = substr($response['message'], 0, 50);
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?> status-badge">
                                            <?php echo $log['status_code']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($resumo); ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#log-<?php echo $log['id']; ?>">
                                            <i class="fas fa-eye"></i> Detalhes
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="log-<?php echo $log['id']; ?>">
                                    <td colspan="4" class="bg-light p-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Entrada (Request):</strong>
                                                <pre class="log-details mt-1"><?php echo htmlspecialchars(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Saída (Response):</strong>
                                                <pre class="log-details mt-1"><?php echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
