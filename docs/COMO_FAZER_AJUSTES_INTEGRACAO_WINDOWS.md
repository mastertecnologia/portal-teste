# Como fazer os ajustes – integração Windows ↔ Portal

Passo a passo: **quais arquivos**, **onde no banco** e **como** alterar.

---

## 1. Banco de dados (PostgreSQL)

A integração Windows ↔ Portal usa a tabela **`empresas`**. As colunas importantes são:

| Coluna   | Uso |
|----------|-----|
| **`id`** | Identificador da empresa. O Integrador envia esse valor no header **`empresa`**. |
| **`urlerp`** | URL base do ERP/Grid (Windows). O Portal monta as chamadas SOAP com isso (ex.: `urlerp` + `WsProdutos.wso?wsdl`). |
| **`token`** | Token da empresa. O Integrador envia no header **`token`** nas chamadas às APIs do Portal. |

### 1.1 Ver o que está no banco

Conecte no PostgreSQL (no servidor 10.0.2.23 ou de onde você acessa o banco):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "SELECT id, nomefantasia, urlerp, LEFT(token, 20) AS token_inicio FROM empresas;"
```

Ou, para ver o token completo (cuidado com quem está olhando a tela):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "SELECT id, nomefantasia, urlerp, token FROM empresas;"
```

### 1.2 Ajustar a URL do ERP (`urlerp`) – Portal → Windows

**Opção A – Pela tela do Portal**

1. Acesse o Portal (ex.: `https://portal.pgm.inf.br/portal`) e faça login como administrador.
2. Menu **Empresas** → lista de empresas.
3. Clique em **Editar** na empresa usada na integração com o Grid.
4. No campo **"URL ERP"**, coloque exatamente:  
   **`http://10.0.2.7:85/WebGridPGM/`**  
   (com barra no final).
5. Clique em **Salvar empresa**.

**Opção B – Direto no banco (SQL)**

Conecte no PostgreSQL e rode (troque `1` pelo `id` da sua empresa, se for outro):

```sql
UPDATE empresas
SET urlerp = 'http://10.0.2.7:85/WebGridPGM/'
WHERE id = 1;
```

Para atualizar **todas** as empresas que ainda tenham URL antiga (localhost, ECS-MASTER, etc.), use o script que já existe no projeto:

```bash
psql -h 10.0.2.23 -U postgres -d pgm -f config/sql_atualizar_urlerp_para_grid_remoto.sql
```

O conteúdo do script está em: **`config/sql_atualizar_urlerp_para_grid_remoto.sql`** (no repositório).

### 1.3 Ver ou definir o token – Windows → Portal

O **token** é o que o Integrador (Windows) envia no header para o Portal aceitar a requisição.

**Ver o token pela tela do Portal**

1. **Empresas** → lista.
2. Na coluna do token, clique em **Exibir**; o valor aparece em um pop-up. Copie esse valor para configurar no Integrador.

**Se o token estiver vazio – definir no banco**

Exemplo (troque `1` pelo `id` da empresa e `SEU_TOKEN_SEGURO` por um valor que só você conheça):

```sql
UPDATE empresas
SET token = 'SEU_TOKEN_SEGURO'
WHERE id = 1;
```

Depois use exatamente esse valor no Integrador no Windows (header **`token`**).

---

## 2. Arquivos no projeto (Portal)

**Nenhum arquivo de código precisa ser alterado** para a URL do ERP ou para o token: ambos vêm do **banco** (tabela `empresas`).

Resumo do que existe no projeto:

| Arquivo | O que é |
|---------|--------|
| **`config/sql_atualizar_urlerp_para_grid_remoto.sql`** | Script SQL para atualizar `empresas.urlerp` em lote. Só executar no PostgreSQL quando quiser padronizar a URL do Grid. |
| **`src/Template/Empresas/edit.ctp`** | Tela de edição de empresa. O campo **URL ERP** está na linha do `urlerp` (por volta da linha 124). Você não precisa editar o arquivo; só usar a tela para salvar o valor. |
| **`.env`** (no servidor do Portal, 10.0.2.25) | Usado para **conexão com o banco** (`DB_HOST=10.0.2.23`, etc.). **Não** guarda URL do ERP; a URL do ERP é só no banco (`urlerp`). |

Ou seja: para a integração Windows ↔ Portal, os “ajustes” são **no banco** (e na configuração do Integrador no Windows). Os arquivos do projeto já estão certos para ler `urlerp` e `token` da tabela `empresas`.

---

## 3. Configuração no Windows (Integrador)

Aqui não são arquivos do repositório do Portal, e sim a **configuração do Integrador GridERP + Web** no Windows (10.0.2.7).

Você precisa informar:

| Configuração | Onde pegar / Valor |
|--------------|--------------------|
| **URL base do Portal** | `https://portal.pgm.inf.br/portal` ou `http://10.0.2.25/portal` (conforme o que o Windows conseguir acessar). |
| **URL de envio de produtos** | `https://portal.pgm.inf.br/portal/produtos/add-api` (ou com IP: `http://10.0.2.25/portal/produtos/add-api`). |
| **Método** | **POST** (não GET). |
| **Header `empresa`** | Número **ID** da empresa no Portal (coluna `empresas.id` no banco). |
| **Header `token`** | Valor da coluna `empresas.token` (visto em Empresas → Exibir no Portal, ou no SQL). |
| **Body** | JSON, ex.: `{"codigo": "03", "descricao": "SUPORTE REMOTO", ...}`. |

Onde exatamente fica essa tela no Integrador depende do Grid; o importante é que a **URL aponte para o Portal** (10.0.2.25 ou portal.pgm.inf.br), que **empresa** e **token** sejam os mesmos do cadastro da empresa no Portal, e que o método seja **POST** para produtos.

---

## 4. Resumo rápido

| O que ajustar | Onde | Como |
|---------------|------|------|
| **URL do ERP (Portal → Windows)** | Banco: tabela **`empresas`**, coluna **`urlerp`** | Portal → Empresas → Editar empresa → campo **URL ERP** = `http://10.0.2.7:85/WebGridPGM/` → Salvar. Ou SQL: `UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE id = <id>;` ou rodar `config/sql_atualizar_urlerp_para_grid_remoto.sql`. |
| **Token (Windows → Portal)** | Banco: tabela **`empresas`**, coluna **`token`** | Ver: Portal → Empresas → Exibir (na coluna token). Definir/alterar: SQL `UPDATE empresas SET token = '...' WHERE id = <id>;` |
| **ID da empresa** | Banco: tabela **`empresas`**, coluna **`id`** | Ver na lista de Empresas no Portal ou: `SELECT id, nomefantasia FROM empresas;` |
| **Integrador (Windows)** | Configuração do Grid no Windows | URL = Portal (portal.pgm.inf.br ou 10.0.2.25); método POST; headers empresa (id) e token; body JSON. |

Nenhum outro arquivo do projeto precisa ser alterado para esses ajustes; a comunicação entre os três dispositivos e o uso de `urlerp` e `token` já estão implementados.
