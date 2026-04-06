# Credenciais (banco, e-mail, salt)

## Onde colocar segredos

1. **Ficheiro `/var/www/portal/.env`** (ou equivalente), **fora do Git** (`.gitignore` inclui `.env`).
2. **`config/app.php`** já lê `DB_*`, `MAIL_*`, `SECURITY_SALT` via `env()` — não duplicar senhas aí.
3. **`config/app_local.php`** no servidor: pode sobrescrever `debug`, `Security.salt` via `env('SECURITY_SALT')`, etc. **Evitar** `'password' => '...'` em texto fixo; usar só `env('DB_PASSWORD', '')` ou omitir o bloco `Datasources` e deixar o `app.php` + `.env`.

## Permissões no Linux

```bash
chmod 600 /var/www/portal/.env
chown www-data:www-data /var/www/portal/.env
```

(Ajustar utilizador ao do PHP-FPM/Apache.)

## Rotação

Se alguma senha chegou a estar em exemplo versionado ou em histórico Git, **troque a senha no PostgreSQL / SMTP** e atualize só o `.env` em cada ambiente.

## Referência de variáveis

Ver `.env.example` na raiz do projeto.
