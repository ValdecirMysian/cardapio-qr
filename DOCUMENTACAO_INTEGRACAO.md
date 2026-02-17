# Manual de Integração - API de Estoque Mediz

## Visão Geral
Esta API permite a sincronização de estoque e preços entre o sistema ERP da farmácia e o Catálogo Digital Mediz. O objetivo é manter os dados atualizados em tempo real.

## Autenticação
A autenticação é feita através do **Token da Farmácia**. Este token é único para cada estabelecimento e deve ser fornecido pelo administrador do sistema Mediz ao responsável pela integração.

## Endpoint
**URL Base**: `https://mediz.digital/cardapio-qr/api_v1.php`
**Método**: `POST`
**Content-Type**: `application/json`

## Estrutura da Requisição
O corpo da requisição deve ser um objeto JSON contendo:
- `token`: String (Obrigatório) - Token de autenticação da farmácia.
- `produtos`: Array de objetos (Obrigatório) - Lista de produtos a atualizar.

Cada objeto em `produtos` deve conter:
- `ean`: String (Opcional se sku_externo for enviado) - Código de barras EAN-13.
- `sku_externo`: String (Opcional se ean for enviado) - Código interno do ERP.
- `preco`: Number (Float) - Preço de venda atual.
- `estoque`: Number (Integer) - Quantidade em estoque.

**Regra de Negócio**: 
1. O sistema busca o produto pelo `ean`. 
2. Se não encontrar, busca pelo `sku_externo`. 
3. Se o estoque for `0`, o produto ficará indisponível no catálogo.

### Exemplo de JSON de Envio
```json
{
  "token": "SEU_TOKEN_AQUI",
  "produtos": [
    {
      "ean": "7891010101010",
      "preco": 19.90,
      "estoque": 50
    },
    {
      "sku_externo": "PROD-1234",
      "preco": 45.50,
      "estoque": 0
    },
    {
      "ean": "7892020202020",
      "sku_externo": "PROD-5678",
      "preco": 12.00,
      "estoque": 10
    }
  ]
}
```

## Estrutura da Resposta
A API retornará um JSON indicando o sucesso da operação.

### Sucesso (200 OK)
```json
{
  "message": "Processamento concluído.",
  "updated": 2,
  "errors": []
}
```

### Erro de Validação (400 Bad Request)
```json
{
  "message": "Dados inválidos ou ausentes."
}
```

### Erro de Autenticação (401 Unauthorized)
```json
{
  "message": "Token inválido."
}
```

## Recomendações para Integração
1. **Gatilho**: Configure seu ERP para enviar os dados sempre que houver alteração de estoque ou preço.
2. **Logs**: O sistema Mediz mantém logs de todas as requisições para fins de auditoria.
