# O que ajustar para a integração Windows ↔ Portal

A comunicação entre os 3 dispositivos (Portal, banco, Windows) já existe; o Portal já conecta no banco. O que falta é **só** a integração entre **Windows (ERP/Grid)** e **Portal**.

São **duas direções**:

1. **Portal → Windows:** o Portal (Linux 10.0.2.25) chama os serviços SOAP do Grid (Windows 10.0.2.7).
2. **Windows → Portal:** o Integrador no Windows envia dados (produtos, clientes, ordens) para o Portal.

---

## 1. Portal → Windows (Portal chama o Grid)

O Portal usa a **URL do ERP** que está no **banco de dados** (tabela `empresas`, coluna `urlerp`). Se essa URL ainda for do tempo em que tudo rodava no mesmo servidor, o Portal não consegue alcançar o Grid.

### O que fazer

| Onde | Ajuste |
|------|--------|
| **Banco (PostgreSQL)** | Garantir que **`empresas.urlerp`** = `http://10.0.2.7:85/WebGridPGM/` (com barra no final). |
| **Como alterar** | No **Portal** → menu **Empresas** → **Editar** a empresa usada na integração → campo **"URL ERP"** → colocar `http://10.0.2.7:85/WebGridPGM/` → **Salvar**. |
| **Ou via SQL** | No PostgreSQL: `UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE id = <id_da_empresa>;` (ou use o script `config/sql_atualizar_urlerp_para_grid_remoto.sql`). |

### Conferir

- No **Portal** (logado), ao usar telas que consultam o ERP (ex.: produtos, estoque, ordem de serviço), o Portal passa a montar as chamadas para `http://10.0.2.7:85/WebGridPGM/WsProdutos.wso`, etc.
- Se der erro de conexão/timeout, verificar: **firewall do Windows (10.0.2.7)** liberando entrada na **porta 85** a partir do IP **10.0.2.25** (Portal).

---

## 2. Windows → Portal (Integrador envia dados para o Portal)

O **Integrador GridERP + Web** (no Windows) envia produtos, clientes e ordens para o Portal via **HTTP** (APIs). Para isso funcionar:

### O que configurar no Integrador (Windows)

| Item | Valor / Como |
|------|----------------|
| **URL base do Portal** | `https://portal.pgm.inf.br/portal` (ou `http://10.0.2.25/portal` se não usar domínio). O Integrador deve chamar o **Portal**, que está no **10.0.2.25**, não o próprio Windows. |
| **Envio de produtos** | **Método:** `POST` (não GET). **URL:** `https://portal.pgm.inf.br/portal/produtos/add-api` (ou com IP: `http://10.0.2.25/portal/produtos/add-api`). |
| **Headers** | `empresa`: **ID** da empresa no Portal (tabela `empresas`, coluna `id`). `token`: **token** da empresa no Portal (coluna `empresas.token`). `Content-Type`: `application/json`. |
| **Body (produtos)** | JSON com pelo menos `{"codigo": "03", ...}`. Opcional: `descricao`, `unidade`, `vlunitario`, `tipo`, `ativo`. |
| **Exibir erro** | Em caso de falha, o Portal devolve JSON com `mensagem` e `retorno`. O Integrador deve ler o **corpo da resposta** (response body) e exibir o valor de **`retorno`** (ou `mensagem`) na caixa "Retorno:". |

### Onde pegar empresa e token no Portal

- **ID da empresa:** no Portal, **Empresas** → lista → o **ID** da empresa (ou via banco: `SELECT id, nome, token FROM empresas;`).
- **Token:** na mesma tela **Empresas** → **Editar** a empresa → campo **Token**. Se estiver vazio, defina um valor e salve (o Portal usa esse token para validar as chamadas do Integrador).

### Conferir

- **Firewall/rede:** o **Windows (10.0.2.7)** precisa conseguir acessar o **Portal** em **10.0.2.25** (porta 80 ou 443, conforme usar HTTP ou HTTPS).
- Teste manual (no Windows, PowerShell ou navegador): abrir `https://portal.pgm.inf.br/portal/produtos/add-api` — deve retornar mensagem de "Método não permitido" (405) com texto em JSON, e **não** erro de rede. Isso confirma que o Windows alcança o Portal.

---

## Resumo em uma tabela

| Sentido | O que ajustar | Onde |
|---------|----------------|------|
| **Portal → Windows** | URL do ERP = `http://10.0.2.7:85/WebGridPGM/` | Banco: `empresas.urlerp` (ou tela Empresas → Editar empresa → URL ERP). Firewall Windows: liberar porta 85 para 10.0.2.25. |
| **Windows → Portal** | URL do Portal no Integrador; método POST; headers `empresa` e `token`; ler `retorno` no erro. | Configuração do Integrador (Grid). Token da empresa no Portal (Empresas → Editar). Firewall: Windows conseguir acessar 10.0.2.25 (80/443). |

Depois desses ajustes, a integração entre Windows e Portal deve passar a funcionar nos dois sentidos.
