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
        $str = preg_replace('/[^A-Z0-9\s]/', '', $str); // Remove caracteres especiais
        $str = trim($str);
        
        return $str;
    }

    /**
     * CAMADA 1: Mapeamento Direto por Grupo CSV
     */
    public function categorizarPorGrupo($grupoCsv) {
        if (empty($grupoCsv)) {
            return null;
        }

        $grupoNormalizado = $this->normalizarString($grupoCsv);

        // Tenta encontrar correspondência exata no mapeamento
        if (isset($this->mapeamento[$grupoNormalizado])) {
            $caminhoCategoria = $this->mapeamento[$grupoNormalizado];
            $partes = explode('/', $caminhoCategoria);
            
            return [
                'categoria' => $caminhoCategoria,
                'categoria_principal' => $partes[0] ?? null,
                'subcategoria' => $partes[1] ?? null,
                'confianca' => 0.95,
                'metodo' => 'mapeamento_csv_direto',
                'origem' => $grupoCsv
            ];
        }

        return null;
    }

    /**
     * CAMADA 2: Matching por Keywords no Nome
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
            if (!isset($catData['subcategorias'])) continue;

            foreach ($catData['subcategorias'] as $subKey => $subData) {
                $score = 0;
                $matches = [];

                // 1. Verifica Keywords
                if (isset($subData['keywords'])) {
                    foreach ($subData['keywords'] as $keyword) {
                        $keywordNorm = $this->normalizarString($keyword);
                        // Verifica se a keyword existe como palavra inteira nos tokens
                        if (in_array($keywordNorm, $tokens)) {
                            $score += 0.4; // Peso alto para keyword exata
                            $matches[] = $keyword;
                        }
                    }
                }

                // 2. Verifica Marcas
                if (isset($subData['marcas'])) {
                    foreach ($subData['marcas'] as $marca) {
                        $marcaNorm = $this->normalizarString($marca);
                        if (in_array($marcaNorm, $tokens)) {
                            $score += 0.3; // Peso médio para marca
                            $matches[] = $marca;
                        }
                    }
                }

                // 3. Verifica Princípios Ativos (se houver)
                if (isset($subData['principios_ativos'])) {
                    foreach ($subData['principios_ativos'] as $ativo) {
                        $ativoNorm = $this->normalizarString($ativo);
                        // Princípio ativo pode ser composto (ex: "DIPIRONA SODICA")
                        if (strpos($nomeNormalizado, $ativoNorm) !== false) {
                            $score += 0.5; // Peso muito alto para princípio ativo
                            $matches[] = $ativo;
                        }
                    }
                }

                // Normaliza o score (max 1.0)
                $score = min($score, 1.0);

                if ($score > $maiorScore) {
                    $maiorScore = $score;
                    $melhorCategoria = [
                        'categoria' => "$catKey/$subKey",
                        'categoria_principal' => $catKey,
                        'subcategoria' => $subKey,
                        'confianca' => $score,
                        'metodo' => 'keywords_nome',
                        'matches' => $matches
                    ];
                }
            }
        }

        return $melhorCategoria;
    }

    /**
     * CAMADA 5: Fallback Heurístico
     * Tenta adivinhar com base em padrões genéricos ou define "Outros"
     */
    public function fallbackHeuristico($dadosProduto) {
        $nome = $this->normalizarString($dadosProduto['nome'] ?? '');
        
        // Regra A: Se tem dosagem (MG/ML/G) + Números -> Provavelmente Medicamento
        // MAS precisamos ter cuidado com suplementos e dermocosméticos que também usam isso.
        // Vamos restringir para formas farmacêuticas claras.
        if (preg_match('/[0-9]+(MG|ML|G|CPR|CPS|COMP|CP|CAPS|DRG)/', $nome)) {
            
            // Exceções claras que NÃO são medicamentos
            if (strpos($nome, 'SHAMPOO') !== false || strpos($nome, 'CONDICIONADOR') !== false || strpos($nome, 'SABONETE') !== false || strpos($nome, 'CREME') !== false || strpos($nome, 'GEL') !== false || strpos($nome, 'LOCAO') !== false || strpos($nome, 'SERUM') !== false || strpos($nome, 'PROTETOR') !== false) {
                 // Deixa passar para o próximo fallback ou define como cosmético
            } else {
                return [
                    'categoria' => 'medicamentos/outros',
                    'categoria_principal' => 'medicamentos',
                    'subcategoria' => 'outros',
                    'confianca' => 0.3,
                    'metodo' => 'fallback_dosagem',
                    'requer_revisao' => true
                ];
            }
        }

        // Regra B: Se tem "KIT" ou "PRESENTE" -> Perfumaria
        if (strpos($nome, 'KIT') !== false || strpos($nome, 'PRESENTE') !== false) {
            return [
                'categoria' => 'perfumaria/kits',
                'categoria_principal' => 'perfumaria',
                'subcategoria' => 'kits',
                'confianca' => 0.4,
                'metodo' => 'fallback_kit',
                'requer_revisao' => true
            ];
        }

        // Regra C: Padrão Genérico
        return [
            'categoria' => 'outros/outros',
            'categoria_principal' => 'outros',
            'subcategoria' => 'outros',
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

        // 3. (Futuro) API Externa
        // 4. (Futuro) ML

        // 5. Fallback Heurístico (Camada 5 - Confiança Baixa)
        return $this->fallbackHeuristico($dadosProduto);
    }
}
