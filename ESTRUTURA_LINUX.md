# Estrutura no Linux: config, logs e app fora da pasta public

Objetivo: **config**, **logs** e **src** (app) ficam **fora** do DocumentRoot; só a pasta **public** é exposta na web.

## Estrutura final no servidor

```
/var/www/portal/                    ← raiz do projeto (não acessível pela web)
├── .env                            ← criar/ajustar (ver abaixo)
├── bin/
├── config/                         ← fora do public
├── logs/                           ← fora do public
├── plugins/
├── src/                            ← app (fora do public)
├── tmp/
├── vendor/
├── composer.json
├── composer.lock
└── public/                         ← DocumentRoot do Apache (único acessível)
    ├── .htaccess
    ├── index.php
    ├── css/
    ├── img/
    ├── js/
    ├── favicon.ico
    ├── arquivos/
    ├── assets/
    ├── dist/
    ├── font/
    ├── plugins/                    ← plugins de assets (webroot)
    └── sass/
```

---

## O que copiar e para onde

### 1. Para `/var/www/portal/` (raiz do projeto)

Copie **toda** a raiz do projeto, **exceto** a pasta `webroot` e o conteúdo dela. Inclua:

| Origem (no seu projeto) | Destino no Linux |
|-------------------------|-------------------|
| `bin/`                  | `/var/www/portal/bin/` |
| `config/`               | `/var/www/portal/config/` |
| `logs/`                 | `/var/www/portal/logs/` |
| `plugins/` (raiz)       | `/var/www/portal/plugins/` |
| `src/`                  | `/var/www/portal/src/` |
| `tmp/`                  | `/var/www/portal/tmp/` |
| `vendor/`               | `/var/www/portal/vendor/` |
| `composer.json`         | `/var/www/portal/composer.json` |
| `composer.lock`         | `/var/www/portal/composer.lock` |
| `index.php` (raiz)      | opcional em `/var/www/portal/index.php` |

Ou, no Linux, a partir da pasta do projeto:

```bash
cd /var/www/portal
# Certifique-se de que estas pastas existem e têm o conteúdo do repositório:
# config, logs, src, bin, plugins, tmp, vendor + composer.json, composer.lock
```

### 2. Para `/var/www/portal/public/` (só o que é público)

Copie **da pasta `webroot`** do projeto para `public/`:

| Origem                    | Destino |
|---------------------------|---------|
| `webroot/.htaccess`       | `/var/www/portal/public/.htaccess` |
| `webroot/index.php`      | **não use** – use o `public/index.php` do repositório (já aponta para a raiz) |
| **Pasta `public/` do repo** | já contém `index.php` e `.htaccess` corretos |
| `webroot/css/`            | `/var/www/portal/public/css/` |
| `webroot/img/`            | `/var/www/portal/public/img/` |
| `webroot/js/`             | `/var/www/portal/public/js/` |
| `webroot/favicon.ico`     | `/var/www/portal/public/favicon.ico` |
| `webroot/arquivos/`      | `/var/www/portal/public/arquivos/` |
| `webroot/assets/`        | `/var/www/portal/public/assets/` |
| `webroot/dist/`          | `/var/www/portal/public/dist/` |
| `webroot/font/`          | `/var/www/portal/public/font/` |
| `webroot/plugins/`       | `/var/www/portal/public/plugins/` |
| `webroot/sass/`          | `/var/www/portal/public/sass/` |

Resumo em um comando (execute na raiz do projeto no Linux, onde já existem `webroot` e `public`):

```bash
# Na raiz do projeto (ex: /var/www/portal)
cp -r webroot/.htaccess public/
cp -r webroot/css webroot/img webroot/js webroot/favicon.ico public/ 2>/dev/null || true
cp -r webroot/arquivos webroot/assets webroot/dist webroot/font webroot/plugins webroot/sass public/ 2>/dev/null || true
# index.php e .htaccess em public/ já vêm do repositório; não sobrescreva com os de webroot
```

---

## Ajustes de configuração

### 1. Arquivo `.env` em `/var/www/portal/.env`

Crie ou edite o `.env` na **raiz** do projeto e defina:

```env
# Obrigatório para essa estrutura (pasta pública = public)
WEBROOT_DIR=public
```

Mantenha também as variáveis que você já usa (banco, `SECURITY_SALT`, e-mail, etc.).

### 2. Caminhos (já ajustados no código)

- **config/paths.php**: usa `ROOT = dirname(__DIR__)` (a partir de `config/`), então `ROOT` = `/var/www/portal`. Com `WEBROOT_DIR=public`, `WWW_ROOT` aponta para `/var/www/portal/public/`.
- **config/app.php**: `App.webroot` usa `env('WEBROOT_DIR', 'webroot')`, então com `WEBROOT_DIR=public` os links e assets usam a pasta `public`.

Nada mais precisa ser alterado nos caminhos para essa estrutura.

### 3. Apache – DocumentRoot

O DocumentRoot deve ser **somente** a pasta `public`:

```apache
<VirtualHost *:80>
    ServerName seu-dominio ou IP

    DocumentRoot /var/www/portal/public

    <Directory /var/www/portal/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Habilite o mod_rewrite e reinicie o Apache:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 4. Permissões

O usuário do Apache (ex.: `www-data`) precisa escrever em `tmp` e `logs`:

```bash
sudo chown -R www-data:www-data /var/www/portal/tmp /var/www/portal/logs
sudo chmod -R 775 /var/www/portal/tmp /var/www/portal/logs
```

---

## Checklist rápido

- [ ] Estrutura em `/var/www/portal/`: config, logs, src, bin, plugins, tmp, vendor (e composer.json) fora de `public`.
- [ ] Em `public/`: apenas `index.php`, `.htaccess` e pastas/arquivos estáticos (css, js, img, assets, etc.) vindos do `webroot`.
- [ ] `.env` na raiz com `WEBROOT_DIR=public`.
- [ ] DocumentRoot do Apache = `/var/www/portal/public`.
- [ ] `mod_rewrite` ativado; `AllowOverride All` no `<Directory>` de `public`.
- [ ] Permissões em `tmp` e `logs` para o usuário do Apache.

Com isso, **config**, **logs** e **app (src)** ficam separados da pasta **public** e só o conteúdo de **public** é servido pela web.
