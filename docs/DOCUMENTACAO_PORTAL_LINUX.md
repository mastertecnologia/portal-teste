# Documentação Completa – Portal PGM (Linux /var/www/portal)

**Projeto:** Portal CakePHP  
**Estrutura no servidor:** `/var/www/portal` (public, config, logs, src – sem pasta app)  
**Servidores:** Portal 10.0.2.25 | PostgreSQL 10.0.2.23 | ERP/Grid 10.0.2.7 (ECS-MASTER). Ver docs/INFRAESTRUTURA_SERVIDORES.md.  
**Acesso:** https://portal.pgm.inf.br/portal/ (URL com /portal)  
**Integração:** ERP Windows (estoque, produtos, contratos, clientes) – ver LIGACAO_ERP_WINDOWS.md  

---

## 1. Estrutura do projeto no servidor Linux

```
/var/www/
├── portal/
│   ├── public/          ← DocumentRoot do Apache (CSS, JS, index.php, imagens)
│   ├── config/          ← app.php, app_local.php, paths, bootstrap
│   ├── logs/            ← error.log (CakePHP)
│   ├── src/             ← Controllers, Models, Templates (não usar pasta app)
│   ├── tmp/             ← cache (models, persistent)
│   ├── vendor/          ← dependências + PGMPackages/UserConstants.php
│   ├── bin/
│   ├── plugins/
│   ├── scripts/
│   ├── .env             ← WEBROOT_DIR=public, APP_BASE=/portal, DB_*
│   ├── .htaccess
│   ├── composer.json
│   └── ...
├── erp/
├── api/
└── backups/
```

---

## 2. Configuração Apache (portal com /portal na URL)

**Arquivo:** `/etc/apache2/sites-enabled/portal.conf`

### HTTP (porta 80)

```apache
<VirtualHost *:80>
    ServerName portal.pgm.inf.br
    ServerAlias 10.0.2.25

    DocumentRoot /var/www/portal/public

    <Directory /var/www/portal/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    Redirect permanent / https://portal.pgm.inf.br/
</VirtualHost>
```

### HTTPS (porta 443) – com Alias e Rewrite para servir assets em /portal/

```apache
<VirtualHost *:443>
    ServerName portal.pgm.inf.br

    DocumentRoot /var/www/portal/public

    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/portal/(.+)$
    RewriteCond /var/www/portal/public/%1 -f
    RewriteRule ^portal/(.+)$ /$1 [L]

    Alias /portal /var/www/portal/public

    <Directory /var/www/portal/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/ssl/portal/portal.crt
    SSLCertificateKeyFile   /etc/ssl/portal/portal.key

    SetEnv WEBROOT_DIR "public"
    SetEnv APP_DIR "src"
    SetEnv APP_BASE "/portal"
    SetEnv HTTPS "on"

    ErrorLog ${APACHE_LOG_DIR}/portal_error_ssl.log
    CustomLog ${APACHE_LOG_DIR}/portal_access_ssl.log combined
</VirtualHost>
```

**Comandos após editar:**

```bash
sudo a2enmod rewrite
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## 3. Arquivo .env no servidor

**Caminho:** `/var/www/portal/.env`

```env
WEBROOT_DIR=public
APP_DIR=src
APP_BASE=/portal

DB_HOST=10.0.2.23
DB_PORT=5432
DB_USERNAME=postgres
DB_PASSWORD=
DB_DATABASE=pgm

