# Corrigir URLs com /portal (login e 2FA)

Quando a página de login mostra **todas** as URLs com o prefixo `/portal/` (ex.: `/portal/users/login`, `/portal/users/verificacodigo/...`), o site está sendo servido em um **subdiretório**. Se os **assets (CSS, JS, imagens)** usavam caminhos absolutos a partir da raiz do domínio (ex.: `/dist/css/...`), o navegador pedia `portal.pgm.inf.br/dist/...` em vez de `portal.pgm.inf.br/portal/dist/...`, gerando 404 e a página de login quebrada (sem estilo, logo, modal).

**Ajuste feito no código:** os layouts `login.ctp` e `cadastrocliente.ctp` passaram a usar `$this->request->getAttribute('webroot')` em todos os links de CSS, JS e favicon.

---

## Diagnóstico pelos logs (tail)

Se em **`/var/www/portal/logs/error.log`** aparecer:

- **`MissingControllerException] Controller class Portal could not be found`** com `Request URL: /portal/dist/css/...` ou `/portal/plugins/...`
- **`Controller class Assets could not be found`** com `Request URL: /assets/images/...`

isso significa que o **Apache está enviando requisições de arquivos estáticos para o CakePHP** em vez de servir os arquivos que existem em `public/dist/`, `public/assets/`, `public/plugins/`. Com DocumentRoot = `/var/www/portal/public`, o pedido `/portal/dist/css/style.min.css` vira busca por `public/portal/dist/...` (não existe), cai no rewrite e vai para `index.php`; o Cake interpreta "portal" como controller.

**Solução:** configurar **Alias /portal** apontando para **public**, para que `/portal/*` seja servido por `public/*`. Ver **`config/apache-vhost-portal-alias.conf.example`** e a seção "Apache com Alias" abaixo.

**Objetivo:** acessar em **https://portal.pgm.inf.br** (sem `/portal` na barra de endereço) e ter links como `/users/login`, `/assets/...`.

---

## 0. Apache com Alias (quando o acesso é portal.pgm.inf.br/portal/...)

Se você acessa **https://portal.pgm.inf.br/portal/users/login** e os logs mostram `MissingControllerException` para `/portal/dist/...` ou `/assets/...`, adicione o **Alias** no VirtualHost do portal:

```apache
Alias /portal /var/www/portal/public

<Directory /var/www/portal/public>
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
</Directory>

SetEnv APP_BASE "/portal"
```

Assim, `/portal/dist/css/style.min.css` passa a ser servido por `public/dist/css/style.min.css` (o .htaccess em public só manda para index.php o que **não** for arquivo existente). Recarregue o Apache: `sudo systemctl reload apache2`.

Exemplo completo: **config/apache-vhost-portal-alias.conf.example**.

---

## 1. Apache: servir o portal na raiz do domínio

O VirtualHost de **portal.pgm.inf.br** deve usar **apenas** o DocumentRoot na pasta pública, **sem** Alias ou subdiretório `/portal`:

```apache
<VirtualHost *:443>
    ServerName portal.pgm.inf.br
    DocumentRoot /var/www/portal/public

    # NÃO use: Alias /portal /var/www/portal/public

    <Directory /var/www/portal/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
    # ... SSL e logs ...
</VirtualHost>
```

- **DocumentRoot** = `/var/www/portal/public`
- **Não** configurar `Alias /portal ...`
- Reinicie o Apache: `sudo systemctl reload apache2`

---

## 2. Nginx: servir na raiz

```nginx
server {
    listen 443 ssl;
    server_name portal.pgm.inf.br;
    root /var/www/portal/public;

    # NÃO use: location /portal { ... }

    location / {
        try_files $uri $uri/ /index.php?$args;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Recarregue: `sudo systemctl reload nginx`

---

## 3. Arquivo .env no servidor

No servidor, edite `/var/www/portal/.env`:

- **Não** defina `APP_BASE=/portal`.  
  Se existir essa linha, **remova** ou deixe comentada.
- Mantenha:
  - `WEBROOT_DIR=public`
  - `APP_DIR=src`

Exemplo do que **não** deve estar ativo:

```env
# ERRADO – gera URLs com /portal
APP_BASE=/portal
```

---

## 4. Como acessar

- **Certo:** https://portal.pgm.inf.br  
  (a partir daí o login será https://portal.pgm.inf.br/users/login)
- **Evite:** https://portal.pgm.inf.br/portal/users/login

Se você tinha um bookmark ou proxy com `/portal`, atualize para a URL sem `/portal`.

---

## 5. Depois de corrigir

1. Limpe o cache do CakePHP no servidor:
   ```bash
   sudo -u www-data rm -rf /var/www/portal/tmp/cache/models/* /var/www/portal/tmp/cache/persistent/*
   ```
2. Acesse de novo **https://portal.pgm.inf.br** (sem `/portal`).
3. Faça login; o 2FA deve chamar `/users/verificacodigo/...` (sem prefixo `/portal`).

Se ainda aparecer "O código não foi informado ou é inválido":
- Confirme que está digitando o código atual do Google Authenticator (código de 6 dígitos que renova a cada 30 segundos).
- No navegador (F12 → Aba Rede), veja se a requisição para `verificacodigo` retorna 200 e qual é a resposta.
