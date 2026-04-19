# Roadmap de stack — PHP 8, CakePHP 4+ e refactors

Este documento descreve uma evolucao **incremental**; nao e um big-bang. Alinhar com [`.cursor/rules/alteracoes-minimas-e-git.mdc`](../.cursor/rules/alteracoes-minimas-e-git.mdc) em cada PR.

## Estado atual (referencia)

- PHP >= 7.4, CakePHP 3.10, PostgreSQL.
- Autorizacao: RBAC + ABAC (`config/rbac.php`, `config/abac.php`).
- Testes: `composer test-rbac`, `composer fiscal-verify` (ver `CLAUDE.md`).

## Fase A — PHP 8.x no CakePHP 3

1. Subir ambiente de CI e local para **PHP 8.0 ou 8.1** (Cake 3.10 suporta PHP 8 com ajustes pontuais).
2. Corrigir deprecations (tipos, `null` safety, assinaturas).
3. Manter `composer test-rbac` verde em todas as branches de migracao.

**Saida:** aplicacao a correr em PHP 8 sem alterar major do framework.

## Fase B — CakePHP 4.x

1. Ler o [migration guide oficial](https://book.cakephp.org/4/en/appendices/4-0-migration-guide.html): middleware, ORM, plugins (`Migrations`, `Bake`, `DebugKit`).
2. Migrar entrada HTTP (`webroot/index.php`, bootstrap), depois ORM (entities/tables), depois Auth (Component → Authentication/Authorization plugins ou equivalente).
3. Reexecutar suites de integracao HTTP (`tests/bootstrap_http.php`).

**Saida:** CakePHP 4 em producao com paridade funcional.

## Fase C — CakePHP 5 (opcional, longo prazo)

Avaliar apos estabilizacao em Cake 4; depende de ecossistema de plugins.

## Refactors de aplicacao (em paralelo, baixo risco)

### APIs ERP duplicadas (nomes de actions)

- Centralizar lista de controllers/actions em `App\Utility\ErpApiRoutes` (feito para CORS, `Security` e `isAuthorized`).
- Evitar duplicar variantes `addApi` / `addAPI` nos sitios; usar sempre os metodos PHP reais (`addAPI`, `listAPI`, `refreshAPI`).

### Regras de negocio em servicos

- Extrair blocos longos de controllers (ex.: fusao de empresas, integracoes SOAP) para classes em `src/Service/` com testes unitarios.
- Manter controllers como orquestracao fina (request/response, Auth, flash).

## Composer e seguranca

- Rever periodicamente `composer audit` e entradas em `composer.json` → `config.audit.ignore`.
- Atualizar `cakephp/plugin-installer` quando o ecossistema permitir (ver notas em `docs/HARDENING_PRODUCAO_PORTAL.md`).

## Prioridade recomendada

1. PHP 8 + testes verdes.
2. CakePHP 4 + paridade RBAC/fiscal.
3. Refactors de servicos por modulo (orcamentos, fiscal, financeiro) conforme necessidade de manutencao.
