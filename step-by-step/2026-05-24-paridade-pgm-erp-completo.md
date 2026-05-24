# Paridade Portal × `pgm_erp_completo_2.html` — diagnóstico

> Data: 2026-05-24  
> Branch: `cursor/diagnostico-paridade-pgm-erp-completo-928e`  
> Tipo: análise (não altera código de runtime)  
> Fonte da verdade visual: `docs/referencias/pgm_erp_completo_2.html` (124 telas `pg-*`)

## 1. O que esse arquivo faz

Documento exclusivamente de **diagnóstico**. Lista por que as páginas servidas hoje pelo portal (URLs legadas) **não** correspondem ao desenho do arquivo de referência, mesmo existindo telas premium implementadas.

Não há alteração de controller, view ou rota nesta entrega — só o relatório passo-a-passo.

## 2. Resumo executivo

O portal **tem** uma camada premium fiel ao mockup, mas ela está **estacionada num conjunto paralelo de URLs `/{modulo}-prototype/*`**. As URLs do dia-a-dia (`/clientes`, `/orcamentos`, `/ordensservico`, `/financeiro`, `/empresas`, etc.) continuam batendo nos controllers/views legados, que usam outro layout, outras classes CSS e outro design — daí a sensação de “as páginas não estão de acordo com o arquivo de referência”.

O mecanismo de **switchover** (`PortalUi::redirectToPrototypeIfEnabled` + `.env PORTAL_PREMIUM_MODULES`) foi escrito, mas **nunca foi ligado** a nenhum controller. A sidebar legada continua apontando para as rotas legadas (com exceção do Service Desk).

## 3. Evidências (apuração no repositório)

### 3.1 Existem duas pilhas visuais paralelas

| Camada | Layout | CSS principal | Onde está | Bate com referência? |
|---|---|---|---|---|
| Legado (default) | `src/Template/Layout/default.ctp` (linhas 44 e 57) | `dist/css/style.min.css` + `pgm-app-shell-premium.css` | URLs `/clientes`, `/orcamentos`, `/financeiro`, … | Não |
| Premium prototype | `src/Template/Layout/erp_prototype.ctp` | `webroot/dist/css/pgm-erp-prototype.css` (678 linhas) | Apenas URLs `/{modulo}-prototype/*` | Sim |

Comparei `src/Template/OrcamentosPrototype/lista.ctp` com o `pg-lista` do mock (linhas 1034–1084 de `docs/referencias/pgm_erp_completo_2.html`): estrutura (`stats`, `tabs`, `tbl-wrap`, badges `b-v` etc.), classes e cores são equivalentes. O conteúdo premium **está fiel** — só não é o que os utilizadores recebem nas URLs default.

### 3.2 As rotas `*-prototype` existem mas só uma chega via sidebar

```text
config/routes.php → /bancos-prototype, /clientes-prototype, /empresas-prototype,
                    /financeiro-prototype, /fornecedores-prototype,
                    /orcamentos-prototype, /ordens-prototype, /pcp-prototype,
                    /produtos-prototype, /servicedesk-prototype, /sistema-prototype
```

Só o **Service Desk** é exposto no menu lateral premium:

```169:172:src/Template/Element/sidebar.ctp
<div class="nav-section-items">
    <?php if (($sg['tickets_servicedesk'] ?? true)) : ?>
    <?= $pgmSbLink('headphones', ' Service Desk', '/servicedesk-prototype', ['data-turbo' => 'false'], $ticketsServicedeskActive, '', 'Service Desk') ?>
    <?php endif; ?>
```

Todos os outros itens da sidebar continuam apontando para os controllers legados (Clientes, Orçamentos, Produtos, Financeiro, Empresas, etc.). Resultado: o utilizador final nunca vê o premium, a não ser que digite a URL `*-prototype` manualmente.

### 3.3 O switchover por `.env` foi escrito mas nunca usado

