# Migração do Portal para Linux (sem XAMPP)

Este guia descreve a migração do portal de XAMPP/Windows para Linux, com banco PostgreSQL em servidor remoto e estrutura em `/var/www`.

**Quer só copiar e testar no Linux?** Use o guia **[DEPLOY_LINUX.md](DEPLOY_LINUX.md)** (passo a passo: copiar, rodar script, testar no navegador).

## Estrutura de diretórios no servidor

```
/var/www/
├── portal/           # Aplicação CakePHP
│   ├── public/       # Document root (ou webroot – veja abaixo)
│   ├── app/          # Opcional: se renomear "src" para "app"
│   ├── src/          # Código da aplicação (padrão CakePHP)
│   ├── config/
│   ├── logs/
│   ├── tmp/
│   ├── vendor/
│   └── ...
├── erp/
├── api/
└── backups/
```

**Nota:** O CakePHP usa por padrão a pasta `webroot` como document root. Se você preferir usar a pasta `public`:

1. Renomeie `webroot` para `public`: `mv webroot public`
2. No `.env` ou no servidor (Apache/Nginx), defina `WEBROOT_DIR=public`
3. No `.htaccess` da **raiz** do projeto, troque `webroot/` por `public/` (ou use o arquivo `.htaccess.public.example`)

## 1. Banco de dados PostgreSQL (remoto)

- **Host:** 10.0.2.23  
- **Porta:** 5432  
- **Usuário:** postgres  
- **Senha:** pgm@postgres  
- **Database:** pgm (ou o nome que você usar)

### Configuração da aplicação

**Opção A – Arquivo local (recomendado em produção)**

```bash
cp config/app_local_linux.example config/app_local.php
# Edite config/app_local.php se precisar (salt, nome do database, etc.)
```

**Opção B – Variáveis de ambiente**

Copie `.env.example` para `.env` e ajuste:

```env
DB_HOST=10.0.2.23
DB_PORT=5432
DB_USERNAME=postgres
DB_PASSWORD=pgm@postgres
DB_DATABASE=pgm
SECURITY_SALT=<gere-uma-string-aleatoria-longa>
DEBUG=false
```

**No servidor PostgreSQL (10.0.2.23):** libere conexões do IP do servidor web no `pg_hba.conf` e, se necessário, no `postgresql.conf` (`listen_addresses`).

## 2. Case sensitivity (Linux)

No Linux, nomes de arquivos e pastas diferenciam maiúsculas de minúsculas. No projeto já foram ajustados:

- **Vendor → vendor** em todos os `require_once` (ROOT . DS . 'vendor' . DS . ...)
- **userConstants.php → UserConstants.php** onde o arquivo real é `UserConstants.php`
- **Use → use** em `config/bootstrap.php` (PSR)

Mantenha os nomes exatos no servidor:

- `vendor/` (minúsculo)
- `vendor/PGMPackages/UserConstants.php` (U e C maiúsculos)
- `webroot/plugins/GoogleAuthenticator-2.x/` (como está no repositório)

## 3. Permissões (Linux)

```bash
# Dono do diretório (ex.: usuário do servidor web)
sudo chown -R www-data:www-data /var/www/portal

# Permissões para escrita em tmp, logs e uploads
sudo chmod -R 775 /var/www/portal/tmp
sudo chmod -R 775 /var/www/portal/logs
sudo chmod -R 775 /var/www/portal/public/arquivos   # ou webroot/arquivos
```

## 4. Apache – VirtualHost exemplo

Document root apontando para a pasta **webroot** (ou **public**, se tiver renomeado):

```apache
<VirtualHost *:80>
    ServerName portal.seudominio.com
    DocumentRoot /var/www/portal/webroot

    <Directory /var/www/portal/webroot>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Variáveis de ambiente (opcional; senão use .env ou app_local.php)
    SetEnv DB_HOST "10.0.2.23"
    SetEnv DB_PORT "5432"
    SetEnv DB_USERNAME "postgres"
    SetEnv DB_PASSWORD "pgm@postgres"
    SetEnv DB_DATABASE "pgm"
    SetEnv SECURITY_SALT "sua-salt-aleatoria-longa"
    SetEnv WEBROOT_DIR "webroot"
</VirtualHost>
```

Se tiver renomeado `webroot` para `public`:

- `DocumentRoot /var/www/portal/public`
- `<Directory /var/www/portal/public>`
- `SetEnv WEBROOT_DIR "public"`

## 5. Nginx – Exemplo

