<?php

class MotorCategorizacao {
    private $taxonomia;
    private $mapeamento;
    private $basePath;

    public function __construct() {
        $this->basePath = __DIR__ . '/../data/';
        $this->carregarDados();
    }

    private function carregarDados() {
        // Carrega Taxonomia
        $taxonomiaFile = $this->basePath . 'taxonomia-farmacia.json';
        if (file_exists($taxonomiaFile)) {
            $this->taxonomia = json_decode(file_get_contents($taxonomiaFile), true);
        } else {
            $this->taxonomia = [];
            error_log("AVISO: Arquivo de taxonomia não encontrado em $taxonomiaFile");
        }

        // Carrega Mapeamento CSV
        $mapeamentoFile = $this->basePath . 'mapeamento-linx.json';
        if (file_exists($mapeamentoFile)) {
            $this->mapeamento = json_decode(file_get_contents($mapeamentoFile), true);
        } else {
            $this->mapeamento = [];
            error_log("AVISO: Arquivo de mapeamento não encontrado em $mapeamentoFile");
        }
    }

    /**
     * Normaliza uma string para comparação (remove acentos, uppercase, trim)
     */
    private function normalizarString($str) {
        if (!$str) return '';
        
        $str = mb_strtoupper($str, 'UTF-8');
        $str = preg_replace('/[ÁÀÃÂÄ]/u', 'A', $str);
        $str = preg_replace('/[ÉÈÊË]/u', 'E', $str);
        $str = preg_replace('/[ÍÌÎÏ]/u', 'I', $str);
        $str = preg_replace('/[ÓÒÕÔÖ]/u', 'O', $str);
        $str = preg_replace('/[ÚÙÛÜ]/u', 'U', $str);
        $str = preg_replace('/[Ç]/u', 'C', $str);
        $str = preg_replace('/[^A-Z0-9\s]/', '', $str);
        $str = trim($str);
        
        return $str;
    }

    /**
     * CAMADA 1: Mapeamento Direto por Grupo CSV
     * Retorna apenas a categoria principal (sem subcategoria)
     */
    public function categorizarPorGrupo($grupoCsv) {
        if (empty($grupoCsv)) {
            return null;
        }

        $grupoNormalizado = $this->normalizarString($grupoCsv);

        // Tenta encontrar correspondência exata no mapeamento
        if (isset($this->mapeamento[$grupoNormalizado])) {
            $categoria = $this->mapeamento[$grupoNormalizado];
            
            return [
                'categoria' => $categoria,
                'categoria_principal' => $categoria,
                'subcategoria' => null,
                'confianca' => 0.95,
                'metodo' => 'mapeamento_csv_direto',
                'origem' => $grupoCsv
            ];
        }

        return null;
    }

    /**
     * CAMADA 2: Matching por Keywords no Nome
     * Retorna apenas a categoria principal (sem subcategoria)
     */
    public function categorizarPorNome($nomeProduto) {
        if (empty($nomeProduto)) {
            return null;
        }

        $nomeNormalizado = $this->normalizarString($nomeProduto);
        $tokens = explode(' ', $nomeNormalizado);
        
        $melhorCategoria = null;
        $maiorScore = 0;

        // Percorre toda a taxonomia procurando matches
        foreach ($this->taxonomia as $catKey => $catData) {
            $score = 0;
            $matches = [];

            // 1. Verifica Keywords
            if (isset($catData['keywords'])) {
                foreach ($catData['keywords'] as $keyword) {
                    $keywordNorm = $this->normalizarString($keyword);
                    
                    // Keyword pode ter múltiplas palavras (ex: "protetor solar")
                    $keywordTokens = explode(' ', $keywordNorm);
                    
                    if (count($keywordTokens) == 1) {
                        // Keyword de palavra única
                        if (in_array($keywordNorm, $tokens)) {
                            $score += 0.4;
                            $matches[] = $keyword;
                        }
                    } else {
                        // Keyword de múltiplas palavras
                        if (strpos($nomeNormalizado, $keywordNorm) !== false) {
                            $score += 0.5; // Peso maior para match de frase completa
                            $matches[] = $keyword;
                        }
                    }
                }
            }

            // 2. Verifica Marcas
            if (isset($catData['marcas'])) {
                foreach ($catData['marcas'] as $marca) {
                    $marcaNorm = $this->normalizarString($marca);
                    
                    // Marca pode ter múltiplas palavras (ex: "johnson baby")
                    if (strpos($nomeNormalizado, $marcaNorm) !== false) {
                        $score += 0.3;
                        $matches[] = $marca;
                    }
                }
            }

            // 3. Verifica Princípios Ativos (se houver)
            if (isset($catData['principios_ativos'])) {
                foreach ($catData['principios_ativos'] as $ativo) {
                    $ativoNorm = $this->normalizarString($ativo);
                    if (strpos($nomeNormalizado, $ativoNorm) !== false) {
                        $score += 0.5;
                        $matches[] = $ativo;
                    }
                }
            }

            // 4. Verifica Sufixos (se houver - útil para antibióticos)
            if (isset($catData['sufixos'])) {
                foreach ($catData['sufixos'] as $sufixo) {
                    $sufixoNorm = $this->normalizarString($sufixo);
                    // Sufixo deve aparecer no final de alguma palavra
                    foreach ($tokens as $token) {
                        if (substr($token, -strlen($sufixoNorm)) === $sufixoNorm) {
                            $score += 0.3;
                            $matches[] = $sufixo;
                            break;
                        }
                    }
                }
            }

            // Normaliza o score (max 1.0)
            $score = min($score, 1.0);

            if ($score > $maiorScore) {
                $maiorScore = $score;
                $melhorCategoria = [
                    'categoria' => $catKey,
                    'categoria_principal' => $catKey,
                    'subcategoria' => null,
                    'confianca' => $score,
                    'metodo' => 'keywords_nome',
                    'matches' => $matches
                ];
            }
        }

        return $melhorCategoria;
    }