- `config/portal_ui.php` lê `PORTAL_UI_MODE` e `PORTAL_PREMIUM_MODULES` (variáveis em `.env.example` linhas 96–98).
- `config/bootstrap.php` carrega esse arquivo (`Configure::load('portal_ui', 'default', false);`).
- `src/Utility/PortalUi.php` expõe `isPremiumModule()` e `redirectToPrototypeIfEnabled()`.
- **Mas** uma busca em `src/Controller` por `PortalUi` retorna **zero** ocorrências — nenhum controller chama o helper.

Ou seja, mesmo escrevendo `PORTAL_PREMIUM_MODULES=clientes,orcamentos,financeiro` em `.env`, a aplicação não redireciona `/clientes` → `/clientes-prototype`. O env não tem efeito.

### 3.4 Cobertura premium é parcial vs. as 124 telas `pg-*`

O `bin/audit_pgm_erp_mock.php` mantém manualmente uma tabela `$implemented`:

```52:64:bin/audit_pgm_erp_mock.php
$implemented = [
    'servicedesk' => 18,
    'orcamentos' => 2,
    'ordens' => 2,
    'clientes' => 4,
    'produtos' => 3,
    'fornecedores' => 1,
    'financeiro' => 4,
    'bancos' => 2,
    'empresas' => 2,
    'sistema' => 5,
    'pcp' => 1,
];
```

Soma ≈ **44 telas premium** entregues × **124 no mock** (~35 %). Lacuna grande mesmo dentro do escopo aprovado em `docs/MIGRACAO_PGM_ERP_COMPLETO.md`. Além disso:

- **PCP (13 telas)** foi explicitamente **excluído** do escopo do mock (decisão 19/05/2026).
- Vários templates dentro das pastas `*Prototype/` são `placeholder.ctp` / `pc_placeholder.ctp` / `inv_placeholder.ctp` (stubs).
- Vários `view($page)` em controllers prototype fazem **bridge** para o módulo clássico (ex.: `Clientes/visao360`, `Produtos/edit`, `Faturamento/index`). Quando o utilizador clica nesses atalhos dentro do shell premium, cai numa view legada que **não bate** com a referência.

### 3.5 Falta CSS de página dentro do shell premium

O plano em `docs/MIGRACAO_PGM_ERP_COMPLETO.md` previa um `webroot/dist/css/pages/pgm-{modulo}-prototype.css` por módulo. Em `webroot/dist/css/pages/` só existe **`pgm-servicedesk-prototype.css`**. Os outros módulos premium herdam apenas o shell genérico (`pgm-erp-prototype.css`), perdendo ajustes finos do mock (margens, paginação rica, KPIs específicos, etc.).

### 3.6 Topbar / multi-empresa não é o do mock

A topbar atual vem de `src/Template/Element/pgm_shell_topbar.ctp` (carregada pelo `default.ctp` quando `pgm-app-shell-premium` está ativo) — desenho próprio do portal, baseado em `pgm-app-shell-premium.css`. O **seletor multi-empresa fixo no topbar** descrito no mock só é visto dentro do layout `erp_prototype.ctp` (via `Element/ErpPrototype/topbar.ctp`), portanto também só nas URLs `*-prototype`.

## 4. Por que as páginas não estão “de acordo com o arquivo de referência” — causas, em ordem

1. **A pilha premium nunca substituiu a pilha legada.** Foi entregue lado-a-lado e o switchover só foi acionado no Service Desk (item de sidebar real). Os demais módulos só existem premium para quem souber a URL `*-prototype`.
2. **O helper de switchover (`PortalUi::redirectToPrototypeIfEnabled`) não está cabeado em nenhum controller legado.** Configurar `.env` não muda nada.
3. **A sidebar legada continua apontando para as rotas legadas** em Clientes, Produtos, Orçamentos, OS, Financeiro, Bancos, Empresas, Sistema.
4. **O layout legado `default.ctp` carrega outro CSS** (`style.min.css` + `pgm-app-shell-premium.css`), com identidade visual distinta do `pgm-erp-prototype.css`.
5. **Mesmo dentro do premium, várias telas ainda são placeholder ou bridge para o legado**, então a navegação “salta” para views clássicas que não obedecem ao mock.
6. **Cobertura parcial** (~35 % das 124 telas `pg-*`); PCP fora do escopo; vários módulos sem o CSS de página específico previsto em `webroot/dist/css/pages/pgm-{modulo}-prototype.css`.

