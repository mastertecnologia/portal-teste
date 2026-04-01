# Fase 0 — Fundação dos módulos novos (referência versionada)

**Objetivo:** registrar decisões técnicas mínimas antes de implementar Histórico, Contratos/Faturas (ERP e Portal) e Relatórios.  
**Relaciona:** `DOCUMENTO_MESTRE_MODULOS.md`, `DOC2_MAPA_MENUS.md`, `SPEC_*`, `index_estrutural.html`.

---

## 1. Convenção de URLs (recomendada para implementação)

Evitar dispersão entre `DOCUMENTO_MESTRE` e `DOC2`: usar **um** padrão por ambiente.

| Contexto | Controller sugerido | Rota Cake (exemplo) | Notas |
|----------|---------------------|---------------------|--------|
| ERP — histórico | `HistoricoController` | `/historico/index`, `/historico/view/:id` | Mantém `TicketsController` focado em fila operacional. |
| Portal — histórico | mesmo `HistoricoController` | `/historico/cliente` (action `cliente`) | `idcliente` / `idempresa` só da sessão; filtrar comentários internos no controller. |
| ERP — contratos visão unificada | `ContratosController` **ou** `ClicontratosController::index` enriquecido | `/contratos/index` **ou** `/clicontratos/index` | Escolher uma opção e manter; evitar duas listagens concorrentes. |
| Portal — contratos/faturas | `PortalController` ou `ContratosController` + filtro role | `/portal/contratos`, `/portal/faturas` (ou prefixo acordado) | Campos sensíveis: ver SPEC Doc 5 / cabeçalho do `index_estrutural`. |
| ERP — relatórios hub | `RelatoriosController` | `/relatorios/index` + sub-actions | `AppController` já mapeia `relatorios` → `relActive`. |
| Portal — relatórios | mesma família | `/portal/relatorios` ou action dedicada | Apenas agregados permitidos ao cliente. |

**Rotas:** declarar entradas explícitas em `config/routes.php` quando houver prefixos amigáveis (`/portal/...`) ou DashedRoute ambíguo.

---

## 2. `AppController` — variáveis de menu

Hoje existem `relActive` e o mapa `'relatorios' => 'relActive'`.

**A acrescentar na implementação:**

- Em `$menuStates`: `historicoActive`, `contratosActive` (nomes podem ser ajustados ao HTML do sidebar).
- Em `$controllerToMenuMap`: por exemplo `'historico' => 'historicoActive'`, `'contratos' => 'contratosActive'`.

**Regra:** só publicar links no `sidebar.ctp` / menu cliente quando a action correspondente existir e responder sem erro.

---

## 3. RBAC / `permissions_registry.php`

Definir códigos de permissão **antes** de expor URLs sensíveis. Exemplos (ajustar à matriz real):

| Código sugerido | Uso |
|-----------------|-----|
| `historico.index` / `historico.view` | ERP listagem e detalhe |
| `historico.cliente` | Portal cliente |
| `contratos.index` / `contratos.portal` | ERP vs portal |
| `relatorios.index` | Hub relatórios ERP |
| `relatorios.tickets` / `sla` / `contratos` / `financeiro` | Sub-relatórios conforme SPEC |

Reutilizar migrations/papéis existentes (`ClientPortalRbacPapel`, etc.) quando couber.

---

## 4. Layout Portal

Views sob `src/Template/Portal/*` devem usar o layout cliente (`client.ctp` ou `portal.ctp` se for criado). Protótipos `index_estrutural.html` assumem layout pai — converter para `.ctp` sem `<html>` duplicado quando o layout Cake já fornecer cabeçalho.

---

## 5. Relatórios — reutilização

Já existe fluxo em **Ordens de Serviço** (`relatorios`, `relatorio_ver`, `relatorio_pdf`). Na Fase de Relatórios ERP:

- **Reutilizar ou delegar** queries/PDF já estáveis;
- Evitar segundo módulo com a mesma regra de negócio sem necessidade.

---

## 6. Ordem global (recordatório)

1. Fase 0 (este documento + rotas + permissões + menu map)  
2. Histórico ERP → Histórico Portal  
3. Contratos/Faturas ERP → Portal  
4. Relatórios ERP → Portal  
5. Polish e priorização de sub-relatórios  

---

## 7. Histórico de alterações

| Data | Alteração |
|------|-----------|
| 2026-03-31 | Criação do documento (decisões Fase 0 para versionamento no repositório). |
