# Comandos para copiar e colar – integração Windows ↔ Portal

Use os blocos abaixo. O **token** já existe no banco (gerado por empresa); o primeiro comando mostra o que está lá para você usar no Integrador.

---

## 1. BANCO DE DADOS (PostgreSQL)

Conecte no servidor onde está o PostgreSQL (10.0.2.23) ou de um PC que tenha acesso. Senha do usuário `postgres`: a que você usa no Portal.

### 1.1 Ver ID e token da empresa (para usar no Integrador no Windows)

Copie e cole no terminal (uma linha só):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "SELECT id, nomefantasia, urlerp, token FROM empresas;"
```

Anote o **id** e o **token** da empresa que usa na integração. O token é o que você vai colocar no Integrador no Windows (header `token`). O **id** vai no header `empresa`.

---

### 1.2 Atualizar a URL do ERP (urlerp) – uma empresa (troque o 1 pelo id da sua empresa)

Copie e cole (se sua empresa for id = 1):

```sql
UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE id = 1;
```

Para rodar no terminal em uma linha (id = 1):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE id = 1;"
```

Se a empresa for outra (ex.: id = 2), troque o `1` por `2` nos dois lugares.

---

### 1.3 Atualizar urlerp em todas as empresas que ainda tenham URL antiga

Copie e cole (uma linha):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "UPDATE empresas SET urlerp = 'http://10.0.2.7:85/WebGridPGM/' WHERE urlerp IS NULL OR urlerp = '' OR urlerp LIKE '%localhost%' OR urlerp LIKE '%ECS-MASTER%' OR urlerp LIKE '%127.0.0.1%';"
```

---

### 1.4 Conferir se ficou certo

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "SELECT id, nomefantasia, urlerp FROM empresas;"
```

---

## 2. WINDOWS (Integrador GridERP + Web)

Aqui não há “comando” no banco; é configuração do Integrador. Use os **mesmos** valores que você viu no banco (id e token).

### 2.1 O que configurar no Integrador

| Campo / opção | Valor para copiar e colar |
|---------------|---------------------------|
| **URL base do Portal** | `https://portal.pgm.inf.br/portal` |
| **URL envio de produtos** | `https://portal.pgm.inf.br/portal/produtos/add-api` |
| **Método** | `POST` |
| **Header empresa** | Número do **id** da empresa (ex.: `1`). O mesmo do `SELECT` acima. |
| **Header token** | O valor da coluna **token** do `SELECT` acima (copiar e colar o token que apareceu). |
| **Content-Type** | `application/json` |

Se não usar HTTPS/domínio e acessar o Portal por IP:

- URL base: `http://10.0.2.25/portal`
- URL produtos: `http://10.0.2.25/portal/produtos/add-api`

---

### 2.2 Testar envio de produto do Windows (PowerShell) – copiar e colar

Abra **PowerShell** no Windows (Win + X → Windows PowerShell). Cole o bloco abaixo. **Antes de rodar:** troque `SEU_TOKEN_AQUI` pelo token completo da empresa (id 1 ou 2) que você obteve no banco. Se for empresa 2 (PGM), troque também `"empresa" = "1"` para `"empresa" = "2"`.

**Com HTTPS (portal.pgm.inf.br):**
```powershell
$headers = @{
  "empresa" = "1"
  "token" = "SEU_TOKEN_AQUI"
  "Content-Type" = "application/json"
}
$body = '{"codigo":"03","descricao":"SUPORTE REMOTO","vlunitario":180,"ativo":0}'
Invoke-RestMethod -Uri "https://portal.pgm.inf.br/portal/produtos/add-api" -Method POST -Headers $headers -Body $body
```

**Com HTTP (IP 10.0.2.25):**
```powershell
$headers = @{
  "empresa" = "1"
  "token" = "SEU_TOKEN_AQUI"
  "Content-Type" = "application/json"
}
$body = '{"codigo":"03","descricao":"SUPORTE REMOTO","vlunitario":180,"ativo":0}'
Invoke-RestMethod -Uri "http://10.0.2.25/portal/produtos/add-api" -Method POST -Headers $headers -Body $body
```

- Se der **erro de SSL/certificado** com HTTPS, use o bloco com HTTP (10.0.2.25).
- Resposta esperada em sucesso: algo como `mensagem` / `retorno` com "Produto cadastrado com sucesso".
- Se aparecer "Autenticação Inválida" ou "Objeto ou parâmetros inválidos", confira o **token** (copiar do banco sem espaço) e o **empresa** (1 ou 2).

**Exemplo com curl (se tiver curl no Windows):**
```bash
curl -X POST "https://portal.pgm.inf.br/portal/produtos/add-api" -H "empresa: 1" -H "token: SEU_TOKEN_AQUI" -H "Content-Type: application/json" -d "{\"codigo\":\"03\",\"descricao\":\"SUPORTE REMOTO\",\"vlunitario\":180,\"ativo\":0}"
```
(troque `SEU_TOKEN_AQUI` e o número após `empresa:` se for empresa 2.)

---

## Resumo

1. **Banco:** rode o `SELECT` (1.1), anote **id** e **token**. Rode o `UPDATE` do urlerp (1.2 ou 1.3). Confira com o `SELECT` (1.4).
2. **Windows:** na configuração do Integrador, use a **URL do Portal**, método **POST**, header **empresa** = id e header **token** = valor que você anotou. O token é o mesmo que já está no banco (não precisa criar outro).