    /**
     * CAMADA 5: Fallback Heurístico
     * Tenta adivinhar com base em padrões genéricos
     */
    public function fallbackHeuristico($dadosProduto) {
        $nome = $this->normalizarString($dadosProduto['nome'] ?? '');
        
        // Regra A: Se tem dosagem farmacêutica clara -> Medicamentos
        // Mas exclui cosméticos e suplementos
        if (preg_match('/[0-9]+(MG|MCG|ML|G|CPR|CPS|COMP|CP|CAPS|DRG)\b/', $nome)) {
            
            // Exceções que NÃO são medicamentos
            $excecoes = ['SHAMPOO', 'CONDICIONADOR', 'SABONETE', 'CREME', 'GEL', 'LOCAO', 
                        'SERUM', 'PROTETOR', 'HIDRATANTE', 'WHEY', 'PROTEINA', 'CREATINA'];
            
            $ehExcecao = false;
            foreach ($excecoes as $exc) {
                if (strpos($nome, $exc) !== false) {
                    $ehExcecao = true;
                    break;
                }
            }
            
            if (!$ehExcecao) {
                return [
                    'categoria' => 'medicamentos',
                    'categoria_principal' => 'medicamentos',
                    'subcategoria' => null,
                    'confianca' => 0.4,
                    'metodo' => 'fallback_dosagem',
                    'requer_revisao' => true
                ];
            }
        }

        // Regra B: Fralda por tamanho
        if (preg_match('/\b(RN|RECEM NASCIDO)\b/', $nome) || 
            preg_match('/FR\s+(RN|P|M|G|XG|XXG)/', $nome)) {
            return [
                'categoria' => 'fraldas_higiene',
                'categoria_principal' => 'fraldas_higiene',
                'subcategoria' => null,
                'confianca' => 0.5,
                'metodo' => 'fallback_fralda',
                'requer_revisao' => true
            ];
        }

        // Regra C: Genérico - vai para categoria mais provável baseada em palavras-chave básicas
        $categoriasBasicas = [
            'medicamentos' => ['MEDICAMENTO', 'REMEDIO', 'GENERICO', 'SIMILAR'],
            'higiene' => ['HIGIENE', 'BANHO', 'DENTAL', 'BUCAL'],
            'suplementos' => ['SUPLEMENTO', 'VITAMINA'],
            'perfumaria' => ['PERFUME', 'COLONIA', 'BATOM']
        ];
        
        foreach ($categoriasBasicas as $cat => $palavras) {
            foreach ($palavras as $palavra) {
                if (strpos($nome, $palavra) !== false) {
                    return [
                        'categoria' => $cat,
                        'categoria_principal' => $cat,
                        'subcategoria' => null,
                        'confianca' => 0.3,
                        'metodo' => 'fallback_palavra_chave',
                        'requer_revisao' => true
                    ];
                }
            }
        }

        // Padrão absoluto: Não conseguiu classificar
        return [
            'categoria' => 'outros',
            'categoria_principal' => 'outros',
            'subcategoria' => null,
            'confianca' => 0.1,
            'metodo' => 'fallback_padrao',
            'requer_revisao' => true
        ];
    }

    /**
     * Obtém toda a taxonomia para uso em dropdowns
     */
    public function getTaxonomia() {
        return $this->taxonomia;
    }

    /**
     * Aprende uma nova correção (Retroalimentação)
     */
    public function aprenderCorrecao($dados) {
        // Se a correção foi baseada em Grupo CSV
        if (!empty($dados['grupo_csv']) && !empty($dados['categoria_correta'])) {
            $grupoNorm = $this->normalizarString($dados['grupo_csv']);
            
            // Atualiza o mapeamento em memória
            $this->mapeamento[$grupoNorm] = $dados['categoria_correta'];
            
            // Salva no arquivo JSON
            $mapeamentoFile = $this->basePath . 'mapeamento-linx.json';
            file_put_contents($mapeamentoFile, json_encode($this->mapeamento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return true;
        }
        
        // Futuro: Aprender também keywords novas para a taxonomia
        
        return false;
    }

    /**
     * Função principal pública para categorizar
     */
    public function categorizarProduto($dadosProduto) {
        $resultado = null;

        // 1. Tenta pelo Grupo CSV (Camada 1 - Confiança Alta)
        if (!empty($dadosProduto['grupo_csv'])) {
            $resultado = $this->categorizarPorGrupo($dadosProduto['grupo_csv']);
            if ($resultado && $resultado['confianca'] >= 0.9) {
                return $resultado;
            }
        }

        // 2. Tenta pelo Nome (Camada 2 - Confiança Média/Alta)
        if (!empty($dadosProduto['nome'])) {
            $resultadoNome = $this->categorizarPorNome($dadosProduto['nome']);
            if ($resultadoNome && $resultadoNome['confianca'] >= 0.4) {
                return $resultadoNome;
            }
        }

        // 3. (Futuro) API Externa por EAN
        // 4. (Futuro) ML

        // 5. Fallback Heurístico (Camada 5 - Confiança Baixa)
        return $this->fallbackHeuristico($dadosProduto);
    }
}