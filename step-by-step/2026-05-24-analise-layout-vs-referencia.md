# Step-by-step — Análise de divergência entre páginas e arquivo de referência

Data: 2026-05-24  
Solicitação: "analise todo o projeto e identifique porque minhas paginas nao estao de acordo com o arquivo de referencia."

## 1) Entendimento inicial
- Objetivo: identificar a causa-raiz de divergência visual entre páginas do portal e o arquivo de referência.
- Escopo técnico analisado:
  - Layouts CakePHP (`src/Template/Layout/*.ctp`)
  - Elementos globais de shell (`src/Template/Element/*`)
  - Controllers que trocam layout (`setLayout(...)`)
  - CSS de shell premium e CSSs legados
  - Documentação que define qual é o arquivo de referência

## 2) Descoberta do arquivo de referência canônico
### Evidências
- `docs/referencias/README.md`: define `pgm_erp_completo_2.html` como arquivo canônico.
- `config/portal_ui.php`: `reference_html.primary = docs/referencias/pgm_erp_completo_2.html`.
- `docs/reference/README.md`: confirma fallback e extratos por módulo.

### Conclusão
- Referência principal do ERP completo: `docs/referencias/pgm_erp_completo_2.html`.
- Referência específica de orçamentos: `pgm_orcamentos_premium.html` (raiz / docs/reference).

## 3) Mapeamento do shell visual em produção
### Arquivos e utilidade
- `src/Template/Layout/default.ctp`
  - Função: layout principal de equipe (e parte do portal cliente por role).
  - Utilidade: compõe sidebar, topbar premium, container principal, turbo-frame e carrega CSS base.
- `src/Template/Layout/client.ctp`
  - Função: layout alternativo do portal cliente (Material).
  - Utilidade: deveria aplicar classe `pgm-portal-client-shell`.
- `src/Template/Layout/orcamentos.ctp`
  - Função: layout dedicado para fluxo público de orçamento.
  - Utilidade: shell mínimo com topbar + conteúdo, sem sidebar ERP completa.
- `src/Template/Element/pgm_shell_topbar.ctp`
  - Função: topbar premium padrão.
  - Utilidade: breadcrumb/título + data/avatar.
- `src/Template/Element/content.ctp`
  - Função: zona de conteúdo.
  - Utilidade: define `#pgm-dynamic-content`, região de scroll do shell premium.
- `public/dist/css/pages/pgm-app-shell-premium.css`
  - Função: contrato visual do shell premium.
  - Utilidade: controla topbar, altura, scroll, e comportamento por classe de body.

## 4) Causas encontradas para divergência com a referência
1. **Layout inconsistente por módulo/rota**
   - Nem todas as páginas usam o mesmo layout.
   - Ex.: `OrcamentosController::viewhash` usa `orcamentos`, enquanto outras ações ficam no `default`.
   - Ex.: `ServicedeskController` usa `servicedesk` (shell diferente) e técnicos podem ser redirecionados para `ServicedeskPrototype`.

2. **Layout `client.ctp` existe, mas não está sendo usado nas actions**
   - Não foram encontrados `setLayout('client')` nos controllers.
   - Resultado: o portal cliente roda no `default.ctp` com classe `pgm-portal-client` (não `pgm-portal-client-shell`).
   - Impacto: parte das regras CSS premium específicas do cliente não é acionada como desenhado.

3. **Conflito entre contrato premium e estilos legados**
   - `pgm-app-shell-premium.css` esconde `.row.page-titles`.
   - Existem CSSs de módulos que ainda estilizam `.page-titles`, gerando percepção de desalinhamento.

4. **Múltiplos shells coexistindo**
   - `default`, `orcamentos`, `servicedesk`, `erp_prototype`, `ajax`, `print`, `layout(false)`.
   - Cada shell possui estrutura DOM e carga de CSS diferente; comparação com um único HTML de referência tende a falhar.

5. **Versões/carga de CSS variando entre layouts**
   - `default.ctp` usa `pgm-app-shell-premium.css?v=7`.
   - `client.ctp` e `orcamentos.ctp` usam `?v=2`.
   - Diferença de versão pode refletir comportamento visual distinto.

## 5) Resultado final da análise
- A divergência não aponta para “um arquivo quebrado”, e sim para **falta de padronização de layout/shell** frente ao mock canônico.
- O sistema está em estado híbrido (legacy + premium + prototype), então páginas diferentes seguem contratos visuais diferentes.

## 6) Próximos passos recomendados (sem alteração de regra de negócio)
1. Definir oficialmente qual shell será padrão por contexto (ERP interno, portal cliente, orçamentos, servicedesk).
2. Eliminar ambiguidade do portal cliente (decidir entre `client.ctp` vs `default.ctp` + `pgm-portal-client`).
3. Unificar a versão de `pgm-app-shell-premium.css` nos layouts que devem compartilhar o mesmo comportamento.
4. Revisar CSSs de módulos que dependem de `.page-titles` quando o premium está ativo.
5. Validar páginas críticas com checklist de layout (layout ativo, classes de body, topbar, `#pgm-dynamic-content`, CSS carregado).
