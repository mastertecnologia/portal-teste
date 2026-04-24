# Service Desk — Timeline B: de-para, segurança e rollback

Este ficheiro descreve o que a entrega altera, o mapeamento (de-para) com o plano, como fazer **backup reversível** e como **reverter** sem editar ficheiro de plano do Cursor.

## O que *não* mexe (compatível com o sistema em uso)

- `AppController` / fluxo de **login** e autenticação geral: sem alteração para esta entrega.
- **Layouts** Cake (portal legado) fora do módulo de tickets: nenhum `Layout/default.ctp` global foi substituído; `Servicedesk` continua a usar o **mesmo** layout de servedesk onde já se aplicava `?sd=1`.
- **Tabelas legadas** `ticketcomentarios`, `ticketsmovs`, `ticketshoras`, `ticket_histories`: **não removidas**; previsto **dual-write** (comentários) e leitura mesclada na API de timeline.
- Novas tabelas são **aditivas** (`ADD COLUMN IF NOT EXISTS` / `IF NOT EXISTS` no PostgreSQL).

## De-para (especificação do plano → implementação)

| Plano | Implementação no código |
|--------|-------------------------|
| `ticket_events` (timeline) | `config/Migrations/20260429120000_ServiceDeskTimelineBSchema.php` + `TicketEventsTable` |
| `holidays` + `BusinessHoursService` + SLA comercial | `HolidaysTable`, `src/Service/Ticket/BusinessHoursService.php`, prazos em `SlaService` |
| Worklog + billing (commercial/extra/holiday) | `TicketEventsTable::afterSave` (worklog) + `ContractBillingContext` lógica em `resolveHourlyRate` |
| Auto-auditoria em `Tickets` | `TicketsTable` (eventos `type = audit`) em paralelo a `TicketHistoryLogger` (transição) |
| `ticket_products` + estoque atómico | `TicketProductsTable` + `apiAddTicketProduct` (transação com `UPDATE` condicional) |
| Geo + timer | colunas `clientes.latitude/longitude/geo_validacao_raio_m` + `apiValidateGeolocation` + validação no fluxo de timer (técnico) |
| Assinatura / evidência | `apiTicketSignature`, `apiAddEvidencePhoto` (ficheiros via `dirAnexos` existente; limites de tamanho no servidor) |
| API JSON timeline | `apiTimeline` + `TicketServiceDeskApiService` (merge com legado) |
| React (bundle `tickets-app`) | `dashboard-react` (fonte); `npm run build` gera `webroot` + `public` |
| PDF OS + Laudo (mPDF) | `src/Template/Servicedesk/pdf_os.ctp`, `pdf_laudo.ctp` (caminho alinhado à pasta do projeto) |
| RBAC | `20260429120100_RbacServiceDeskTimelineActionsPatch.php` + `config/rbac.php` (`rbac_api_enforced_actions`) |

**Correção importante (Linux/CI):** o caminho de view deve ser `Servicedesk` (igual à pasta `src/Template/Servicedesk/`), não `ServiceDesk`.

## Segurança (verificação rápida)

- Ações `api*`: `isAuthorized` restringe por `role` (técnico 0 / cliente 1) conforme cada endpoint; JSON sensível a técnico fica com `role === 0` onde o plano exige.
- `rbac.php` regista ações forçadas para RBAC alinhado a permissões `tickets.*`.
- SQL de estoque usa **parâmetros** (`:q`, `:id`, etc.), não concatenação de input do utilizador.
- Uploads base64: rejeição se imagem vazia/ inválida; **limite de tamanho** (assinatura ~2,5MB; evidência ~5MB) para reduzir abuso/DoS de memória.
- Pistas em disco: `dirAnexos` e prefixo de empresa, consistente com anexos existentes.

## Backup reversível (recomendado *antes* de `migrations migrate` em produção)

1. **Base de dados**
   - `pg_dump` (ou export do ambiente) com timestamp no nome, por exemplo: `backup_antes_timelineB_YYYYMMDD.sql`.
2. **Código (Git)**
   - Com histórico limpo, criar **tag** apontando ao commit aprovado:  
     `git tag -a backup/pre-servicedesk-timeline-YYYYMMDD "antes migrate timeline B" HEAD`  
   - Com alterações locais ainda **não** commitadas, usar *antes*:  
     `git stash push -u -m "WIP serv desk timeline B"`  
     ou fazer **commit** num branch e depois tag nesse commit.
