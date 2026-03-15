# Integração ERP (IIS/Windows) ↔ Portal (Linux)

Visão completa das integrações: **o que o ERP chama no Portal** (APIs HTTP) e **o que o Portal chama no ERP** (serviços SOAP no IIS).

**Infraestrutura:** Portal em **10.0.2.25**, PostgreSQL em **10.0.2.23**, ERP/Grid em **10.0.2.7** (ECS-MASTER). Ver `docs/INFRAESTRUTURA_SERVIDORES.md`.

**Base URL do Portal (com subdiretório):** `https://portal.pgm.inf.br/portal`  
**Base URL do ERP (IIS no 10.0.2.7):** configurada no Portal em **Empresas → URL ERP**, ex.: `http://10.0.2.7:85/WebGridPGM/`.

---

## Parte 1: APIs do Portal chamadas pelo ERP (Integrador)

O Integrador/ERP no Windows faz requisições **HTTP** para o Portal. Todas exigem:

- **Headers:** `empresa` (ID da empresa no Portal), `token` (token da empresa no Portal).
- **Content-Type:** `application/json` quando houver body.

As respostas de erro/sucesso incluem `mensagem` e `retorno` (mesmo texto), para o Integrador exibir em "Retorno:".

---

### 1.1 Produtos

| Item | Valor |
|------|--------|
| **Cadastrar/atualizar** | `POST /produtos/add-api` ou `POST /produtos/addAPI` |
| **Listar** | `GET /produtos/list-api` ou `GET /produtos/listAPI` |

**URL completa (ex.):** `https://portal.pgm.inf.br/portal/produtos/add-api`

**add-api (POST)**  
- Body JSON: `codigo` (obrigatório), opcional: `descricao`, `unidade`, `vlunitario`, `tipo`, `ativo`.  
- Resposta: `{"mensagem": "...", "retorno": "..."}` (201 sucesso, 400/401/500 erro).

**list-api (GET)**  
- Headers: `empresa`, `token`, opcional `codigo` (para um produto).  
- Resposta: array de produtos (200) ou mensagem de erro.

---

### 1.2 Clientes e contratos

Clientes e **contratos (serviços)** são enviados juntos em uma única API.

| Item | Valor |
|------|--------|
| **Cadastrar/atualizar cliente + contratos** | `POST /clientes/add-api` ou `POST /clientes/addAPI` |
| **Listar clientes** | `GET /clientes/list-api` ou `GET /clientes/listAPI` |

**URL completa (ex.):** `https://portal.pgm.inf.br/portal/clientes/add-api`

**add-api (POST)**  
- Body JSON (ex.):  
  `cnpj`, `nome`, `inscest`, `endereco`, `nroendereco`, `complemento`, `bairro`, `cep`, `codibge` (cidade), `telefone`, `celular`, `email`, `contrato`, `fantasia`, e array **`Servicos`** (contratos): cada item com `idERP`, `codproduto`, `descricao`, `infadicional`, `vlunit`, `qtde`, `vltotal`, `dtcontratacao`, `dtvalidade`, `dtcancelamento`.  
- Resposta: `{"mensagem": "...", "retorno": "..."}` (201 sucesso, 400/401 erro).

**list-api (GET)**  
- Headers: `empresa`, `token`, opcional `cnpj`.  
- Resposta: array de clientes (com contratos) ou mensagem de erro.

---

### 1.3 Ordens de serviço

| Item | Valor |
|------|--------|
| **Listar ordens** | `GET /ordensservico/list-api` ou `GET /ordensservico/listAPI` |
| **Atualizar situação** | `PUT /ordensservico/refresh-api` ou `PUT /ordensservico/refreshAPI` |

**list-api (GET)**  
- Headers: `empresa`, `token`, **`situacao`** (obrigatório, ex.: 4 = liberadas para faturamento), opcional **`id`** (ID da ordem).  
- Resposta: array de ordens com itens e parcelas (200) ou mensagem de erro.

**refresh-api (PUT)**  
- Body JSON: `nroordem` (ID da ordem), `situacao` (nova situação), opcional `nrodestino`.  
- Resposta: `{"mensagem": "...", "retorno": "..."}` (201 sucesso, 400/401 erro).

---

## Parte 2: Serviços SOAP do ERP (IIS) chamados pelo Portal

O Portal chama o ERP via **SOAP** usando a **URL ERP** da empresa (cadastro no Portal). No IIS devem estar publicados os seguintes serviços (WSDL).

---

### 2.1 WsProdutos.wso

**URL esperada:** `{urlerp}WsProdutos.wso?wsdl`  
Ex.: `http://10.0.2.7:85/WebGridPGM/WsProdutos.wso?wsdl` (Grid em 10.0.2.7)

**WSDL verificado:** O serviço expõe (entre outros) os métodos abaixo. A mensagem do navegador *"This XML file does not appear to have any style information"* ao abrir o WSDL é **normal** — significa apenas que o XML está sendo exibido sem folha de estilo; o serviço está correto.