SECURITY_SALT=<string-aleatoria-longa>
DEBUG=false
```

---

## 4. Comandos executados (resumo)

### 4.1 Git – safe directory (quando repositório pertence a outro usuário)

```bash
git config --global --add safe.directory /var/www/portal
```

### 4.2 Deploy (atualizar código do GitHub)

```bash
cd /var/www/portal
sudo -u www-data git fetch origin
sudo -u www-data git pull origin main
```

### 4.3 Permissões (dono e escrita em tmp/logs)

```bash
sudo chown -R www-data:www-data /var/www/portal
sudo chmod -R 775 /var/www/portal/tmp /var/www/portal/logs /var/www/portal/public/arquivos
```

### 4.4 Preparação inicial (criar dirs e config a partir do exemplo)

```bash
cd /var/www/portal
chmod +x scripts/preparar_linux.sh
./scripts/preparar_linux.sh www-data
```

### 4.5 Limpar cache CakePHP (após alterações ou deploy)

```bash
sudo -u www-data rm -rf /var/www/portal/tmp/cache/models/* /var/www/portal/tmp/cache/persistent/*
```

### 4.6 Descomentar APP_BASE no .env (para manter URL com /portal)

```bash
sudo sed -i 's/^#APP_BASE=\/portal/APP_BASE=\/portal/' /var/www/portal/.env
```

Ou editar manualmente: `sudo nano /var/www/portal/.env` e garantir `APP_BASE=/portal`.

---

## 5. Diagnóstico (logs e testes)

### 5.1 Log do CakePHP

```bash
tail -40 /var/www/portal/logs/error.log
tail -f /var/www/portal/logs/error.log
```

### 5.2 Log do Apache (portal SSL)

```bash
tail -20 /var/log/apache2/portal_error_ssl.log
tail -25 /var/log/apache2/portal_access_ssl.log
```

### 5.3 Verificar se arquivos estáticos existem

```bash
ls -la /var/www/portal/public/dist/css/style.min.css
ls -la /var/www/portal/public/assets/node_modules/jquery/jquery-3.2.1.min.js
ls -la /var/www/portal/public/assets/images/pgm.png
```

### 5.4 Verificar Rewrite e Alias no portal.conf

```bash
grep -n "RewriteEngine\|RewriteCond\|RewriteRule\|Alias\|DocumentRoot" /etc/apache2/sites-enabled/portal.conf
```

### 5.5 Teste curl (servir CSS via /portal/)

```bash
curl -sS -o /dev/null -w "HTTP %{http_code}\n" -H "Host: portal.pgm.inf.br" "http://127.0.0.1/portal/dist/css/style.min.css"
```

(200 = OK; 404 = arquivo não está sendo servido.)

---

## 6. Ajustes feitos no código (repositório)

- **config/app.php:** `cssBaseUrl` e `jsBaseUrl` definidos como `''` para evitar URLs `/portal/css/` e `/portal/js/` (erro Controller Css/Js not found).
- **Layouts (login.ctp, cadastrocliente.ctp):** Uso de paths relativos nos helpers (sem concatenar `$webroot`) para o HtmlHelper não duplicar a base e gerar `/portal/portal/`.
- **public/.htaccess:** Regras para, com Alias /portal, servir arquivos existentes em public/ (evitar MissingControllerException Portal/Assets).
- **Documentação:** CORRIGIR_URL_PORTAL.md, portal.conf.example, portal-raiz.conf.example, apache-vhost-portal-alias.conf.example, ACESSO_RAIZ_LINUX.md, DEPLOY_LINUX.md, MIGRACAO_LINUX.md, DIAGNOSTICO_ERRO_500.md, ESTRUTURA_LINUX.md.

---

## 7. Fluxo de trabalho (notebook → GitHub → servidor)

1. **No notebook (Windows):** editar código, depois:
   ```bash
   git add .
   git commit -m "Descrição"
   git push origin main
   ```
2. **No servidor Linux:**
   ```bash
   cd /var/www/portal
   sudo -u www-data git pull origin main
   sudo -u www-data rm -rf tmp/cache/models/* tmp/cache/persistent/*
   ```

---

## 8. Referência rápida de arquivos do projeto

| Arquivo / Pasta        | Uso |
|------------------------|-----|
| config/app_local.php   | Config local (DB, salt) – não versionar; criar a partir de app_local_linux.example |
| config/app.php         | cssBaseUrl e jsBaseUrl vazios; webroot, etc. |
| .env                   | WEBROOT_DIR=public, APP_BASE=/portal, DB_*, SECURITY_SALT |
| scripts/preparar_linux.sh | Cria tmp, logs, public/arquivos; copia app_local se não existir |
| public/.htaccess       | Rewrite para index.php; regras para Alias /portal |
| LIGACAO_ERP_WINDOWS.md | Integração ERP (URL em empresas.urlerp; UserConstants.php) |
| config/portal.conf.example | Exemplo Apache com /portal (Alias + Rewrite) |
| config/portal-raiz.conf.example | Exemplo Apache sem /portal na URL |

---

*Documentação gerada a partir do projeto portal-teste (estrutura Linux, deploy e comandos utilizados).*
