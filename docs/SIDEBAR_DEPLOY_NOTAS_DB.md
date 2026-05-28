# Sidebar / UI premium — relação com erro PostgreSQL `localhost`

## Conclusão

**As alterações de sidebar e layout (`PgmSidebarStaffNavRegistry`, `portal_ui.php`, layout `default` para `*-prototype`) não mudam `Datasources` nem credenciais do banco.**

O erro `connection to server at "localhost"` após o deploy da sidebar veio de **configuração de ambiente**, não do menu em si.

## O que o deploy da sidebar introduziu (relevante)

| Alteração | Commit | Impacto em DB |
|-----------|--------|----------------|
| `scripts/enable-portal-premium-ui.sh` | `70900d11` | Só grava `PORTAL_*` no `.env`. Versão antiga podia **criar** `.env` copiando `.env.example` **sem** `DB_HOST` ativo → fallback `localhost` em `app.php`. |
| `config/app.php` fallback | Antes `localhost`; corrigido em `dea3c408` para `10.0.2.23` | Sem `DB_HOST` no ambiente do PHP, usava localhost. |
| `UsersController` → `ErpHomePrototype` | `70900d11` | Mais rotas premium após login; **expõe** falha de DB mais cedo, não altera host. |
| `AppController::beforeFilter` `Empresas->get()` | (inalterado na linha ~464) | Já existia; stack trace aponta aqui porque é o primeiro uso do ORM na requisição. |
| `config/bootstrap.php` carrega `portal_ui.php` separado | `70900d11` | Apenas flags de UI; não toca em PostgreSQL. |

## Cenários que reproduzem o erro após `git pull` da sidebar

1. **`.env` sem `DB_HOST`** (ou comentado) e `app.php` ainda com fallback `localhost` (antes do commit `dea3c408`).
2. **`.env` ilegível pelo PHP-FPM** (ex.: `chmod 600` só root) — CLI como root vê `DB_HOST`; browser não.
3. **Cache OPcache / PHP-FPM** com `config/app.php` antigo em memória até reiniciar o FPM.
4. Rodar `enable-portal-premium-ui.sh` num servidor **sem** `.env` pré-existente (versão antiga do script criava cópia do example sem banco).

## Verificação no servidor

```bash
# Como o FPM (ajuste o usuário: www-data, nginx, apache)
sudo -u www-data php bin/check_db_env.php

grep '^DB_HOST=' /var/www/portal/.env
ls -la /var/www/portal/.env

bin/cake cache clear_all
systemctl restart php8.1-fpm   # versão conforme o servidor
```

## Referência

- Infra DB: `docs/INFRAESTRUTURA_SERVIDORES.md`
- Diagnóstico: `bin/check_db_env.php`