| Método SOAP | Uso no Portal | Parâmetros (Data) | Resposta (ex.) |
|-------------|----------------|-------------------|----------------|
| **GetEstoqueProdutos** | Listagem de produtos, orçamentos, ordens, estoque | `iFilial`, `sChave`, `bApenasComSaldo`, `sCodProduto`, `sDescricao` | `GetEstoqueProdutosResult` → `tWsProdutosEstoque` (array de `tWsProdutosEstoque`: sCodProduto, sDescProduto, nQtdeAtual, nPrecoVenda, etc.) |
| **GetProdutoEstoque** | Quantidade em estoque de um produto | `iFilial`, `sChave`, `sProduto` | `GetProdutoEstoqueResult` (decimal) |
| **GetSerialNumberProduto** | Números de série do produto | `iFilial`, `sChave`, `sProduto`, `bApenasDisponiveis` | `GetSerialNumberProdutoResult` → `tWsProdutoSerialNumber` (array) |

- **iFilial** e **sChave** vêm de `vendor/PGMPackages/UserConstants.php` (`C_Filial`, `C_ChaveAcesso`).

---

### 2.2 WSPGMPessoas.wso

**URL esperada:** `{urlerp}WSPGMPessoas.wso?wsdl`

| Método SOAP | Uso no Portal | Parâmetros (Data) |
|-------------|----------------|-------------------|
| **GerenciaCliente** | Sincronizar cliente do Portal para o ERP | `iEmpresa`, `sToken` (token da empresa no Portal), `sJSON` (dados do cliente em JSON) |

---

### 2.3 WSPGMContratos.wso

**URL esperada:** `{urlerp}WSPGMContratos.wso?wsdl`

| Método SOAP | Uso no Portal | Parâmetros (Data) |
|-------------|----------------|-------------------|
| **GerenciaContrato** | Sincronizar contratos do cliente para o ERP | `iEmpresa`, `sToken`, `sJSON` (contratos em JSON), `sCNPJ` (cliente) |

---

## Parte 3: Resumo para o ERP (IIS/Windows)

### O que o Integrador (ERP) deve fazer

1. **Chamar o Portal com POST (produtos):**  
   - URL: `https://portal.pgm.inf.br/portal/produtos/add-api`  
   - Método: **POST** (não GET).  
   - Headers: `empresa`, `token`, `Content-Type: application/json`.  
   - Body: JSON com pelo menos `{"codigo": "03", ...}`.  
   - Exibir na tela "Retorno:" o valor da chave **`retorno`** ou **`mensagem`** do JSON da resposta.

2. **Clientes/contratos:** POST em `/portal/clientes/add-api` com JSON de cliente + array `Servicos` (contratos).

3. **Ordens de serviço:**  
   - GET em `/portal/ordensservico/list-api` (headers: `empresa`, `token`, `situacao`).  
   - PUT em `/portal/ordensservico/refresh-api` para atualizar situação (body: `nroordem`, `situacao`).

### O que o ERP (IIS) deve expor para o Portal

1. **WsProdutos.wso** — métodos: `GetEstoqueProdutos`, `GetProdutoEstoque`, `GetSerialNumberProduto` (parâmetros conforme tabela acima).
2. **WSPGMPessoas.wso** — método: `GerenciaCliente`.
3. **WSPGMContratos.wso** — método: `GerenciaContrato`.

A **URL base** do ERP (onde esses .wso estão) é configurada no Portal em **Empresas → Editar empresa → URL ERP**. O Portal monta as chamadas como `{urlerp}NomeDoServico.wso?wsdl`.

---

## Parte 4: O que precisamos do ERP (checklist)

Para fechar a integração do lado do ERP/IIS, confirme ou envie:

1. **URL base do ERP no IIS**  
   Com a infraestrutura atual: `http://10.0.2.7:85/WebGridPGM/` (Grid em 10.0.2.7). Cadastre em **Empresas → URL ERP**.

2. **Serviços SOAP publicados**  
   - Os três arquivos/serviços estão no IIS? (`WsProdutos.wso`, `WSPGMPessoas.wso`, `WSPGMContratos.wso`)  
   - Os métodos listados (GetEstoqueProdutos, GetProdutoEstoque, GetSerialNumberProduto, GerenciaCliente, GerenciaContrato) existem e aceitam os parâmetros descritos?

3. **Integrador (envio para o Portal)**  
   - Envio de produtos: está usando **POST** (não GET) em `/portal/produtos/add-api`?  
   - Headers `empresa` e `token` estão preenchidos com o ID e o token da empresa cadastrada no Portal?  
   - Em caso de erro, o Integrador lê o **body** da resposta (JSON) e exibe a chave **`retorno`** ou **`mensagem`** na caixa "Retorno:"?

4. **Token da empresa**  
   - No Portal, em **Empresas**, a empresa usada na integração tem um **token** definido? Esse mesmo token deve ser enviado no header `token` em todas as chamadas do ERP ao Portal.

5. **Firewall/rede**  
   - O servidor do Portal (Linux) consegue acessar a URL do ERP (IIS) na rede?  
   - O servidor do ERP (IIS) consegue acessar `https://portal.pgm.inf.br/portal/...`?

Com essas informações e os ajustes no Integrador (POST + headers + leitura de `retorno`), as integrações de produtos, clientes, contratos e ordens de serviço podem ser validadas ponta a ponta.
