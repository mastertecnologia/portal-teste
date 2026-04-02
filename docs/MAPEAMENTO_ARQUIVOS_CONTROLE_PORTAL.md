# Mapeamento: ficheiros que controlam o portal PGM

Este documento descreve **o que o projeto usa em runtime**, a **ordem de carregamento da configuração** e a **função dos ficheiros principais**. Útil para produção (onde `config/app_local.php` costuma existir só no servidor) e para não confundir ficheiros **versionados** com **exemplos** (`.env.example`, `app_local_example.example`).

---

## 1. Melhor opção: URL base (`fullBaseUrl`) e onde configurar

| Abordagem | Quando usar |
|-----------|-------------|
| **`config/app_local.php` em produção** | **Recomendado para vocês**, se já é o padrão da equipa: URL absoluta, `debug`, salt, DB ou SMTP específicos do servidor **sem** versionar segredos. O CakePHP faz *merge* com `app.php`; só precisas de definir as chaves que queres substituir. |
| **`.env` na raiz do projeto** | Carregado **antes** de `paths.php` e `app.php` (ver `config/bootstrap.php`). Bom para variáveis lidas com `env()` dentro de `app.php` (DB, mail, contratos, etc.). O ficheiro **não deve ir para o Git** com valores reais. |
| **`APP_FULL_BASE_URL` no `.env`** | **Implementado em `config/app.php`:** se definida (não vazia), usa essa URL (barra final removida). Caso contrário, mantém-se `false` até o `bootstrap.php` tentar `HTTP_HOST` + `HTTPS`. **Prioridade:** valor em `app_local.php` (carregado depois) continua a poder sobrescrever o `.env`. |

**Conclusão:** Para **proxy/SSL** problemático, pode usar **`APP_FULL_BASE_URL`** no `.env` do servidor **ou** `'fullBaseUrl' => 'https://…'` no **`app_local.php`**. O segundo ganha se ambos existirem (merge após `app.php`). O `.env.example` é **só documentação**; a app lê apenas **`.env`** na raiz.

---

## 2. Fluxo de arranque (ordem real)

