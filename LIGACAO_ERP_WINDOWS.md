# Ligação do Portal com o ERP (Windows Server)

Onde estão os dados do servidor ERP e como cada arquivo chama o ERP.

---

## 1. Onde estão os dados do servidor ERP

**Nenhum arquivo contém a URL do servidor ERP escrita no código.** Tudo vem do banco ou de um único arquivo de constantes:

| Dado | Onde está | Arquivo que lê |
|------|-----------|----------------|
| **URL do ERP** | Banco de dados: tabela `empresas`, coluna `urlerp` | Todos os controllers que chamam ERP usam `$this->Empresas->get($idempresa)->urlerp` |
| **Chave de acesso** | Constante no código: `C_ChaveAcesso` | `vendor/PGMPackages/UserConstants.php` (valor atual: `'gridweb'`) |
| **Filial** | Constante no código: `C_Filial` | `vendor/PGMPackages/UserConstants.php` (valor atual: `1`) |
| **Token (só Clientes/Contratos)** | Banco: tabela `empresas`, coluna `token` | `ClientesController`, `ClicontratosController` usam `$this->Empresas->get(...)->token` |

- **Onde editar a URL na tela:** **Empresas** → Editar empresa → campo **"URL ERP"** (template: `src/Template/Empresas/edit.ctp`).
- **Onde editar chave e filial:** `vendor/PGMPackages/UserConstants.php` (linhas com `C_ChaveAcesso` e `C_Filial`).

---

## 2. Todos os arquivos que chamam o ERP e como chamam

Cada chamada segue o padrão:

1. Obter URL: `$this->Empresas->get(...)->urlerp` (ou `$empresa->urlerp`).
2. Montar WSDL: `urlerp . 'NomeDoServico.wso?wsdl'`.
3. Instanciar SOAP: `new CakeSoap(['wsdl' => $url])`.
4. Chamar método: `$soap->sendRequest('NomeDoMetodo', ['Data' => [...]])`.

Os parâmetros `iFilial` e `sChave` vêm de `C_Filial` e `C_ChaveAcesso` (UserConstants.php). A URL vem sempre do banco (urlerp).

---

### 2.1 ProdutosController.php

**Arquivo:** `src/Controller/ProdutosController.php`  
**Serviço:** `WsProdutos.wso`  
**URL:** `$this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WsProdutos.wso?wsdl'`  
**Dados do servidor:** `urlerp` do banco (empresa do usuário); `C_Filial` e `C_ChaveAcesso` do `UserConstants.php`.

| Método do controller | Método SOAP no ERP | Parâmetros enviados (Data) |
|----------------------|--------------------|----------------------------|
| `index()` | `GetEstoqueProdutos` | `iFilial`, `sChave`, `bApenasComSaldo`, `sCodProduto`, `sDescricao` |
| `edit()` | `GetEstoqueProdutos` | idem |
| `produto()` | `GetEstoqueProdutos` | idem |
| `qtdestoque($produto)` | `GetProdutoEstoque` | `iFilial`, `sChave`, `sProduto` |
| `serialnumberproduto($produto)` | `GetSerialNumberProduto` | `iFilial`, `sChave`, `sProduto`, `bApenasDisponiveis` |
| `estoque($opt)` | `GetEstoqueProdutos` | `iFilial`, `sChave`, `bApenasComSaldo`, `sCodProduto`, `sDescricao` |

---

### 2.2 OrdensservicoController.php

**Arquivo:** `src/Controller/OrdensservicoController.php`  
**Serviço:** `WsProdutos.wso`  
**URL:** `$this->Empresas->get($idempresa)->urlerp . 'WsProdutos.wso?wsdl'`  
**Dados do servidor:** `urlerp` do banco (empresa da ordem); `C_Filial` e `C_ChaveAcesso` do `UserConstants.php`.

| Método do controller | Método SOAP no ERP | Parâmetros enviados (Data) |
|----------------------|--------------------|----------------------------|
| (ao adicionar item / preço) | `GetEstoqueProdutos` | `iFilial`, `sChave`, `bApenasComSaldo`, `sCodProduto`, `sDescricao` |

---

### 2.3 ClientesController.php

**Arquivo:** `src/Controller/ClientesController.php`  
**Serviço:** `WSPGMPessoas.wso`  
**URL:** `$this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WSPGMPessoas.wso?wsdl'`  
**Dados do servidor:** `urlerp` e `token` do banco (empresa do usuário). Não usa C_Filial/C_Chave neste método.

| Método do controller | Método SOAP no ERP | Parâmetros enviados (Data) |
|----------------------|--------------------|----------------------------|
| `sincronizacliente($idcliente)` | `GerenciaCliente` | `iEmpresa`, `sToken` (empresa->token), `sJSON` (dados do cliente) |

---

### 2.4 ClicontratosController.php

**Arquivo:** `src/Controller/ClicontratosController.php`  
**Serviço:** `WSPGMContratos.wso`  
**URL:** `$this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WSPGMContratos.wso?wsdl'`  
**Dados do servidor:** `urlerp` e `token` do banco (empresa do usuário). Não usa C_Filial/C_Chave neste método.

| Método do controller | Método SOAP no ERP | Parâmetros enviados (Data) |
|----------------------|--------------------|----------------------------|
| `sincronizacontrato($idcliente)` | `GerenciaContrato` | `iEmpresa`, `sToken` (empresa->token), `sJSON` (contratos), `sCNPJ` (cliente) |

---

## 3. Resumo: arquivos e onde estão os dados do servidor

| Arquivo | Serviço ERP | URL do servidor | Chave/Filial | Token |
|---------|-------------|-----------------|--------------|-------|
| `src/Controller/ProdutosController.php` | WsProdutos.wso | Banco: `empresas.urlerp` | UserConstants.php | — |
| `src/Controller/OrdensservicoController.php` | WsProdutos.wso | Banco: `empresas.urlerp` | UserConstants.php | — |
| `src/Controller/ClientesController.php` | WSPGMPessoas.wso | Banco: `empresas.urlerp` | — | Banco: `empresas.token` |
| `src/Controller/ClicontratosController.php` | WSPGMContratos.wso | Banco: `empresas.urlerp` | — | Banco: `empresas.token` |
| `vendor/PGMPackages/UserConstants.php` | — | — | **Aqui:** `C_ChaveAcesso`, `C_Filial` | — |
| `src/Template/Empresas/edit.ctp` | — | **Tela** para editar `urlerp` | — | — |

Conclusão: **a URL e o token do servidor ERP estão no banco (cadastro da empresa).** Só as constantes de chave e filial estão no código, em `UserConstants.php`. Nenhum controller tem a URL do ERP escrita fixa no arquivo.