3. O script `scripts/backup-servicedesk-timeline.ps1` automatiza a **tag** (e avisa se o working tree estiver sujo).

## Rollback (reverter)

### Código
- `git reset --hard <commit-anterior-à-tag>` ou `git checkout backup/pre-servicedesk-timeline-...` (conforme fluxo de equipa) e reinstalar dependências se necessário.
- `git stash pop` se usou `stash`.
- Rebuild do `dashboard-react` se voltar atrás: `cd dashboard-react && npm run build`.

### Base de dados (Phinx)
- `bin/cake migrations rollback` (última batch), **ou**  
- Chamar o `down()` da migration `20260429120000_ServiceDeskTimelineBSchema` via rollback Phinx.  
  O `down()` **apaga** as tabelas novas (`ticket_events`, `holidays`, etc.) e **não** remove as colunas adicionais em `clientes` e `produtos` (ficam órfãs se quiser *limpeza* total: ver SQL manual abaixo).

### Rollback do patch RBAC
- A migration `20260429120100_RbacServiceDeskTimelineActionsPatch` tem `down()` vazio.  
- Reversão: restaurar a cópia de `rbac_permissions` a partir do dump, ou reverter à mão a coluna `action` dos códigos `tickets.view`, `tickets.update`, `tickets.timer` (remover as substrings adicionadas: `apitimeline`, `apivalidategeolocation`, `apiticketsignature`, etc.) a partir de um de-para exportado *antes* da alteração.
- Sincronizar com `config/rbac.php` (entrada `rbac_api_enforced_actions`) a versão anterior do ficheiro no Git.

### SQL opcional (só se quiser *remover* colunas de geo/estoque)
Executar em **ambiente de teste** primeiro; ajustar nomes reais de schema se diferirem.

```sql
-- PostgreSQL: exemplo (cuidado com dependências ainda em uso)
ALTER TABLE clientes DROP COLUMN IF EXISTS latitude;
ALTER TABLE clientes DROP COLUMN IF EXISTS longitude;
ALTER TABLE clientes DROP COLUMN IF EXISTS geo_validacao_raio_m;
ALTER TABLE produtos DROP COLUMN IF EXISTS estoque_atual;
```

## Terceira revisão (CSRF / Security)

- As actions JSON novas em `TicketsController` que usam **POST** com `fetch` (sem `_Token` do FormHelper) precisam constar em `AppController` → componente **Security** → `unlockedActions` (padrão já usado por `apiTimer`, `apiView`, etc.). Sem isso o pedido pode ser bloqueado (blackhole).
- Incluídas: `apiTimeline`, `apiValidateGeolocation`, `apiTicketSignature`, `apiAddTicketProduct`, `apiAddEvidencePhoto`, `apiPdfTicketOs`, `apiPdfLaudo` (GET em geral não sofre o mesmo bloqueio, mas ficam alinhadas ao padrão das outras APIs de tickets).

## Verificação de consistência (segunda passagem)

- **TicketsTable::beforeSave:** havia duas funções com o mesmo nome (a segunda eliminava a sincronização `owner_id` / `idtecnico_responsavel` em PHP). Corrigido para **um único** `beforeSave` que faz owner + snapshot de auditoria.
- **Templates PDF:** path `setTemplatePath('Servicedesk')` alinhado à pasta real `src/Template/Servicedesk/` (evita falha em sistemas de ficheiros case-sensitive).
- **Uploads base64:** limites de tamanho em assinatura e evidência para reduzir risco de DoS por memória.

## Backfill legado (opcional, após migrate)

- Comando Cake (shell): `src/Shell/TicketEventsBackfillShell.php`
  - `bin/cake ticket_events_backfill comments --dry-run` — comentários `ticketcomentarios` → `ticket_events` (dedup por `metadata.ticket_comentario_id`)
  - `bin/cake ticket_events_backfill comments --empresa=1`
  - `bin/cake ticket_events_backfill worklogs --dry-run` — `ticketshoras` → worklogs (dedup por `metadata.ticketshoras_id`)
- Correr `--dry-run` antes em produção; volume grande: filtrar por `--empresa` em comentários.

## Plano de deploy (passo a passo, do mais seguro para produção)

### Fase 0 — Pré-requisitos