1. **`public/index.php`** (DocumentRoot típico em Linux) — carrega Composer e instancia `Cake\Http\Server` com a pasta `config/`.
2. **`vendor/autoload.php`** — autoload PSR-4 (`App\`, CakePHP, dependências).
3. **`config/bootstrap.php`** (invocado pelo núcleo Cake a partir da pasta de config):
   - Lê **`.env`** na **raiz do projeto** (se existir): `putenv` + `$_ENV`.
   - Carrega **`config/paths.php`** — define `ROOT`, `APP`, `WWW_ROOT`, `TMP`, `LOGS`, `CONFIG`, etc. (`WEBROOT_DIR` e `APP_DIR` podem vir do `.env`).
   - Carrega o bootstrap do núcleo CakePHP.
   - **`Configure::load('app')`** → **`config/app.php`** (base **sempre** usada).
   - Se existir **`config/app_local.php`** → carrega e **sobrepõe** chaves (produção/homolog/local).
   - Se existir **`config/rbac.php`**, **`config/abac.php`** → carregam em seguida.
   - Timezone, `intl`, `I18n::setLocale`, tipos de data imutáveis, ligação à BD, e-mail, etc.

**CLI (`bin/cake`):** usa o mesmo `config/bootstrap.php` (via consola Cake).

---

## 3. Ficheiros de configuração (`config/`)

| Ficheiro | Versionado? | Função |
|----------|-------------|--------|
| **`bootstrap.php`** | Sim | Ponto central: `.env`, `paths.php`, carga de `app`, `app_local`, `rbac`, `abac`, handlers de erro, cache, DB, mail, `FrozenDate` JSON, etc. |
| **`paths.php`** | Sim | Constantes de caminho: `ROOT`, `APP`, `WWW_ROOT` (pasta pública: `webroot` ou `public` via `WEBROOT_DIR`), `TMP`, `LOGS`, `CACHE`. |
| **`app.php`** | Sim | **Configuração base** da aplicação: `debug`, `App` (encoding, locale, `webroot`, `fullBaseUrl`…), `Security.salt` (com fallback; preferir env/ local), datasources, cache, e-mail (vários transports), logs, **Contract** (Autentique, PDFs, notificações), e outras chaves. Lê muitas variáveis com `env()`. |
| **`app_local.php`** | **Não** (só no servidor / `.gitignore`) | **Overrides** por ambiente: `debug`, senhas de BD, `fullBaseUrl`, transports SMTP, chaves Sintegra/Speedio em array `Configure`, etc. Modelo: `app_local_example.example`. |
| **`rbac.php`** | Sim | Matriz de permissões por papel; modo `RBAC_MODE` pode vir de `env('RBAC_MODE')`. |
| **`abac.php`** | Sim | Regras ABAC (escopo empresa/cliente/*own*), complementar ao RBAC. |
| **`routes.php`** | Sim | URLs → controladores/ações; rotas amigáveis (ex.: `/modulo-contratos/...`). |
| **`ticket_workflow_constants.php`** | Sim | Constantes de fluxo de tickets (incluído no fim do `bootstrap.php`). |
| **`permissions_registry.php`** | Sim | Registo/canónico de códigos de permissão (usado com RBAC). |
| **`ordens_servico_relatorios.php`** | Sim | Configuração específica de relatórios de OS. |
| **`Migrations/*.php`** | Sim | Evolução do schema PostgreSQL (Phinx/Cake Migrations). |
| **`schema/*.sql`, `sql/*.sql`** | Sim | Scripts SQL pontuais / referência (alguns idempotentes). |

### Ficheiros de configuração **não** usados em runtime normal (modelo / legado)

| Ficheiro | Nota |
|----------|------|
| **`app_local_example.example`** | Modelo para copiar para `app_local.php`. |
| **`app_local_linux.example`** | Exemplo orientado a Linux. |
| **`app.default.php`, `app-git.php`, `app_old.php`** | Cópias ou variantes históricas; o runtime usa **`app.php`**. |
| **`bootstrap_old.php`** | Legado. |
| **`routes_darli.php`, `routes - backup.php`** | Não são carregados salvo inclusão manual (o ativo é `routes.php`). |
| **`*.conf.example`, `httpd-vhosts*.conf`** | Exemplos Apache; não são lidos pelo PHP. |

---

## 4. Variáveis de ambiente (`.env`)

| Aspecto | Detalhe |
|---------|---------|
| **Localização** | Raiz do projeto (irmão de `config/`, `src/`, `public/`). |
| **Carregamento** | `config/bootstrap.php` — parser simples linha a linha (`KEY=VALUE`). |
| **`.env.example`** | **Apenas documentação** para humanos; a app **não** o lê. |
| **Uso** | Valores referenciados em `config/app.php` (e por vezes em `app_local.php` via `env()`), por exemplo: `DB_*`, `SECURITY_SALT`, `MAIL_*`, `CONTRACT_*`, `RBAC_MODE`, `APP_DEFAULT_LOCALE`, `VAULT_ENCRYPTION_KEY`, etc. |

Em produção, **podes** usar só `app_local.php` **ou** combinar `.env` + `app.php` + overrides em `app_local.php`; o importante é **não versionar** segredos.

---

## 5. Entrada da aplicação e HTTP

| Ficheiro | Função |
|----------|--------|
| **`public/index.php`** | Front controller web quando o DocumentRoot aponta a `public/` (estrutura recomendada: código fora da pasta pública). |
| **`src/Application.php`** | Subclasse `BaseApplication`: plugins (ex. DebugKit em debug), fila de **middleware** (erro, body parser JSON/XML, assets, routing). |
| **`webroot/`** | Pasta clássica Cake; neste repo o deploy Linux costuma usar **`public/`** como equivalente (definido por `WEBROOT_DIR` + `paths.php`). |

---

## 6. Código da aplicação (`src/`)

| Pasta / área | Função |
|----------------|--------|
| **`Controller/`** | Ações HTTP; `AppController.php` — Auth, Flash, RBAC, ABAC, Security (`unlockedActions`), modelos comuns. |
| **`Model/Table`, `Model/Entity`** | ORM: tabelas, entidades, validação. |
| **`Template/`** | Views `.ctp`, `Layout/`, `Element/`. |
| **`Service/`** | Lógica de domínio (contratos, PDF, notificações, cadastro, etc.). |
| **`Utility/`** | Helpers (ex.: `VaultCrypto`, RBAC). |
| **`View/`** | `AppView.php` e helpers de vista. |
| **`Shell/`** | Comandos `bin/cake …` (cron, alertas, etc.). |

---

## 7. Outros diretórios relevantes

| Caminho | Função |
|---------|--------|
| **`vendor/`** | CakePHP e dependências Composer (não editar à mão). |
| **`tmp/`** | Cache, sessões, ficheiros temporários; contratos/PDFs podem usar subpastas ou path configurável (`CONTRACT_PDF_STORAGE_PATH`). |
| **`logs/`** | Logs da aplicação. |
| **`plugins/`** | Plugins Cake (se existirem). |
| **`docs/`** | Documentação funcional e de infra (ex.: `INFRAESTRUTURA_SERVIDORES.md`, `DOC3_RBAC_ABAC.md`, `MODULO_CONTRATOS_COMPLETO.md`). |

---

## 8. Resumo em uma frase

**O projeto é controlado por:** `public/index.php` → `config/bootstrap.php` (`.env` + `paths.php`) → **`config/app.php`** (base) → **`config/app_local.php`** (se existir) → `rbac.php` / `abac.php` → `routes.php` → `src/Application.php` (middleware) → controladores/modelos/templates em **`src/`**.

---

## 9. Documentos relacionados

- `docs/INFRAESTRUTURA_SERVIDORES.md` — servidores e ERP no banco.
- `docs/DOC3_RBAC_ABAC.md` — permissões.
- `docs/HARDENING_PRODUCAO_PORTAL.md` — endurecimento.
- `docs/MODULO_CONTRATOS_COMPLETO.md` — módulo de contratos e envs associados.

*Última atualização: alinhado à estrutura CakePHP 3.x do repositório com pasta pública `public/` e carregamento explícito de `.env` no `bootstrap.php`.*