## 5. Próximos passos sugeridos (sem aplicar agora)

Mantendo a regra de **alterações mínimas e lado-a-lado**, sugiro um caminho seguro:

1. **Cabearar `PortalUi::redirectToPrototypeIfEnabled`** em cada `*Controller::beforeFilter()` dos módulos legados (Clientes, Orcamentos, Ordensservico, Produtos, Financeiro, FinanceiroBancos, Empresas, Sistema) — um redirect curto controlado por `PORTAL_PREMIUM_MODULES`. Mantém legado intacto quando o flag está vazio.
2. **Trocar os `pgmSbLink` da sidebar legada** para apontarem às rotas `*-prototype` apenas quando `PortalUi::isPremiumModule(...)` for `true`. Sem o flag, sidebar continua como hoje.
3. **Fechar a cobertura por módulo** seguindo a lista em `docs/MIGRACAO_PGM_ERP_COMPLETO.md` — começar pelos placeholders já existentes (Empresas/`nova`, Sistema/`config`, Bancos/`conciliacao`, Produtos/`inv_historico`, Orcamentos/`wizard_*` finais).
4. **Criar `webroot/dist/css/pages/pgm-{modulo}-prototype.css`** por módulo, conforme plano da Fase 0–5; carregar a partir da própria view (não do layout) para não inchar shell genérico.
5. **Substituir os bridges para módulos clássicos** por views premium dedicadas, módulo a módulo, com `view($page)` deixando de redirecionar para `/Clientes/visao360` etc.
6. **Validar com auditoria**: `php bin/audit_pgm_erp_mock.php` e `bash bin/verify_prototype_bridges.sh` em cada PR de fase.

## 6. Reflexão (escalabilidade × manutenibilidade)

A arquitetura escolhida é boa para **risco baixo** (legado preservado, switchover por env), mas a falha de não plugar o helper em controllers/sidebar fez com que o esforço de implementar 44 telas premium fique **invisível** para o utilizador final. O custo de “ligar” esse caminho é pequeno (poucas linhas em `beforeFilter` + condicionais na sidebar), mas o ganho percebido é grande: as URLs default passam a renderizar o desenho do mock para os módulos já prontos.

A longo prazo, evitar **bridges premium → legado**: cada salto de volta ao legado destrói a sensação de paridade visual. Vale priorizar fechar fluxo a fluxo dentro do shell `erp_prototype` antes de marcar um módulo como “premium switched on”.

## 7. Estrutura desta investigação

| Etapa | Comando / arquivo lido | Conclusão |
|---|---|---|
| Inventário das telas no mock | `grep -oE 'id="pg-[a-z0-9-]+"' docs/referencias/pgm_erp_completo_2.html` | 124 telas únicas |
| Inventário das rotas prototype | `grep -oE '/[a-z0-9-]+-prototype' config/routes.php` | 11 módulos com rota prototype |
| Cobertura entregue | `bin/audit_pgm_erp_mock.php` (tabela `$implemented`) | ≈ 44 telas |
| Switchover por env | `config/portal_ui.php`, `src/Utility/PortalUi.php` | Helper existe |
| Uso do helper | `grep -rn 'PortalUi' src/Controller/` | **0 ocorrências** |
| Sidebar legada | `src/Template/Element/sidebar.ctp` | Só Service Desk usa `-prototype` |
| Layout legado | `src/Template/Layout/default.ctp` linhas 44 e 57 | Outro CSS, outra identidade |
| Layout premium | `src/Template/Layout/erp_prototype.ctp` | Carrega `pgm-erp-prototype.css` (alinhado ao mock) |
| CSS por página premium | `webroot/dist/css/pages/*prototype*` | Só `pgm-servicedesk-prototype.css` |