```nginx
server {
    listen 80;
    server_name portal.seudominio.com;
    root /var/www/portal/webroot;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param DB_HOST 10.0.2.23;
        fastcgi_param DB_PORT 5432;
        fastcgi_param DB_USERNAME postgres;
        fastcgi_param DB_PASSWORD pgm@postgres;
        fastcgi_param DB_DATABASE pgm;
        fastcgi_param SECURITY_SALT "sua-salt-aleatoria-longa";
    }
}
```

## 6. Rotas e URLs

- Use sempre URLs em **minúsculas e com hífen** (já configuradas):  
  `/ordensservico/list-api`, `/ordensservico/refresh-api`.
- O CakePHP com `DashedRoute` converte `listAPI` → `list-api` na URL.

## 7. Checklist pós-migração

- [ ] `config/app_local.php` criado (a partir de `app_local_linux.example`) ou `.env` configurado
- [ ] Banco PostgreSQL acessível a partir do servidor web (firewall, pg_hba.conf)
- [ ] Permissões em `tmp/`, `logs/` e pasta de anexos
- [ ] Document root do Apache/Nginx = `webroot` ou `public`
- [ ] `SECURITY_SALT` definido e igual em todos os ambientes que compartilham sessão
- [ ] Debug desativado em produção (`debug => false` ou `DEBUG=false`)

## 8. Como testar

### 8.1 Teste via script (recomendado)

Na raiz do projeto (Windows ou Linux). No Windows use `copy` em vez de `cp` se preferir.

```bash
# 1. Criar config local (escolha uma opção)
cp .env.example .env
# e edite .env com DB_HOST=10.0.2.23, DB_PASSWORD=pgm@postgres, SECURITY_SALT=...
# OU para produção Linux:
cp config/app_local_linux.example config/app_local.php

# 2. Rodar o teste do ambiente
php scripts/test_ambiente_linux.php

# No Windows, se php não estiver no PATH (ex.: XAMPP):
# C:\xampp\php\php.exe scripts/test_ambiente_linux.php
```

O script verifica:

- Existência do `.env` e carregamento das variáveis
- Constantes ROOT, WWW_ROOT, APP_DIR, APP, CONFIG, LOGS, TMP
- Existência e permissão dos diretórios
- Conexão com PostgreSQL e query `SELECT 1`
- Presença de `vendor/PGMPackages/UserConstants.php` (case-sensitive)

Se tudo estiver certo, a saída será algo como:

```
=== Teste do ambiente (portal) ===

  [OK] .env encontrado: ...
  [OK] ROOT = /var/www/portal
  [OK] WWW_ROOT = /var/www/portal/webroot/
  ...
  [OK] Conexão PostgreSQL: OK (host=10.0.2.23, database=pgm)
  [OK] Query de teste (SELECT 1): OK
  [OK] vendor/PGMPackages/UserConstants.php: encontrado (case OK)

Ambiente OK. Pode testar no navegador ou com: php bin/cake.php
```

### 8.2 Teste no navegador (servidor embutido PHP)

No mesmo diretório raiz do projeto:

```bash
php -S localhost:8765 -t webroot
```

Acesse: **http://localhost:8765**

(No Linux com banco remoto 10.0.2.23, use o mesmo comando no servidor e acesse pelo IP/host do servidor.)

### 8.3 Teste com Apache/Nginx

Após configurar o VirtualHost (ou server no Nginx), acesse a URL do portal (ex.: `http://portal.seudominio.com`). A rota `/` deve exibir o dashboard (login ou redirecionamento).

### 8.4 Teste rápido da API (ordens)

Se a aplicação estiver no ar:

```bash
# Listar ordens (ajuste empresa, token e a URL base)
curl -s "http://localhost:8765/ordensservico/list-api?empresa=1&token=SEU_TOKEN&situacao=4"
```

---

## 9. Arquivos de referência

| Arquivo | Uso |
|--------|-----|
| `config/paths.php` | Define ROOT, WWW_ROOT, APP; usa `WEBROOT_DIR` e `APP_DIR` do ambiente |
| `config/app_local_linux.example` | Exemplo de config local para Linux com PostgreSQL remoto |
| `.env.example` | Exemplo de variáveis de ambiente (DB, WEBROOT_DIR, APP_DIR) |
| `.htaccess` (raiz) | Redireciona para `webroot/` (ou use `.htaccess.public.example` para `public/`) |
| `scripts/test_ambiente_linux.php` | Script para testar paths, .env, diretórios e conexão PostgreSQL |
