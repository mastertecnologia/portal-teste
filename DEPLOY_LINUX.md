# Deploy no Linux – copiar e testar

Guia rápido para copiar o portal do Windows para o Linux e testar.

---

## O que copiar para o Linux (resumo)

Copie **toda a pasta do projeto** (exceto o que for só de desenvolvimento no Windows). No PowerShell, por exemplo:

```powershell
# Exemplo: enviar para o servidor Linux (ajuste usuario e IP_DO_LINUX)
scp -r c:\Portal_New\portal usuario@IP_DO_LINUX:/var/www/
```

Ou com **rsync** (no Linux, puxando do Windows ou de outro servidor):

```bash
rsync -av --exclude=tmp --exclude=logs --exclude=.git \
  usuario@origem:/caminho/portal/ /var/www/portal/
```

**Pastas/arquivos que devem estar no Linux (estrutura /var/www/portal):**

| Copiar | Observação |
|--------|------------|
| `bin/` | Comandos CakePHP |
| `config/` | Inclui exemplos; no Linux use `app_local.php` (script cria a partir do example) |
| `src/` | Controllers, Models, Templates – **aqui está a ligação com o ERP** (veja `LIGACAO_ERP_WINDOWS.md`). **Não usar pasta app.** |
| `vendor/` | Dependências + PGMPackages (UserConstants.php com C_ChaveAcesso, C_Filial) |
| `public/` | Document root: CSS, JS, plugins, index.php (estrutura Linux) |
| `scripts/` | preparar_linux.sh, test_ambiente_linux.php |
| `.htaccess` | Raiz (redireciona para public/) e dentro de public/ |
| `.env.example` | Copiar para `.env` e definir `WEBROOT_DIR=public`, `APP_DIR=src` |
| `config/app_local_linux.example` | Modelo de config para o script usar no Linux |

**Não precisa copiar (ou pode excluir):**  
`tmp/`, `logs/` (serão recriados no Linux), `.git` se quiser deploy sem histórico.

**Arquivo para ver a ligação com o Windows (ERP):**  
`src/Controller/ProdutosController.php` (estoque e produtos) e `LIGACAO_ERP_WINDOWS.md` (lista de todos os arquivos).

---

## Pré-requisitos no Linux

- PHP 5.6+ com extensões: intl, mbstring, pdo_pgsql
- PostgreSQL acessível no servidor 10.0.2.23 (usuário postgres, senha pgm@postgres). Portal em 10.0.2.25; ERP/Grid em 10.0.2.7. Ver docs/INFRAESTRUTURA_SERVIDORES.md.
- Banco `pgm` criado no PostgreSQL (ou outro nome; ajuste em `config/app_local.php`)

## 1. Copiar o projeto para o Linux

Na sua máquina Windows (PowerShell ou CMD), por exemplo:

```powershell
# Exemplo com SCP (ajuste usuário e IP do servidor Linux)
scp -r c:\Portal_New\portal usuario@IP_DO_SERVIDOR:/var/www/
```

Ou use WinSCP, FileZilla, rsync, ou compacte a pasta e envie (descompacte no Linux).

**Importante:** Não copie a pasta `tmp` nem os arquivos de log (podem ser recriados). Se usar rsync:

```bash
# No Linux, puxando do Windows (ou de outro servidor)
rsync -av --exclude=tmp --exclude=logs --exclude='.git' usuario@origem:/caminho/portal/ /var/www/portal/
```

Se copiar tudo (incluindo tmp/logs), não há problema; o script de preparação cria o que faltar.

**Estrutura esperada no Linux (pasta app não existe; usar src e public):**

```
/var/www/portal/
├── bin/
├── config/
├── logs/          (pode estar vazio; o script cria)
├── public/        (Document root – CSS, JS, index.php)
├── scripts/
├── src/           (app não existe; código em src)
├── tmp/           (pode estar vazio; o script cria)
├── vendor/
├── .env            (criar a partir de .env.example: WEBROOT_DIR=public, APP_DIR=src)
├── .htaccess
└── ...
```

## 2. No servidor Linux: preparar e testar

Conecte no servidor (SSH) e execute:

```bash
cd /var/www/portal

# Dar permissão de execução ao script
chmod +x scripts/preparar_linux.sh

# Executar (cria tmp, logs, app_local.php, testa conexão)
./scripts/preparar_linux.sh
```

