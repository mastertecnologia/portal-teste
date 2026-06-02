# Infraestrutura – Portal, Banco e ERP (Grid) em servidores separados

Resumo da topologia atual e onde configurar cada coisa. **No servidor Windows/Grid (ECS-MASTER) não foi feita alteração.**

---

## Servidores

| Serviço | IP | Observação |
|--------|-----|------------|
| **Portal (Linux)** | **10.0.2.25** | Aplicação CakePHP; antes rodava em 10.0.2.7 junto com o resto. |
| **PostgreSQL** | **10.0.2.23** | Banco de dados do Portal. |
| **ERP/Grid (Windows, ECS-MASTER)** | **10.0.2.7** | IIS, WebGridPGM, serviços .wso; **sem alterações** neste servidor. |

---

## O que configurar

### 1. No servidor do Portal (10.0.2.25)

- **Arquivo `.env`** (ou `config/app_local.php`):
  - `DB_HOST=10.0.2.23` (PostgreSQL em servidor separado)
  - `DB_PORT=5432`
  - `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` conforme seu ambiente
  - Não é necessário configurar IP do ERP no .env; a URL do ERP fica no **banco** (abaixo).

- **Firewall/rede:** o servidor 10.0.2.25 deve conseguir:
  - Acessar **10.0.2.23** na porta **5432** (PostgreSQL)
  - Acessar **10.0.2.7** na porta **85** (HTTP do IIS/WebGridPGM) para as chamadas SOAP ao ERP.

### 2. No banco de dados (PostgreSQL 10.0.2.23)

A **URL do ERP** é lida da tabela **`empresas`**, coluna **`urlerp`**. O Portal usa esse valor para montar as chamadas aos serviços .wso (WsProdutos, WSPGMPessoas, WSPGMContratos).

- **Valor correto para a nova infraestrutura:**  
  `http://10.0.2.7:85/WebGridPGM/`  
  (com barra no final; porta 85 é a do HTTP no IIS que você mostrou; WebGridPGM é o caminho da aplicação.)

- **Como ajustar:**
  - **Pela tela:** Portal → **Empresas** → Editar empresa → campo **"URL ERP"** → salvar.
  - **Direto no banco (exemplo):**  
    `UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE id = 1;`  
    (ajuste o `WHERE` se tiver mais de uma empresa ou IDs diferentes.)

Se antes tudo rodava no mesmo servidor (10.0.2.7), é provável que `urlerp` estivesse como `http://localhost:85/WebGridPGM/` ou `http://ECS-MASTER:85/WebGridPGM/`. Com o Portal agora em 10.0.2.25, o Portal precisa chamar o ERP pelo **IP 10.0.2.7** (ou hostname que resolva para 10.0.2.7), por isso usar `http://10.0.2.7:85/WebGridPGM/`.

### 3. No servidor do ERP/Grid (10.0.2.7 – ECS-MASTER)

- Nenhuma alteração necessária no Windows/IIS/WebGridPGM.
- O **Integrador** (quando envia produtos/clientes/ordens para o Portal) deve usar a URL do **Portal**:  
  `https://portal.pgm.inf.br/portal/`  
  (ou o IP 10.0.2.25 se não usar nome de domínio). O Portal está em 10.0.2.25; o Integrador faz HTTP **para** o Portal, não para o próprio 10.0.2.7.

  **Importante:** se `portal.pgm.inf.br` resolver para o ERP Windows (Apache **Win64**), rotas `/portal/licencas-*` devolvem **404**. O tráfego HTTPS público deve terminar no **10.0.2.25**. Ver `docs/LICENCIAMENTO_DNS_PUBLICO.md` e `bin/cake licencas url_check` no servidor Linux.

### 4. PostgreSQL (10.0.2.23)

- **pg_hba.conf:** liberar conexões do IP do Portal (**10.0.2.25**) para o banco `pgm` (usuário postgres ou o que o Portal usar).
- **listen_addresses:** se necessário, permitir escuta em rede (ex.: `*` ou `0.0.0.0`) para aceitar conexões do 10.0.2.25.

---

## Resumo rápido

| Onde | O que |
|------|--------|
| **Portal (10.0.2.25)** | `.env`: `DB_HOST=10.0.2.23`. Rede: conseguir acessar 10.0.2.23:5432 e 10.0.2.7:85. |
| **Banco (10.0.2.23)** | Tabela `empresas.urlerp` = `http://10.0.2.7:85/WebGridPGM/`. Liberar conexão do 10.0.2.25. |
| **ERP/Grid (10.0.2.7)** | Nada a alterar. Integrador apontar para o Portal (10.0.2.25 ou portal.pgm.inf.br). |

---

## Script SQL de exemplo (atualizar URL do ERP)

Execute no PostgreSQL (por exemplo `psql -h 10.0.2.23 -U postgres -d pgm`):

```sql
-- Atualizar URL do ERP para todas as empresas (Grid em 10.0.2.7)
UPDATE empresas
SET urlerp = 'http://10.0.2.7:85/WebGridPGM/'
WHERE urlerp IS NULL OR urlerp = '' OR urlerp LIKE '%localhost%' OR urlerp LIKE '%ECS-MASTER%';
```

Ajuste o `WHERE` se quiser restringir a uma empresa específica (ex.: `WHERE id = 1`).
