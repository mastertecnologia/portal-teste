# Evitar erro 301 no Integrador (HTTP em /portal)

Quando o Integrador no Windows usa **http://10.0.2.25/portal** e recebe **Status 301**, o Apache está redirecionando tudo para HTTPS. O Integrador não segue o redirect (ou trata como erro).

**Solução:** no servidor do Portal (Linux 10.0.2.25), alterar o VirtualHost da **porta 80** para **não** redirecionar requisições cujo caminho começa com **/portal**. Assim o Integrador pode usar HTTP em `http://10.0.2.25/portal/...` sem receber 301.

---

## O que alterar no servidor (Apache)

No arquivo de configuração do portal (ex.: **/etc/apache2/sites-available/portal.conf**), no bloco **&lt;VirtualHost *:80&gt;**:

1. **Remova** a linha que redireciona tudo:
   ```apache
   Redirect permanent / https://portal.pgm.inf.br/
   ```

2. **Coloque** no lugar (com **RewriteEngine** e **Alias** para /portal):

```apache
# Redireciona para HTTPS apenas quando a URL NÃO for /portal (Integrador usa HTTP)
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/portal
RewriteRule ^ https://portal.pgm.inf.br%{REQUEST_URI} [R=301,L]

Alias /portal /var/www/portal/public
<Directory /var/www/portal/public>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
</Directory>

SetEnv WEBROOT_DIR "public"
SetEnv APP_DIR "src"
SetEnv APP_BASE "/portal"
```

3. Confirme que o módulo **rewrite** está ativo:
   ```bash
   sudo a2enmod rewrite
   ```

4. Recarregue o Apache:
   ```bash
   sudo apache2ctl configtest
   sudo systemctl reload apache2
   ```

---

## Resultado

- **http://10.0.2.25/** ou **http://portal.pgm.inf.br/** → continua redirecionando 301 para HTTPS (navegador).
- **http://10.0.2.25/portal/ordensservico/list-api** (e qualquer **/portal/...**) → **não** redireciona; responde direto na porta 80 (Integrador deixa de receber 301).

No Integrador, mantenha a URL: **http://10.0.2.25/portal** (ou **http://10.0.2.25/portal/**).
