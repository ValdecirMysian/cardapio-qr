<?php
// Arquivo de teste rápido para validar o Motor de Categorização

require_once 'classes/MotorCategorizacao.php';

echo "<h1>🧪 Teste do Motor de Categorização (V1)</h1>";
echo "<pre>";

$motor = new MotorCategorizacao();

// Casos de Teste
$casosTeste = [
    // Camada 1: Grupo CSV (Já testado)
    ['nome' => 'Dipirona', 'grupo_csv' => 'ANALGESICOS'],
    
    // Camada 2: Keywords (Grupo vazio ou inválido)
    ['nome' => 'DIPIRONA SODICA 500MG', 'grupo_csv' => ''], // Deve achar por princípio ativo
    ['nome' => 'FRALDA PAMPERS GIGA', 'grupo_csv' => 'OUTROS'], // Deve achar por keyword + marca
    ['nome' => 'SHAMPOO DOVE RECONSTRUCAO', 'grupo_csv' => 'PERFUMARIA_GERAL'], // Deve achar por keyword
    ['nome' => 'TYLENOL 750MG', 'grupo_csv' => ''], // Deve achar por marca
    ['nome' => 'CREME DENTAL COLGATE', 'grupo_csv' => ''], // Deve achar por keyword + marca
    
    // Casos Negativos (Fallback)
    ['nome' => 'PRODUTO DESCONHECIDO XYZ 500MG', 'grupo_csv' => ''], // Fallback Dosagem
    ['nome' => 'KIT PRESENTE NATAL', 'grupo_csv' => ''], // Fallback Kit
    ['nome' => 'COISA ALEATORIA SEM SENTIDO', 'grupo_csv' => ''], // Fallback Padrão
];

foreach ($casosTeste as $caso) {
    echo "--------------------------------------------------\n";
    echo "📥 Input: " . json_encode($caso) . "\n";
    
    $start = microtime(true);
    $resultado = $motor->categorizarProduto($caso);
    $end = microtime(true);
    $tempo = round(($end - $start) * 1000, 2); // ms

    if ($resultado) {
        $cor = ($resultado['confianca'] >= 0.7) ? 'green' : (($resultado['confianca'] >= 0.3) ? 'orange' : 'red');
        
        echo "✅ <strong style='color:$cor'>CATEGORIZADO</strong> ({$tempo}ms)\n";
        echo "   Categoria: " . $resultado['categoria'] . "\n";
        echo "   Confiança: " . $resultado['confianca'] . "\n";
        echo "   Método: " . $resultado['metodo'] . "\n";
        if (isset($resultado['requer_revisao']) && $resultado['requer_revisao']) {
            echo "   ⚠️ <strong style='color:red'>REQUER REVISÃO HUMANA</strong>\n";
        }
    } else {
        echo "❌ <strong style='color:red'>ERRO FATAL</strong> (Não deveria acontecer com fallback)\n";
    }
}

echo "--------------------------------------------------\n";
echo "</pre>";