- **Código no repositório:** commit da feature no branch a usar; em cada ambiente, `git pull` (ou o vosso processo de deploy).
- **Front (só se alterarem fonte):** `cd dashboard-react` → `npm install` se necessário → `npm run build` (atualiza `webroot/tickets-app` e `public/tickets-app`). Se a pipeline já publica o bundle, este passo é na CI.
- **Stack:** sem dependências PHP novas obrigatórias além do que o projeto já usa (Cake, PostgreSQL, mPDF).

### Fase 1 — Backup (antes de `migrate` em produção)

1. **Base:** `pg_dump` (ex.: `pg_dump -Fc -f backup_pre_timelineB_YYYYMMDD.bak nomedb`) e guardar o ficheiro com política de restore testada.
2. **Código (referência):** `.\scripts\backup-servicedesk-timeline.ps1` **ou** `git tag -a deploy/pre-servicedesk-timeline-YYYYMMDD -m "antes migrate" HEAD` (e `git push --tags` se usarem).
3. Registar no ticket interno: **commit** + **caminho do dump**.

### Fase 2 — Staging / UAT (sempre antes de produção)

1. Mesmo *build* / commit aprovado que irá a produção.
2. Na raiz do projeto: `php bin\cake.php migrations migrate` (ajustar caminho a `php` no Windows) ou `php bin/cake.php migrations migrate` no Linux.
3. **Ordem Phinx:** ficheiros com prefixo de data `20260428…` (ex.: *Ticket audit*) correm **antes** de `20260429120000_…` e `20260429120100_…` porque o timestamp do nome impõe a ordem. O phinx aplica tudo o que ainda estiver *down*, por ordem crescente.
4. `php bin\cake.php migrations status` — sem pendências inesperadas; em caso de erro, **não** promover a produção até corrigir.
5. `php bin\cake.php cache clear_all` (ou equivalente do projeto).
6. **RBAC (staging):** com utilizador de equipa, abrir módulo de tickets/Service Desk; se usarem o catálogo de permissões, validar visibilidade das ações mapeadas em `tickets.view` / `tickets.update` / `tickets.timer` (patch `20260429120100_…`).

### Fase 3 — Dados (staging; repetir o que fizer sentido em produção)

- **Feriados (`holidays`):** tabela vazia — carregar feriados (nacionais e, se necessário, por `idempresa`) para o SLA e a classificação comercial/feriado fazerem sentido.
- **Geo:** `clientes.latitude`, `longitude`, `geo_validacao_raio_m` (opcional) para o fluxo de validação de proximidade com o timer.
- **Estoque:** `produtos.estoque_atual` — testar com quantidade mínima em staging antes de abrir a baixa em produção.

### Fase 4 — Backfill (opcional, após migrate)

- `php bin\cake.php ticket_events_backfill comments --dry-run` → rever números → `… comments` (ou `--empresa=ID` em blocos). Idem `worklogs` se necessário. Preferir janela de baixo tráfego em produção; volumes grandes fatiar por empresa.

### Fase 5 — Testes manuais (mínima)

- Timeline, comentário (dual-write), timer/geo, assinatura/PDF se usados, regressão de tickets *clássicos*, portal cliente sem alteração indevida em fluxos fora de `api*`. Ver tabela de testes abaixo na checklist “Testes pós-migrate”.

### Fase 6 — Produção

- Repetir **Fase 1** (dump + tag) na janela acordada.
- **Fase 2** (migrations + cache) no servidor de produção, mesmo commit que em staging.
- **Fase 3** (feriados, geo) conforme decisão pós-OAT.
- **Fase 4** (backfill) se ainda for preciso; sempre `--dry-run` antes.

### Fase 7 — Pós-deploy (24–48 h)

- Logs: erros 500 em `tickets/api-*`, mPDF, escrita de anexos; timeouts em PDF/upload.
- Utilizadores com RBAC: quem fica sem ação após o patch precisa ajuste de papeis (modo *enforce*).
- Comunicar equipa: novas tabelas; rollback (ver **Rollback** acima) não é “um clique” se já houver dados em `ticket_events`.

## Testes pós-migrate (checklist mínima)

- `bin/cake migrations status` — migrations aplicadas na ordem esperada.
- Técnico: timer com geo (se cliente tiver lat/lng), gravação de comentário (dual-write) e abertura de OS/PDF.
- Não regredir: listagem e edição de tickets clássicos; portal cliente inalterado fora de rotas `api*`.

---

*Ficheiro operacional: não substitui o registo de alterações/PR; serve como roteiro de rollback e de-para com o plano de produto.*
