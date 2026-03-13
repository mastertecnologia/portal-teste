# Diagnóstico: "An Internal Error Has Occurred" no Linux

Quando a página mostra apenas **"An Internal Error Has Occurred"**, o motivo real fica nos **logs** ou no **debug**. Siga os passos abaixo no servidor Linux.

---

## 1. Ver o erro real no log

No servidor (SSH), execute:

```bash
cd /var/www/portal
tail -100 logs/error.log
```

Ou para acompanhar em tempo real:

```bash
tail -f /var/www/portal/logs/error.log
```

Depois acesse de novo `portal.pgm.inf.br` no navegador e veja a linha que aparecer no log. A mensagem (e o arquivo/linha indicados) é a causa do 500.

---

## 2. Causas comuns no Linux

| Causa | O que verificar | Solução |
|-------|------------------|---------|
| **Falta `config/app_local.php`** | No servidor: `ls -la config/app_local.php` | Se não existir: `cp config/app_local_linux.example config/app_local.php` e edite (salt, banco). |
| **Security.salt vazio** | Em `config/app_local.php`: `'Security' => ['salt' => '...']` | Defina uma string longa e aleatória em `salt`. |
| **Banco de dados inacessível** | Ver host, usuário, senha em `config/app_local.php` ou `.env` | Ajuste host (ex.: 10.0.2.23), usuário, senha e teste: `psql -h ... -U ... -d pgm -c "SELECT 1"`. |
| **Document root errado** | Estrutura Linux usa pasta `public`. | No `.env` na raiz: `WEBROOT_DIR=public` e `APP_DIR=src`. DocumentRoot do Apache/Nginx = `/var/www/portal/public`. |
| **Permissões** | `tmp/` e `logs/` precisam ser graváveis pelo usuário do servidor web | `sudo chown -R www-data:www-data /var/www/portal/tmp /var/www/portal/logs /var/www/portal/public/arquivos` e `chmod -R 775 tmp logs`. |
| **Extensões PHP** | intl, mbstring, pdo_pgsql | `php -m` e instale o que faltar (ex.: `apt install php-intl php-mbstring php-pgsql`). |

---

## 3. Ativar debug só para achar o erro (cuidado em produção)

Em **`config/app_local.php`** (no servidor), coloque temporariamente:

```php
'debug' => true,
```

Recarregue a página; o CakePHP deve mostrar a mensagem de erro e o stack trace na tela. **Depois de anotar o erro, volte para `'debug' => false`.**

---

## 4. Resumo rápido (comandos no servidor)

```bash
cd /var/www/portal

# Ver último erro
tail -50 logs/error.log

# app_local existe?
ls -la config/app_local.php

# Se não existir, criar a partir do exemplo Linux
cp config/app_local_linux.example config/app_local.php
nano config/app_local.php   # Ajustar salt e Datasources

# Permissões
sudo chown -R www-data:www-data tmp logs
chmod -R 775 tmp logs
```

Depois disso, acesse de novo o site e, se ainda der 500, use o conteúdo de `logs/error.log` (ou a tela de debug) para ver exatamente qual exceção está ocorrendo.