Se o servidor web for Apache/Nginx com usuário `www-data`:

```bash
./scripts/preparar_linux.sh www-data
```

O script:

1. Cria os diretórios `tmp`, `tmp/cache`, `logs`, `public/arquivos/tickets` (ou webroot se usar essa pasta) se não existirem  
2. Copia `config/app_local_linux.example` para `config/app_local.php` (só se ainda não existir)  
3. Ajusta permissões (775 em tmp, logs, public/arquivos)  
4. Roda `php scripts/test_ambiente_linux.php` (paths, banco, case-sensitive)

**Antes de rodar:** crie `.env` a partir de `.env.example` com `WEBROOT_DIR=public` e `APP_DIR=src`.

Se aparecer **Ambiente OK**, siga para o passo 3.

### Se der erro no teste

- **Diretório não existe:** o script já cria; rode de novo.  
- **Diretório não gravável:** use `./scripts/preparar_linux.sh www-data` ou `sudo chown -R www-data:www-data /var/www/portal/tmp /var/www/portal/logs /var/www/portal/public/arquivos`.  
- **Banco de dados:** confira em `config/app_local.php` (host 10.0.2.23, usuário postgres, senha pgm@postgres, database pgm). Teste a conexão: `psql -h 10.0.2.23 -U postgres -d pgm -c "SELECT 1"`.  
- **Arquivo UserConstants.php não encontrado:** no Linux é case-sensitive; a pasta deve ser `vendor` (minúsculo) e o arquivo `UserConstants.php` (U e C maiúsculos). Não renomeie; o código já está ajustado.

## 3. Ajustar config (se precisar)

Edite apenas se for usar outro banco, host ou salt:

```bash
nano config/app_local.php
```

- `host` → IP do PostgreSQL (10.0.2.23 com servidores separados)  
- `database` → nome do banco (padrão pgm)  
- `password` → senha do postgres (padrão pgm@postgres)  
- `Security.salt` → troque por uma string longa e aleatória (obrigatório em produção)

**URL do ERP (Grid em 10.0.2.7):** não fica no app_local; fica no **banco** (tabela `empresas`, coluna `urlerp`). Use `http://10.0.2.7:85/WebGridPGM/`. Atualize pela tela **Empresas → Editar empresa** ou execute o script `config/sql_atualizar_urlerp_para_grid_remoto.sql`. Ver `docs/INFRAESTRUTURA_SERVIDORES.md`.

Salve e rode de novo o teste:

```bash
php scripts/test_ambiente_linux.php
```

## 4. Testar no navegador

Na raiz do projeto (com estrutura public/):

```bash
cd /var/www/portal
php -S 0.0.0.0:8765 -t public
```

No seu PC, abra: **http://IP_DO_SERVIDOR_LINUX:8765**

Exemplo: se o IP do Linux for 10.0.2.10, use `http://10.0.2.10:8765`.

Para parar o servidor: Ctrl+C.

## 5. Usar Apache ou Nginx (opcional)

- **Apache:** DocumentRoot = `/var/www/portal/public`. Ver exemplos em `MIGRACAO_LINUX.md`.  
- **Nginx:** `root /var/www/portal/public;` e `try_files $uri $uri/ /index.php?$args;`. Ver `MIGRACAO_LINUX.md`.

## Resumo dos comandos (copiar e colar no Linux)

```bash
cd /var/www/portal
chmod +x scripts/preparar_linux.sh
./scripts/preparar_linux.sh
# Se tudo OK:
php -S 0.0.0.0:8765 -t public
# Acessar http://IP:8765
```

## Referência rápida

| Arquivo / Ação | Uso |
|----------------|-----|
| `config/app_local_linux.example` | Modelo de config; o script copia para `app_local.php` |
| `config/app_local.php` | Config local (não versionado); edite host, DB, salt; opcional fullBaseUrl para SSL |
| `scripts/preparar_linux.sh` | Cria dirs (public/arquivos), config e roda teste |
| `scripts/test_ambiente_linux.php` | Testa paths e conexão PostgreSQL |
| `.env` | Definir `WEBROOT_DIR=public`, `APP_DIR=src` (estrutura sem pasta app) |
| `MIGRACAO_LINUX.md` | Detalhes de estrutura, Apache/Nginx, SSL, permissões |
| `LIGACAO_ERP_WINDOWS.md` | Integração ERP (estoque, produtos, contratos, clientes) |
