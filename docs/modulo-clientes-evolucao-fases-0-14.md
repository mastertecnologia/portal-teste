# Módulo Clientes — evolução incremental (fases 0 a 14)

Documentação técnica curta da entrega alinhada ao plano de refatoração gradual (CakePHP + PostgreSQL, produção).

---

## 1. Resumo do que foi alterado (visão geral)

- **Navegação da ficha:** abas extraídas para `Element/Cli/edit_tabs_nav.ctp` + deep-link por hash (`Element/Cli/edit_tabs_js.ctp`).
- **Subtelas (UI):** Acessos (subcards), Usuários (tabela + atalho), Contratos (coluna situação + estilos), Token (blocos leitura/ação), rodapé em blocos.
- **Regra de situação de contrato:** centralizada no `ClientesController` (`_clicontratoValidadeYmd`, `_clicontratoRowUi`, `$contratosRowUi`) alinhada ao resumo do rodapé.
- **Integração ERP:** SOAP `GerenciaCliente` movido para `ClienteErpSyncService` (controller só delega + Flash).
- **Notificações:** carregamento em lote de preferências in-app e e-mail + e-mails dos usuários em `PortalNotificationService` / `MailAutomationService`.
- **Front modular (1º passo):** `webroot/js/modules/clientes/cliente-edit.js` (helpers de e-mail + `pgmCsrfToken`); POST `verificasenha` com `_csrfToken`.
- **Correções/contexto anterior:** shell escuro `Pgm/form_shell_dark`, notificações (URLs), etc. (histórico em commits anteriores na mesma linha).

---

## 2. Arquivos criados ou alterados (principais)

| Área | Caminhos |
|------|-----------|
| Controller cliente | `src/Controller/ClientesController.php` |
| Views cliente | `src/Template/Clientes/edit.ctp`, `add.ctp`, … (foco em `edit.ctp`) |
| Elements Cli | `src/Template/Element/Cli/edit_tabs_nav.ctp`, `edit_tabs_js.ctp`, `ui_css.ctp`, `card*.ctp`, `input.ctp`, `select.ctp`, … |
| Shell forms | `src/Template/Element/Pgm/form_shell_dark.ctp` |
| Integração ERP | `src/Service/ClienteIntegration/ClienteErpSyncService.php` |
| Domínio / notificações | `src/Service/ClienteDomain/PortalNotificationService.php`, `MailAutomationService.php`, `ClienteDomainBridge.php`, `ClienteDomainCronService.php` |
| Portal notificações | `src/Controller/PortalNotificationsController.php`, `src/Template/PortalNotifications/preferences.ctp` |
| JS | `webroot/js/modules/clientes/cliente-edit.js` |
| Rotas (API legado) | `config/routes.php` (trechos clientes / clicontratos / relatórios cliente) |
| Usuários cliente | `src/Template/Users/editcliente.ctp`, `addcliente.ctp` |

---

## 3. Migrations

- **Nenhuma migration nova** foi exigida por este pacote de UI/services descritos acima.  
- Funcionalidades de notificações/domínio continuam dependendo das tabelas já previstas pelo módulo (`InfrastructureGuard` / migrations pré-existentes do portal).

---

## 4. Rotas alteradas ou adicionadas (referência)

- **Não** houve mudança de assinatura das rotas centrais de `Clientes/edit`, `Cliacessos`, `Clicontratos`, `Users/editcliente`.  
- Rotas de **API** (`/clientes/add-api`, `list-api`, …) e aliases legados **mantidos**.  
- Deep-link na ficha: apenas **fragmento** `#cliente`, `#contratos`, etc. (sem nova rota).

---

## 5. Services / helpers / elements / JS criados

- `ClienteErpSyncService` — sincronização cliente → ERP.  
- `PortalNotificationService` — batch de prefs in-app.  
- `MailAutomationService` — batch de prefs e-mail + e-mails de usuários.  
- `Cli/edit_tabs_nav`, `Cli/edit_tabs_js` — abas da ficha.  
- `Pgm/form_shell_dark` — layout escuro reutilizável em formulários satélites.  
- `webroot/js/modules/clientes/cliente-edit.js` — `PgmClienteEditUtils`.

