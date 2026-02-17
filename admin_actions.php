<?php
// ARQUIVO DE DIAGNÓSTICO E DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px;'>";
echo "<h1>🕵️ MODO DE DEPURAÇÃO ATIVO</h1>";

// 1. Teste de Conexão
if (!file_exists('../config/database.php')) {
    die("<h2 style='color:red'>ERRO FATAL: Arquivo database.php não encontrado!</h2>");
}
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once __DIR__ . '/config/database.php';
}
echo "<p>✅ Conexão com banco incluída.</p>";

// 2. Teste de Sessão
if (!isset($_SESSION['user_id'])) {
    die("<h2 style='color:red'>ERRO: Usuário não logado (Sessão vazia).</h2>");
}
echo "<p>✅ Usuário Logado ID: " . $_SESSION['user_id'] . "</p>";

// 3. Verificar se recebeu dados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<h2 style='color:red'>ERRO: Acesso direto detectado. O formulário não enviou POST.</h2>");
}

echo "<h3>📦 Dados Recebidos (POST):</h3>";
echo "<pre style='background: #fff; border: 1px solid #ccc; padding: 10px;'>";
print_r($_POST);
echo "</pre>";

if (empty($_POST)) {
    die("<h2 style='color:red'>ERRO CRÍTICO: O array POST está vazio! Verifique se a tag &lt;form&gt; fecha antes dos inputs ou se o botão está fora do form.</h2>");
}

// 4. Tentar Executar a Query
if (isset($_POST['acao']) && $_POST['acao'] == 'adicionar_produto') {
    echo "<h3>⚙️ Tentando inserir no Banco...</h3>";
    
    try {
        // Preparando dados básicos
        $nome = $_POST['nome'] ?? 'Sem Nome';
        $categoria_id = $_POST['categoria'] ?? 1;
        $preco = $_POST['preco'] ?? 0;
        
        // Simulação dos campos (mesmos do index_old)
        // ATENÇÃO: Se o banco reclamar de coluna faltando, vai aparecer aqui!
        $sql = "INSERT INTO produtos 
            (farmacia_id, categoria_id, nome, descricao, principio_ativo, indicacao, contra_indicacao, 
            tarja, exige_receita, mostrar_no_cardapio, preco, promocao, destaque, estoque_disponivel, is_leite, tem_tamanhos, registro_ms) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        // Valores simulados para teste
        $params = [
            $farmacia['id'] ?? 1, // Farmacia ID (Pego da sessão ou fixo pra teste)
            $categoria_id,
            $nome,
            $_POST['descricao'] ?? '',
            $_POST['principio_ativo'] ?? '',
            $_POST['indicacao'] ?? '',
            $_POST['contra_indicacao'] ?? '',
            $_POST['tarja'] ?? 'sem_tarja',
            isset($_POST['exige_receita']) ? 1 : 0,
            isset($_POST['mostrar_no_cardapio']) ? 1 : 0,
            $preco,
            isset($_POST['promocao']) ? 1 : 0,
            isset($_POST['destaque']) ? 1 : 0,
            isset($_POST['estoque_disponivel']) ? 1 : 0,
            isset($_POST['is_leite']) ? 1 : 0,
            isset($_POST['tem_tamanhos']) ? 1 : 0,
            $_POST['registro_ms'] ?? ''
        ];

        // Tenta preparar
        $stmt = $pdo->prepare($sql);
        
        // Tenta executar
        if($stmt->execute($params)) {
            echo "<h1 style='color:green'>✅ SUCESSO ABSOLUTO!</h1>";
            echo "<p>O produto foi inserido. O problema original era apenas o redirecionamento (header location).</p>";
            echo "<p>Agora você pode voltar o código original, sabendo que o banco está OK.</p>";
        } else {
            echo "<h1 style='color:red'>❌ ERRO AO EXECUTAR!</h1>";
            print_r($stmt->errorInfo());
        }

    } catch (PDOException $e) {
        echo "<h1 style='color:red'>❌ ERRO FATAL (PDO Exception):</h1>";
        echo "<h3>" . $e->getMessage() . "</h3>";
        echo "<p>Isso geralmente indica que o nome de uma coluna no código está diferente do banco.</p>";
    }
} else {
    echo "<h3>⚠️ Ação não é 'adicionar_produto'. Ação recebida: " . ($_POST['acao'] ?? 'VAZIO') . "</h3>";
    echo "<p>Verifique o campo hidden no seu index.php: &lt;input type='hidden' name='acao' value='adicionar_produto'&gt;</p>";
}

echo "</div>";
die(); // Encerra aqui para você ver a tela
?>