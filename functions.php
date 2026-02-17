<?php
/**
 * Funções auxiliares para o sistema de cardápio QR Code
 */

function verificarLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function obterFarmacia($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM farmacias WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $farmacia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$farmacia) {
            try {
                $token = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("INSERT INTO farmacias (usuario_id, nome, qr_code_token) VALUES (?, ?, ?)");
                $stmt->execute([$usuario_id, 'Minha Farmácia', $token]);
                
                $stmt = $pdo->prepare("SELECT * FROM farmacias WHERE usuario_id = ?");
                $stmt->execute([$usuario_id]);
                $farmacia = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Erro ao criar farmácia para usuario $usuario_id: " . $e->getMessage());
                return false;
            }
        }
        
        return $farmacia;
    } catch (Exception $e) {
        error_log("Erro geral em obterFarmacia: " . $e->getMessage());
        return false;
    }
}

function obterCategorias($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nome");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtém todos os tamanhos disponíveis
 */
function obterTamanhos($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM tamanhos WHERE ativo = TRUE ORDER BY ordem_exibicao, nome");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtém os tamanhos de um produto específico
 */
function obterTamanhosProduto($pdo, $produto_id) {
    $stmt = $pdo->prepare("
        SELECT t.*, pt.preco_adicional, pt.estoque_disponivel as tamanho_disponivel
        FROM tamanhos t
        JOIN produto_tamanhos pt ON t.id = pt.tamanho_id
        WHERE pt.produto_id = ? AND t.ativo = TRUE
        ORDER BY t.ordem_exibicao, t.nome
    ");
    $stmt->execute([$produto_id]);
    return $stmt->fetchAll();
}

/**
 * Salva os tamanhos selecionados para um produto
 */
function salvarTamanhosProduto($pdo, $produto_id, $tamanhos_selecionados, $precos_adicionais = []) {
    try {
        // Primeiro, remove todos os tamanhos existentes do produto
        $stmt = $pdo->prepare("DELETE FROM produto_tamanhos WHERE produto_id = ?");
        $stmt->execute([$produto_id]);
        
        // Depois, adiciona os novos tamanhos
        if (!empty($tamanhos_selecionados)) {
            $stmt = $pdo->prepare("INSERT INTO produto_tamanhos (produto_id, tamanho_id, preco_adicional) VALUES (?, ?, ?)");
            
            foreach ($tamanhos_selecionados as $tamanho_id) {
                $preco_adicional = isset($precos_adicionais[$tamanho_id]) ? $precos_adicionais[$tamanho_id] : 0.00;
                $stmt->execute([$produto_id, $tamanho_id, $preco_adicional]);
            }
            
            // Atualiza o produto para indicar que tem tamanhos
            $stmt = $pdo->prepare("UPDATE produtos SET tem_tamanhos = TRUE WHERE id = ?");
            $stmt->execute([$produto_id]);
        } else {
            // Se não há tamanhos, marca como FALSE
            $stmt = $pdo->prepare("UPDATE produtos SET tem_tamanhos = FALSE WHERE id = ?");
            $stmt->execute([$produto_id]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao salvar tamanhos do produto: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém todos os produtos de uma farmácia
 * 
 * @param PDO $pdo Conexão com o banco de dados
 * @param int $farmacia_id ID da farmácia
 * @return array Lista de produtos
 */
function obterProdutos($pdo, $farmacia_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.farmacia_id = ? 
        ORDER BY c.nome, p.nome
    ");
    $stmt->execute([$farmacia_id]);
    return $stmt->fetchAll();
}

/**
 * Obtém um produto específico
 * 
 * @param PDO $pdo Conexão com o banco de dados
 * @param int $produto_id ID do produto
 * @param int $farmacia_id ID da farmácia para verificação de segurança
 * @return array|false Dados do produto ou false se não encontrado
 */
function obterProduto($pdo, $produto_id, $farmacia_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.id = ? AND p.farmacia_id = ?
    ");
    $stmt->execute([$produto_id, $farmacia_id]);
    return $stmt->fetch();
}

/**
 * Agrupa produtos por categoria
 * 
 * @param array $produtos Lista de produtos
 * @return array Produtos agrupados por categoria
 */
function agruparProdutosPorCategoria($produtos) {
    $produtos_por_categoria = [];
    foreach ($produtos as $produto) {
        $categoria = $produto['categoria_nome'];
        if (!isset($produtos_por_categoria[$categoria])) {
            $produtos_por_categoria[$categoria] = [];
        }
        $produtos_por_categoria[$categoria][] = $produto;
    }
    return $produtos_por_categoria;
}

/**
 * Faz upload de uma imagem e retorna o caminho
 * 
 * @param array $arquivo Dados do arquivo do $_FILES
 * @return string|null Caminho da imagem ou null se ocorrer erro
 */
function fazerUploadImagem($arquivo) {
    // Retorna null se não houver arquivo ou ocorrer erro
    if (!isset($arquivo) || $arquivo['error'] != 0) {
        return null;
    }
    
    // Define o diretório de upload
    $diretorio_upload = 'uploads/';
    
    // Cria o diretório se não existir
    if (!file_exists($diretorio_upload)) {
        mkdir($diretorio_upload, 0777, true);
    }
    
    // Gera um nome único para a imagem
    $nome_arquivo = time() . '_' . basename($arquivo['name']);
    $caminho_destino = $diretorio_upload . $nome_arquivo;
    
    // Move o arquivo para o diretório de upload
    if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
        return $caminho_destino;
    }
    
    return null;
}

/**
 * Exclui uma imagem do servidor
 * 
 * @param string $caminho Caminho da imagem
 * @return bool Retorna true se a imagem foi excluída com sucesso
 */
function excluirImagem($caminho) {
    if (!empty($caminho) && file_exists($caminho)) {
        return unlink($caminho);
    }
    return false;
}

/**
 * Gera a URL completa do QR Code para acesso ao cardápio
 * 
 * @param string $token Token único da farmácia
 * @return string URL completa
 */
function gerarUrlQrCode($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Remove filename if present (shouldn't be returned by dirname but just in case of weird server configs)
    $scriptDir = rtrim($scriptDir, '/');
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $scriptDir . "/cardapio.php?token=" . $token;
}

/**
 * Retorna a URL da imagem do QR Code usando Google Charts API
 * 
 * @param string $url URL a ser codificada no QR Code
 * @param int $tamanho Tamanho do QR Code em pixels
 * @return string URL da imagem do QR Code
 */
function gerarQrCodeUrl($url, $tamanho = 300) {
    // Use a API QR Code do Google Charts com parâmetros adicionais
    return "https://chart.googleapis.com/chart?cht=qr&chs={$tamanho}x{$tamanho}&chl=" . urlencode($url) . "&choe=UTF-8&chld=H|0";
}

/**
 * Verifica se o produto exige receita baseado na tarja
 * 
 * @param string $tarja Tarja do medicamento
 * @return bool True se exige receita, false caso contrário
 */
function verificarExigeReceita($tarja) {
    return $tarja == 'vermelha' || $tarja == 'preta';
}

/**
 * Verifica se o produto pode ser exibido no cardápio público baseado na tarja
 * 
 * @param string $tarja Tarja do medicamento
 * @return bool True se pode ser mostrado, false caso contrário
 */
function verificarPodeSerMostrado($tarja) {
    // Medicamentos de tarja preta não podem ser mostrados no cardápio
    return $tarja != 'preta';
}

/**
 * Obtém o texto da tarja para exibição
 * 
 * @param string $tarja Tarja do medicamento
 * @return string Texto formatado da tarja
 */
function obterTextoTarja($tarja) {
    switch ($tarja) {
        case 'sem_tarja':
            return 'Medicamento de Venda Livre';
        case 'amarela':
            return 'Medicamento Genérico';
        case 'vermelha':
            return 'Venda Sob Prescrição (Tarja Vermelha)';
        case 'preta':
            return 'Medicamento Controlado (Tarja Preta)';
        default:
            return 'Tarja não especificada';
    }
}

/**
 * Obtém a cor CSS da tarja para estilização
 * 
 * @param string $tarja Tarja do medicamento
 * @return string Classe CSS correspondente
 */
function obterCorTarja($tarja) {
    switch ($tarja) {
        case 'sem_tarja':
            return 'secondary';
        case 'amarela':
            return 'warning';
        case 'vermelha':
            return 'danger';
        case 'preta':
            return 'dark';
        default:
            return 'light';
    }
}

/**
 * Valida os campos específicos de medicamentos
 * 
 * @param array $dados Dados do produto
 * @return array Array associativo com erros, se houver
 */
function validarCamposMedicamento($dados) {
    $erros = [];
    
    // Se for selecionada uma tarja, o princípio ativo é obrigatório
    if (isset($dados['tarja']) && $dados['tarja'] != 'sem_tarja' && empty($dados['principio_ativo'])) {
        $erros[] = 'O princípio ativo é obrigatório para medicamentos tarjados.';
    }
    
    // Medicamentos de tarja vermelha e preta exigem receita
    if (isset($dados['tarja']) && verificarExigeReceita($dados['tarja']) && empty($dados['exige_receita'])) {
        $erros[] = 'Medicamentos de tarja vermelha ou preta sempre exigem receita.';
    }
    
    // Medicamentos de tarja preta não podem ser mostrados no cardápio
    if (isset($dados['tarja']) && $dados['tarja'] == 'preta' && !empty($dados['mostrar_no_cardapio'])) {
        $erros[] = 'Medicamentos de tarja preta não podem ser mostrados no cardápio público.';
    }
    
    return $erros;
}