---

## 6. Riscos conhecidos

- **Ficha `edit.ctp`:** ainda contém **muito JS inline**; evolução futura pode mover trechos para `cliente-contracts.js` / `cliente-token.js` com objeto de config gerado na view.  
- **`Users::verificadadoscliente`:** endurecido para **POST** com corpo (`idcliente`, `nomeresponsavel`, `cpf`, `rg`, `_csrfToken`); exige **sessão** (removido de `Auth->allow`); mitigação **IDOR** (cliente só o próprio `idcliente`; equipe só cliente da mesma `idempresa`).  
- **APIs públicas** (`Auth->allow` em `addAPI`/`listAPI`): dependem de token de empresa; não fazem parte desta entrega de UI, mas são superfície crítica em auditoria.  
- **Opt-in de e-mail** em preferências: sem linha salva = **não** envia e-mail (comportamento atual preservado).

---

## 7. Pendências sugeridas (não bloqueantes)

- Extrair mais JS da ficha para `webroot/js/modules/clientes/*`.  
- Endpoints JSON internos com validação explícita e política CORS clara, se expostos além do portal.  
- Testes automatizados de smoke para `ClienteErpSyncService` com mock SOAP (ambiente isolado).

---

## 8. Próximos passos recomendados

- Fase de **hardening** focada em: sanitização de saída em todas as células de tabelas legadas, revisão de `SecurityComponent`/`CsrfProtectionMiddleware` por action, e log estruturado em falhas ERP.  
- Documentar no runbook de deploy a ordem: código → limpar cache de views → validar SOAP WSDL por empresa.

---

## 9. Checklist final de testes manuais (regressão rápida)

- [ ] `Clientes/edit/{id}` — equipe: todas as abas, hash, salvar, sincronização ERP.  
- [ ] Portal cliente (permissão): abas permitidas, token somente leitura.  
- [ ] Preferências de notificações: salvar, depois disparar evento de teste (homologação).  
- [ ] Revelar senha de acesso (POST com CSRF).  
- [ ] `Users/addcliente` / `editcliente` (form shell).  
- [ ] Lista/API clientes com token (se usado em produção).

---

## 10. Observações para deploy

- Incluir **`webroot/js/modules/clientes/cliente-edit.js`** no artefato (não é só PHP).  
- Após deploy, **limpar cache** de views/opcache conforme rotina do ambiente.  
- Monitorar logs `error`/`warning` para `ClienteErpSyncService` e `ClienteDomainBridge`.  
- PostgreSQL: nenhum script DDL novo deste pacote; validar conexão e permissões como de costume.

---

## ETAPA 13 — Revisão de segurança e compatibilidade (checklist)

| Item | Status / nota |
|------|----------------|
| CSRF em formulários Cake | Mantido; POST `verificasenha` passou a enviar `_csrfToken` via `PgmClienteEditUtils`. |
| Métodos HTTP | `savePreferences`, `verificasenha` como POST; endpoints JSON com `allowMethod` onde aplicável. |
| Permissões / RBAC | `ClientesController::edit` e `_findClienteForCurrentUser` + `Abac`; `PortalNotificationsController` restrito a staff (`role !== 0` em `isAuthorized`). |
| Escape em views | Contratos: uso ampliado de `h()` em campos de texto; modais e e-mails com helpers existentes. |
| JSON interno | Notificações: respostas tipadas; URLs normalizadas no controller de notificações (trabalho anterior). |
| PostgreSQL | Sem queries novas complexas além de `IN (...)` em batches de prefs (parâmetros via ORM). |
| Integrações | ERP: mesma chamada SOAP, apenas relocada; falhas ainda emitem eventos de domínio. |
| Rotas legadas | Preservadas; deep-link só `#fragment`. |

**Cenários críticos a revalidar em homologação:** IDOR em `edit` com outro `idcliente`; token de empresa em API; expiração de sessão durante AJAX.

---

## ETAPA 14 — Esta documentação

- Arquivo: **`docs/modulo-clientes-evolucao-fases-0-14.md`** (este).  
- Commits recentes na linha de evolução: ver histórico `git log` em `main` (mensagens `refactor(clientes)`, `feat(clientes)`, etc.).
