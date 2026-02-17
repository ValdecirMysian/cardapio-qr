# Manual Técnico de Integração - API Mediz v1.1
*Última atualização: 2026-01-23*

## 1. Visão Geral
A API Mediz permite que sistemas de gestão (ERPs/PDVs) sincronizem **Preço** e **Estoque** com o Cardápio Digital em tempo real.

O fluxo é unidirecional: **ERP -> Mediz**.

---

## 2. Dados de Acesso
Para integrar, você precisará apenas de:
1. **URL do Endpoint**: `https://mediz.digital/entrar/cardapio-qr/api_v1.php`
2. **Token da Farmácia**: Disponível no painel do cliente, em "Configurações".

---

## 3. Teste de Conectividade (Ping)
Antes de enviar dados, você pode testar se a API está online:

**Endpoint**: `GET https://mediz.digital/entrar/cardapio-qr/api_v1.php?action=ping`
**Resposta Esperada**: `{"status": "online", "time": "..."}`

---

## 4. Métodos de Envio Suportados
Devido a variações em firewalls corporativos, a API suporta dois métodos de envio. Recomendamos tentar o Método A primeiro.

### Método A: POST JSON (Padrão REST)
Ideal para sistemas modernos. Envie um payload JSON no corpo da requisição.

**Endpoint**: `POST https://mediz.digital/entrar/cardapio-qr/api_v1.php`
**Headers**: `Content-Type: application/json`

**Corpo da Requisição:**
```json
{
  "token": "TOKEN_DA_FARMACIA_AQUI",
  "produtos": [
    {
      "ean": "7891234567890",
      "preco": 19.90,
      "estoque": 50
    },
    {
      "ean": "7899876543210",
      "preco": 45.50,
      "estoque": 0
    }
  ]
}
```

### Método B: GET Query String (Fallback / Firewall Friendly)
Use este método se o seu ERP tiver restrições de firewall ou bloqueio de saída POST.

**Endpoint**: `GET https://mediz.digital/entrar/cardapio-qr/api_v1.php`

**Parâmetros na URL:**
Para atualizar um único produto de forma simples:
```
?token=SEU_TOKEN&ean=7891234567890&preco=19.90&estoque=10
```

---

## 5. Glossário de Campos

| Campo | Tipo | Obrigatório? | Descrição |
|-------|------|--------------|-----------|
| `token` | String | **Sim** | Token de autenticação único da farmácia. |
| `ean` | String | **Sim*** | Código de Barras (GTIN/EAN-13). *Pode usar `sku_externo` se não tiver EAN. |
| `sku_externo` | String | Não | Código interno do produto no seu ERP. |
| `preco` | Float | **Sim** | Preço de venda final (ex: `19.90`). Use ponto como separador decimal. Valores negativos serão rejeitados. |
| `estoque` | Int | **Sim** | Quantidade atual. Se `0`, o produto é ocultado do cardápio. |

---

## 6. Códigos de Retorno (HTTP Status)

- **200 OK**: Sucesso. O JSON conterá `"updated": X` e `"not_found_or_identical": [...]`.
- **400 Bad Request**: Erro na estrutura dos dados (JSON inválido ou campos faltando).
- **401 Unauthorized**: Token inválido ou não fornecido.
- **429 Too Many Requests**: Limite de taxa excedido (100 req/min por IP).

---

## 7. Exemplos de Implementação

### Delphi 7 / XE (Usando Indy)
Para sistemas legados que não suportam JSON nativo ou TLS 1.2, recomendamos usar uma biblioteca externa como SuperObject.

```delphi
uses IdHTTP, IdSSLOpenSSL, SuperObject; // Requer DLLs OpenSSL na pasta

function AtualizarEstoqueMediz(Token: String; EAN: String; Preco: Double; Estoque: Integer): Boolean;
var
  HTTP: TIdHTTP;
  SSL: TIdSSLIOHandlerSocketOpenSSL;
  JSON, Resposta: String;
  Stream: TStringStream;
begin
  Result := False;
  HTTP := TIdHTTP.Create(nil);
  SSL := TIdSSLIOHandlerSocketOpenSSL.Create(nil);
  try
    // Configurar SSL para TLS 1.2 (se suportado pelas DLLs)
    SSL.SSLOptions.Method := sslvTLSv1_2; 
    HTTP.IOHandler := SSL;
    HTTP.Request.ContentType := 'application/json';
    
    // Monta JSON manualmente (compatível com Delphi 7)
    JSON := '{"token":"' + Token + '","produtos":[{' +
            '"ean":"' + EAN + '",' +
            '"preco":' + FormatFloat('0.00', Preco) + ',' +
            '"estoque":' + IntToStr(Estoque) + '}]}';
    
    Stream := TStringStream.Create(JSON);
    try
      Resposta := HTTP.Post('https://mediz.digital/entrar/cardapio-qr/api_v1.php', Stream);
      Result := Pos('"updated":1', Resposta) > 0;
    except
      on E: Exception do
      begin
        // Fallback para GET se o POST falhar (Erro SSL ou Firewall)
        try
           Resposta := HTTP.Get('https://mediz.digital/entrar/cardapio-qr/api_v1.php?token=' + Token + 
                                '&ean=' + EAN + 
                                '&preco=' + FormatFloat('0.00', Preco) + 
                                '&estoque=' + IntToStr(Estoque));
           Result := Pos('"updated":1', Resposta) > 0;
        except
        end;
      end;
    end;
    Stream.Free;
  finally
    SSL.Free;
    HTTP.Free;
  end;
end;
```

### C# (.NET / Windows Forms)
```csharp
using System.Net.Http;
using System.Text;
using Newtonsoft.Json;

public async Task<bool> AtualizarEstoque(string token, string ean, decimal preco, int estoque)
{
    var url = "https://mediz.digital/entrar/cardapio-qr/api_v1.php";
    var payload = new
    {
        token = token,
        produtos = new[] {
            new { ean = ean, preco = preco, estoque = estoque }
        }
    };

    using (var client = new HttpClient())
    {
        var json = JsonConvert.SerializeObject(payload);
        var content = new StringContent(json, Encoding.UTF8, "application/json");
        
        try 
        {
            var response = await client.PostAsync(url, content);
            return response.IsSuccessStatusCode;
        }
        catch 
        {
            // Fallback para GET
            var getUrl = $"{url}?token={token}&ean={ean}&preco={preco}&estoque={estoque}";
            var response = await client.GetAsync(getUrl);
            return response.IsSuccessStatusCode;
        }
    }
}
```

---

## 8. Suporte e Logs
O lojista tem acesso a um painel de **"Logs de Integração"** onde pode ver em tempo real se as requisições estão chegando e qual foi o resultado. Use isso para debugar sua integração.
