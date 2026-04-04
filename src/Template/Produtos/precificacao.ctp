<?php
/**
 * Precificacao.ctp — Gestão de Preços / Custos (Produtos e Serviços)
 * Carrega produtos do BD + custos do ERP (SOAP), calcula preços via
 * 3 métodos: Markup, Fator Multiplicador, Fator Divisor.
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'produtos-premium']));
?>
<style>
/* ── Precificação: estilos específicos ─────────────────────────── */
.prec-root{display:flex;flex-direction:column;gap:0;background:var(--prd-bg);isolation:isolate;overflow:hidden;width:100%;max-width:100%;box-sizing:border-box;flex:1 1 auto;min-height:0;margin:0;color-scheme:dark;}
/* Zera scroll de página — layout app-like */
body.prec-screen-active{overflow:hidden!important;}
body.prec-screen-active .page-wrapper{overflow:hidden!important;width:100%!important;max-width:100%!important;}
body.prec-screen-active .container-fluid,
body.prec-screen-active .row{overflow:visible!important;padding:0!important;margin:0!important;}
/* Ocupa toda a largura da coluna principal (evita bloco “encostado” à esquerda com faixa vazia à direita) */
body.prec-screen-active .pgm-shell-main .page-wrapper > .container-fluid{
  width:100%!important;
  max-width:100%!important;
  display:flex!important;
  flex-direction:column!important;
  flex:1 1 auto!important;
  min-height:0!important;
}
body.prec-screen-active .pgm-shell-main .page-wrapper > .container-fluid > main.pgm-page-primary{
  flex:1 1 auto!important;
  min-height:0!important;
  display:flex!important;
  flex-direction:column!important;
}
/* content.ctp envolve a view em .row sem .col-* no .prec-root — no flex do Bootstrap o bloco não estica */
body.prec-screen-active .container-fluid > main .pgm-page-body.tirar-black-mode,
body.prec-screen-active .container-fluid > .pgm-page-body.tirar-black-mode{
  display:flex!important;
  flex-direction:column!important;
  align-items:stretch!important;
  width:100%!important;
  max-width:100%!important;
  margin-left:0!important;
  margin-right:0!important;
}
body.prec-screen-active .container-fluid > main .pgm-page-body.tirar-black-mode > .prec-root,
body.prec-screen-active .container-fluid > .pgm-page-body.tirar-black-mode > .prec-root{
  width:100%!important;
  max-width:100%!important;
  flex:1 1 auto!important;
  min-height:0!important;
}
.prec-topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;background:var(--prd-surface);border-bottom:1px solid var(--prd-border);}
.prec-topbar-left{display:flex;align-items:center;gap:12px;}
.prec-topbar h1{font-size:1.1rem;font-weight:700;color:var(--prd-teal-lt);margin:0;}
.prec-topbar .prec-badge-erp{font-size:.7rem;padding:2px 8px;background:var(--prd-teal-dim);color:var(--prd-teal-lt);border:1px solid var(--prd-teal);border-radius:99px;font-family:'DM Mono',monospace;}
.prec-breadcrumb{font-size:.75rem;color:var(--prd-muted);margin:0;}
.prec-body{display:flex;gap:0;flex:1;min-height:0;}
/* ── Painel lateral (estratégia) ───────────────────────────────── */
.prec-panel{width:300px;min-width:260px;background:var(--prd-surface);border-right:1px solid var(--prd-border);padding:20px 18px;display:flex;flex-direction:column;gap:14px;flex-shrink:0;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.22) transparent;}
.prec-panel::-webkit-scrollbar{width:8px;}
.prec-panel::-webkit-scrollbar-track{background:transparent;}
.prec-panel::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18);border-radius:6px;}
.prec-panel::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.28);}
.prec-panel-title{font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--prd-text2);margin-bottom:4px;}
/* Tabs de método */
.prec-method-tabs{display:flex;gap:4px;background:var(--prd-bg);border-radius:8px;padding:3px;}
.prec-method-tab{flex:1;padding:7px 4px;font-size:.72rem;font-weight:600;text-align:center;border-radius:6px;cursor:pointer;color:var(--prd-muted);border:none;background:transparent;transition:all .18s;}
.prec-method-tab.active{background:var(--prd-teal);color:#fff;}
/* Parâmetros */
.prec-param-block{display:flex;flex-direction:column;gap:10px;}
.prec-param-row{display:flex;flex-direction:column;gap:4px;}
.prec-param-row label{font-size:.7rem;color:var(--prd-text2);font-weight:600;}
.prec-param-input{width:100%;padding:8px 10px;background:var(--prd-bg);border:1px solid var(--prd-border);border-radius:6px;color:var(--prd-text);font-family:'DM Mono',monospace;font-size:.9rem;}
.prec-param-input:focus{outline:none;border-color:var(--prd-teal);box-shadow:0 0 0 2px var(--prd-teal-dim);}
.prec-param-hint{font-size:.66rem;color:var(--prd-text2);line-height:1.4;}
/* Formula display */
.prec-formula{background:var(--prd-bg);border:1px solid var(--prd-border);border-radius:8px;padding:10px 12px;font-family:'DM Mono',monospace;font-size:.78rem;color:var(--prd-text);line-height:1.6;}
.prec-formula .formula-label{font-size:.65rem;color:var(--prd-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px;}
.prec-formula .formula-eq{color:var(--prd-teal-lt);}
.prec-formula .formula-val{color:var(--prd-text);}
/* Custos operacionais */
.prec-costs{display:flex;flex-direction:column;gap:8px;}
.prec-cost-row{display:grid;grid-template-columns:1fr 72px;align-items:center;gap:8px;}
.prec-cost-row label{font-size:.72rem;color:var(--prd-muted);}
.prec-cost-input{padding:5px 8px;background:var(--prd-bg);border:1px solid var(--prd-border);border-radius:5px;color:var(--prd-text);font-family:'DM Mono',monospace;font-size:.78rem;text-align:right;width:100%;}
.prec-cost-input:focus{outline:none;border-color:var(--prd-teal);}
.prec-costs-total{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-top:1px solid var(--prd-border);font-size:.78rem;}
.prec-costs-total .val{font-family:'DM Mono',monospace;color:var(--prd-teal-lt);font-weight:700;}
/* Margem mínima */
.prec-margin-alert{display:flex;align-items:center;gap:6px;padding:8px 10px;border-radius:7px;font-size:.72rem;background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.3);color:#fb923c;display:none;}
.prec-margin-alert.show{display:flex;}
/* Ações do painel */
.prec-panel-actions{display:flex;flex-direction:column;gap:8px;margin-top:auto;}
.prec-btn-apply-sel{padding:9px 14px;background:var(--prd-teal);color:#fff;border:none;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer;transition:background .18s;}
.prec-btn-apply-sel:hover{background:var(--prd-teal-lt);color:#111;}
.prec-btn-apply-all{padding:9px 14px;background:transparent;color:var(--prd-teal-lt);border:1px solid var(--prd-teal);border-radius:7px;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .18s;}
.prec-btn-apply-all:hover{background:var(--prd-teal-dim);}
.prec-btn-reset{padding:7px 14px;background:transparent;color:var(--prd-muted);border:1px solid var(--prd-border);border-radius:7px;font-size:.75rem;cursor:pointer;}
/* ── Grade de produtos ─────────────────────────────────────────── */
.prec-grid-wrap{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.prec-grid-toolbar{display:flex;align-items:center;gap:10px;padding:12px 18px;background:var(--prd-surface2);border-bottom:1px solid var(--prd-border);}
.prec-search{flex:1;max-width:280px;padding:7px 12px;background:var(--prd-bg);border:1px solid var(--prd-border);border-radius:6px;color:var(--prd-text);font-size:.82rem;}
.prec-search:focus{outline:none;border-color:var(--prd-teal);}
.prec-tipo-pills{display:flex;gap:4px;}
.prec-tipo-pill{padding:5px 12px;border-radius:99px;font-size:.72rem;font-weight:600;cursor:pointer;border:1px solid var(--prd-border);background:transparent;color:var(--prd-muted);transition:all .15s;}
.prec-tipo-pill.active{background:var(--prd-teal-dim);color:var(--prd-teal-lt);border-color:var(--prd-teal);}
.prec-sel-count{font-size:.72rem;color:var(--prd-muted);margin-left:auto;}
.prec-grid-scroll{flex:1;overflow:auto;min-height:0;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.22) transparent;}
.prec-grid-scroll::-webkit-scrollbar{width:8px;height:8px;}
.prec-grid-scroll::-webkit-scrollbar-track{background:transparent;}
.prec-grid-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18);border-radius:6px;}
.prec-grid-scroll::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.28);}
.prec-grid-scroll::-webkit-scrollbar-corner{background:transparent;}
/* Tabela */
.prec-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.prec-table thead th{position:sticky;top:0;z-index:2;background:var(--prd-surface);padding:9px 10px;text-align:left;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--prd-text2);border-bottom:1px solid var(--prd-border);white-space:nowrap;}
.prec-table tbody tr{border-bottom:1px solid var(--prd-border);transition:background .12s;}
.prec-table tbody tr:hover{background:var(--prd-surface2);}
.prec-table tbody tr.selected{background:var(--prd-teal-dim);}
.prec-table td{padding:8px 10px;color:var(--prd-text);vertical-align:middle;background:transparent;}
.prec-td-mono{font-family:'DM Mono',monospace;font-size:.78rem;}
.prec-td-code{font-family:'DM Mono',monospace;font-size:.73rem;color:var(--prd-text2);}
.prec-td-desc{max-width:220px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
/* Custo — cinza quando não tem dado ERP */
.prec-td-custo{font-family:'DM Mono',monospace;font-size:.78rem;}
.prec-td-custo.no-data{color:var(--prd-text2);font-style:italic;}
/* Novo preço — editável */
.prec-novo-preco{width:100px;padding:5px 7px;background:var(--prd-bg);border:1px solid var(--prd-border);border-radius:5px;color:var(--prd-text);font-family:'DM Mono',monospace;font-size:.78rem;text-align:right;}
.prec-novo-preco:focus{outline:none;border-color:var(--prd-teal);box-shadow:0 0 0 2px var(--prd-teal-dim);}
.prec-novo-preco.changed{border-color:var(--prd-teal);background:var(--prd-teal-dim);}
/* Delta */
.prec-delta{font-family:'DM Mono',monospace;font-size:.75rem;white-space:nowrap;}
.prec-delta.up{color:#34d399;}
.prec-delta.down{color:#f87171;}
.prec-delta.same{color:var(--prd-text2);}
/* Margem */
.prec-td-margem{font-family:'DM Mono',monospace;font-size:.75rem;}
.prec-td-margem.good{color:#34d399;}
.prec-td-margem.ok{color:#fbbf24;}
.prec-td-margem.low{color:#f87171;}
.prec-td-margem.none{color:var(--prd-muted);}
/* Warning */
.prec-warn{display:inline-block;width:18px;height:18px;border-radius:50%;background:rgba(249,115,22,.2);color:#fb923c;font-size:.6rem;font-weight:900;text-align:center;line-height:18px;cursor:default;visibility:hidden;}
.prec-warn.show{visibility:visible;}
/* Zero-price row (tem custo ERP mas Preço Atual zerado) */
.prec-table tbody tr.prec-row-zero-price td{background:rgba(210,153,34,.07);}
.prec-table tbody tr.prec-row-zero-price:hover td{background:rgba(210,153,34,.13);}
.prec-badge-zero{font-size:.68rem;font-weight:700;color:#d29922;background:rgba(210,153,34,.15);border:1px solid rgba(210,153,34,.3);border-radius:5px;padding:2px 6px;white-space:nowrap;}
/* Floating selection bar */
.prec-sel-bar{position:fixed;bottom:0;left:0;right:0;z-index:200;background:var(--prd-surface);border-top:2px solid var(--prd-teal);padding:12px 24px;display:flex;align-items:center;gap:12px;transform:translateY(100%);transition:transform .2s ease;box-shadow:0 -4px 24px rgba(0,0,0,.4);}
.prec-sel-bar.visible{transform:translateY(0);}
.prec-sel-bar-count{font-size:.85rem;font-weight:700;color:var(--prd-teal-lt);}
.prec-sel-bar-label{font-size:.8rem;color:var(--prd-muted);}
.prec-sel-bar-spacer{flex:1;}
.prec-btn-sel-apply{padding:8px 18px;background:var(--prd-teal);color:#fff;border:none;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer;transition:background .15s;}
.prec-btn-sel-apply:hover{background:var(--prd-teal-lt);color:#111;}
.prec-btn-sel-clear{padding:8px 14px;background:transparent;color:var(--prd-muted);border:1px solid var(--prd-border);border-radius:7px;font-size:.78rem;cursor:pointer;transition:border-color .15s,color .15s;}
.prec-btn-sel-clear:hover{border-color:var(--prd-text2);color:var(--prd-text);}
/* Avatar */
.prec-avatar{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;}
.prec-avatar.t1{background:rgba(29,158,117,.15);color:var(--prd-teal-lt);}
.prec-avatar.t2{background:rgba(99,102,241,.15);color:#a5b4fc;}
.prec-avatar.t3{background:rgba(251,191,36,.12);color:#fde68a;}
/* Markup bar mini */
.prec-bar-mini{width:60px;height:5px;background:var(--prd-border);border-radius:3px;overflow:hidden;}
.prec-bar-fill{height:100%;background:linear-gradient(90deg,var(--prd-teal),var(--prd-teal-lt));border-radius:3px;transition:width .3s;}
/* ── Footer ────────────────────────────────────────────────────── */
.prec-footer{background:var(--prd-surface);border-top:1px solid var(--prd-border);padding:12px 24px;display:flex;align-items:center;gap:16px;}
.prec-footer-stat{display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--prd-muted);}
.prec-footer-stat strong{font-family:'DM Mono',monospace;color:var(--prd-text);}
.prec-footer-divider{width:1px;height:20px;background:var(--prd-border);}
.prec-footer-actions{display:flex;gap:10px;margin-left:auto;}
.prec-btn-save{padding:9px 22px;background:var(--prd-teal);color:#fff;border:none;border-radius:7px;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:7px;}
.prec-btn-save:hover{background:var(--prd-teal-lt);color:#111;}
.prec-btn-save:disabled{opacity:.5;cursor:not-allowed;}
.prec-btn-cancel{padding:9px 16px;background:transparent;color:var(--prd-muted);border:1px solid var(--prd-border);border-radius:7px;font-size:.78rem;cursor:pointer;}
/* Toast */
.prec-toast{position:fixed;bottom:20px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.prec-toast-item{padding:11px 18px;border-radius:9px;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:9px;box-shadow:0 4px 20px rgba(0,0,0,.4);animation:toastIn .25s ease;}
.prec-toast-item.success{background:#064e3b;color:#34d399;border:1px solid #065f46;}
.prec-toast-item.error{background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;}
@keyframes toastIn{from{transform:translateY(16px);opacity:0}to{transform:none;opacity:1}}
/* Checkbox */
.prec-check{width:15px;height:15px;accent-color:var(--prd-teal);cursor:pointer;}

/* Escudo de contraste para esta tela (evita herança de estados globais) */
body.prec-screen-active .pgm-shell-main,
body.prec-screen-active .page-wrapper,
body.prec-screen-active .container-fluid {
  opacity: 1 !important;
  filter: none !important;
}

/* Sidebar nesta rota: sem hambúrguer ao lado da marca, sem badge de data, marca centralizada */
body.prec-screen-active .pgm-sidebar-toggler,
body.prec-screen-active a.pgm-sidebar-toggler {
  display: none !important;
}
body.prec-screen-active .pgm-ws-date {
  display: none !important;
}
body.prec-screen-active .pgm-sidebar-brand {
  justify-content: center !important;
}
body.prec-screen-active .pgm-sidebar-logo-link {
  justify-content: center !important;
  flex: 0 1 auto !important;
}
body.prec-screen-active .pgm-sidebar-logo-link .pgm-sidebar-titles {
  align-items: center !important;
  text-align: center !important;
}
body.prec-screen-active .pgm-sidebar-logo-link .pgm-sidebar-titles strong {
  white-space: normal !important;
}

/* Se o preloader/backdrop ficar órfão nesta tela, não pode cobrir o conteúdo */
body.prec-screen-active .preloader,
body.prec-screen-active .modal-backdrop {
  display: none !important;
  opacity: 0 !important;
  visibility: hidden !important;
  pointer-events: none !important;
}

body.prec-screen-active .prec-overlay-killed {
  display: none !important;
  opacity: 0 !important;
  visibility: hidden !important;
  pointer-events: none !important;
}
</style>

<div class="prec-root">

  <!-- Topbar -->
  <div class="prec-topbar">
    <div class="prec-topbar-left">
      <div>
        <h1>Gestão de Preços</h1>
        <div class="prec-breadcrumb">Produtos e Serviços &rsaquo; Precificação</div>
      </div>
      <span class="prec-badge-erp">ERP Integrado</span>
    </div>
    <a href="<?= $this->Url->build(['controller' => 'Produtos', 'action' => 'index']) ?>" style="font-size:.78rem;color:var(--prd-muted);text-decoration:none;">
      &larr; Voltar para Produtos
    </a>
  </div>

  <div class="prec-body">

    <!-- ── Painel de Estratégia ─────────────────────────────────── -->
    <aside class="prec-panel">

      <div>
        <div class="prec-panel-title">Método de Cálculo</div>
        <div class="prec-method-tabs">
          <button class="prec-method-tab active" data-method="markup" onclick="setMethod('markup')">Markup</button>
          <button class="prec-method-tab" data-method="fmult" onclick="setMethod('fmult')">F. Mult.</button>
          <button class="prec-method-tab" data-method="fdiv" onclick="setMethod('fdiv')">F. Div.</button>
        </div>
      </div>

      <!-- Parâmetros ligados -->
      <div class="prec-param-block">
        <div class="prec-param-row" id="row-markup">
          <label for="inp-markup">Markup (%)</label>
          <input class="prec-param-input" id="inp-markup" name="inp_markup" type="number" min="0" step="0.01" placeholder="ex.: 40" oninput="syncParams('markup')">
          <div class="prec-param-hint">PV = Custo × (1 + Markup/100)</div>
        </div>
        <div class="prec-param-row" id="row-fmult">
          <label for="inp-fmult">Fator Multiplicador</label>
          <input class="prec-param-input" id="inp-fmult" name="inp_fmult" type="number" min="0" step="0.001" placeholder="ex.: 1.40" oninput="syncParams('fmult')">
          <div class="prec-param-hint">PV = Custo × Fator</div>
        </div>
        <div class="prec-param-row" id="row-fdiv">
          <label for="inp-fdiv">Fator Divisor</label>
          <input class="prec-param-input" id="inp-fdiv" name="inp_fdiv" type="number" min="0" max="0.9999" step="0.0001" placeholder="ex.: 0.7143" oninput="syncParams('fdiv')">
          <div class="prec-param-hint">PV = Custo ÷ FD &nbsp;·&nbsp; FD = 1 − Margem%/100</div>
        </div>
        <div class="prec-param-row" id="row-margem">
          <label for="inp-margem">Margem Líquida (%)</label>
          <input class="prec-param-input" id="inp-margem" name="inp_margem" type="number" min="0" max="99" step="0.01" placeholder="ex.: 28.57" oninput="syncParams('margem')">
          <div class="prec-param-hint">% da receita (não do custo)</div>
        </div>
      </div>

      <!-- Fórmula dinâmica -->
      <div class="prec-formula" id="prec-formula">
        <div class="formula-label">Fórmula ativa</div>
        <div class="formula-eq" id="formula-txt">PV = Custo × (1 + Markup/100)</div>
        <div style="margin-top:6px;color:var(--prd-muted);font-size:.7rem;">
          Markup: <span id="fml-markup-v">—</span> % &nbsp;·&nbsp;
          F.Mult: <span id="fml-fmult-v">—</span> &nbsp;·&nbsp;
          F.Div: <span id="fml-fdiv-v">—</span> &nbsp;·&nbsp;
          Margem: <span id="fml-margem-v">—</span> %
        </div>
      </div>

      <!-- Custos operacionais (Fator Divisor composto) -->
      <div>
        <div class="prec-panel-title">Custos Operacionais (opcional)</div>
        <div class="prec-param-hint" style="margin-bottom:8px;">Preenchendo, o Fator Divisor é calculado automaticamente: FD = 1 − Σcustos/100</div>
        <div class="prec-costs" id="prec-costs">
          <div class="prec-cost-row">
            <label for="cost-impostos">Impostos (%)</label>
            <input class="prec-cost-input" id="cost-impostos" name="cost_impostos" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-comissao">Comissão (%)</label>
            <input class="prec-cost-input" id="cost-comissao" name="cost_comissao" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-despesas">Despesas Fixas (%)</label>
            <input class="prec-cost-input" id="cost-despesas" name="cost_despesas" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-frete">Frete (%)</label>
            <input class="prec-cost-input" id="cost-frete" name="cost_frete" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-lucro">Lucro Desejado (%)</label>
            <input class="prec-cost-input" id="cost-lucro" name="cost_lucro" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-costs-total">
            <span>Σ Custos</span>
            <span class="val" id="costs-sum">0,00 %</span>
          </div>
          <div class="prec-costs-total" style="padding-top:2px;">
            <span>Fator Divisor resultante</span>
            <span class="val" id="costs-fd">1,0000</span>
          </div>
        </div>
      </div>

      <!-- Margem mínima alerta -->
      <div>
        <div class="prec-panel-title">Margem Mínima</div>
        <div class="prec-cost-row">
          <label for="margem-min">Alertar abaixo de (%)</label>
          <input class="prec-cost-input" id="margem-min" name="margem_min" type="number" min="0" max="99" step="1" value="10" oninput="refreshTable()">
        </div>
      </div>
      <div class="prec-margin-alert" id="margem-alert">
        ⚠ Alguns itens estão abaixo da margem mínima
      </div>

      <!-- Ações -->
      <div class="prec-panel-actions">
        <button class="prec-btn-apply-sel" onclick="aplicarSelecionados()">Aplicar aos Selecionados</button>
        <button class="prec-btn-apply-all" onclick="aplicarTodos()">Aplicar a Todos os Visíveis</button>
        <button class="prec-btn-reset" onclick="resetPrecos()">Resetar Alterações</button>
      </div>

    </aside><!-- /prec-panel -->

    <!-- ── Grade de Produtos ────────────────────────────────────── -->
    <div class="prec-grid-wrap">

      <div class="prec-grid-toolbar">
        <label for="prec-search" style="position:absolute;left:-9999px;">Buscar itens da precificação</label>
        <input class="prec-search" id="prec-search" name="prec_search" type="text" placeholder="Buscar código ou descrição…" oninput="refreshTable()">
        <div class="prec-tipo-pills">
          <button class="prec-tipo-pill active" data-tipo="0" onclick="setTipo(0,this)">Todos</button>
          <button class="prec-tipo-pill" data-tipo="1" onclick="setTipo(1,this)">Produtos</button>
          <button class="prec-tipo-pill" data-tipo="2" onclick="setTipo(2,this)">Serviços</button>
          <button class="prec-tipo-pill" data-tipo="3" onclick="setTipo(3,this)">Contratos</button>
        </div>
        <label style="font-size:.72rem;color:var(--prd-muted);display:flex;align-items:center;gap:5px;cursor:pointer;">
          <input type="checkbox" id="chk-only-changed" class="prec-check" onchange="refreshTable()"> Só alterados
        </label>
        <div class="prec-sel-count" id="sel-count">0 selecionados</div>
      </div>

      <div class="prec-grid-scroll">
        <table class="prec-table" id="prec-table">
          <thead>
            <tr>
              <th style="width:30px;">
                <label for="chk-all" style="position:absolute;left:-9999px;">Selecionar todos os itens</label>
                <input type="checkbox" class="prec-check" id="chk-all" name="chk_all" onchange="toggleAll(this)">
              </th>
              <th style="width:36px;"></th>
              <th>Código</th>
              <th>Descrição</th>
              <th>Tipo</th>
              <th>Custo ERP</th>
              <th>Preço Atual</th>
              <th>Markup Atual</th>
              <th>Novo Preço</th>
              <th>Nova Margem</th>
              <th>Δ Diferença</th>
              <th style="width:22px;"></th>
            </tr>
          </thead>
          <tbody id="prec-tbody"></tbody>
        </table>
      </div>

    </div><!-- /prec-grid-wrap -->

  </div><!-- /prec-body -->

  <!-- ── Footer ──────────────────────────────────────────────────── -->
  <div class="prec-footer">
    <div class="prec-footer-stat">Itens: <strong id="ft-total">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Alterados: <strong id="ft-changed">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Selecionados: <strong id="ft-selected">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Impacto estoque: <strong id="ft-impacto">R$ 0,00</strong></div>
    <div class="prec-footer-actions">
      <button class="prec-btn-cancel" onclick="resetPrecos()">Cancelar</button>
      <button class="prec-btn-save" id="btn-save" onclick="salvarPrecos()" disabled>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Salvar Preços
      </button>
    </div>
  </div>

</div><!-- /prec-root -->

<!-- Floating selection bar -->
<div class="prec-sel-bar" id="prec-sel-bar">
  <span class="prec-sel-bar-count" id="sel-bar-count">0</span>
  <span class="prec-sel-bar-label">itens selecionados</span>
  <span class="prec-sel-bar-spacer"></span>
  <button class="prec-btn-sel-clear" onclick="clearSelection()">Limpar seleção</button>
  <button class="prec-btn-sel-apply" onclick="aplicarSelecionados()">&#9654; Aplicar cálculo</button>
</div>

<!-- Toast container -->
<div class="prec-toast" id="prec-toast"></div>

<script>
/* ── Dados do servidor ─────────────────────────────────────────── */
var PREC_DATA = <?= $produtosJson ?? '[]' ?>;

/* ── Estado ────────────────────────────────────────────────────── */
var state = {
  method: 'markup',
  tipoFiltro: 0,
  params: { markup: null, fmult: null, fdiv: null, margem: null },
  novosPrecos: {},   // id → float
  selected: new Set()
};
var saveUrl = <?= json_encode($this->Url->build(['controller' => 'Produtos', 'action' => 'salvarPrecos'])) ?>;

/* ── Formatação ────────────────────────────────────────────────── */
function fmt(n, dec) {
  if (n === null || n === undefined || isNaN(n)) return '—';
  return parseFloat(n).toLocaleString('pt-BR', { minimumFractionDigits: dec ?? 2, maximumFractionDigits: dec ?? 2 });
}
function fmtBRL(n) {
  if (n === null || isNaN(n)) return '—';
  return 'R$ ' + fmt(n, 2);
}
function parseFl(s) {
  if (s === '' || s === null || s === undefined) return NaN;
  var raw = String(s).trim();
  if (!raw) return NaN;
  var hasComma = raw.indexOf(',') !== -1;
  if (hasComma) {
    raw = raw.replace(/\./g, '').replace(',', '.');
  } else {
    raw = raw.replace(',', '.');
  }
  return parseFloat(raw);
}

/* ── Sincronização dos 4 campos ────────────────────────────────── */
function syncParams(src) {
  var mk = parseFl(document.getElementById('inp-markup').value);
  var fm = parseFl(document.getElementById('inp-fmult').value);
  var fd = parseFl(document.getElementById('inp-fdiv').value);
  var mg = parseFl(document.getElementById('inp-margem').value);

  if (src === 'markup' && !isNaN(mk)) {
    fm = 1 + mk / 100;
    fd = (fm > 0) ? 1 / fm : 0;
    mg = (1 - fd) * 100;
  } else if (src === 'fmult' && !isNaN(fm) && fm > 0) {
    mk = (fm - 1) * 100;
    fd = 1 / fm;
    mg = (1 - fd) * 100;
  } else if (src === 'fdiv' && !isNaN(fd) && fd > 0 && fd < 1) {
    fm = 1 / fd;
    mk = (fm - 1) * 100;
    mg = (1 - fd) * 100;
  } else if (src === 'margem' && !isNaN(mg) && mg >= 0 && mg < 100) {
    fd = 1 - mg / 100;
    fm = (fd > 0) ? 1 / fd : 0;
    mk = (fm - 1) * 100;
  } else {
    return;
  }

  state.params = { markup: mk, fmult: fm, fdiv: fd, margem: mg };

  // Atualizar campos (sem disparar oninput)
  silentSet('inp-markup', isNaN(mk) ? '' : mk.toFixed(4));
  silentSet('inp-fmult',  isNaN(fm) ? '' : fm.toFixed(6));
  silentSet('inp-fdiv',   isNaN(fd) ? '' : fd.toFixed(6));
  silentSet('inp-margem', isNaN(mg) ? '' : mg.toFixed(4));

  // Atualizar resumo fórmula
  document.getElementById('fml-markup-v').textContent = isNaN(mk) ? '—' : fmt(mk, 2);
  document.getElementById('fml-fmult-v').textContent  = isNaN(fm) ? '—' : fmt(fm, 4);
  document.getElementById('fml-fdiv-v').textContent   = isNaN(fd) ? '—' : fmt(fd, 4);
  document.getElementById('fml-margem-v').textContent = isNaN(mg) ? '—' : fmt(mg, 2);

  refreshTable();
}

function silentSet(id, val) {
  var el = document.getElementById(id);
  if (el) el.value = val;
}

/* ── Custos operacionais → Fator Divisor composto ─────────────── */
function calcFatorDivComposto() {
  var ids = ['cost-impostos','cost-comissao','cost-despesas','cost-frete','cost-lucro'];
  var soma = 0;
  ids.forEach(function(id) {
    var v = parseFl(document.getElementById(id).value);
    if (!isNaN(v)) soma += v;
  });
  var fd = 1 - soma / 100;
  document.getElementById('costs-sum').textContent = fmt(soma, 2) + ' %';
  document.getElementById('costs-fd').textContent  = (fd > 0 && fd < 1) ? fd.toFixed(4) : '—';
  if (fd > 0 && fd < 1) {
    silentSet('inp-fdiv', fd.toFixed(6));
    syncParams('fdiv');
  }
}

/* ── Método ativo ──────────────────────────────────────────────── */
var formulaTexts = {
  markup: 'PV = Custo × (1 + Markup / 100)',
  fmult:  'PV = Custo × Fator Multiplicador',
  fdiv:   'PV = Custo ÷ Fator Divisor'
};
function setMethod(m) {
  state.method = m;
  document.querySelectorAll('.prec-method-tab').forEach(function(b) {
    b.classList.toggle('active', b.dataset.method === m);
  });
  document.getElementById('formula-txt').textContent = formulaTexts[m] || '';
}

/* ── Calcular novo preço ───────────────────────────────────────── */
function calcNewPrice(custo) {
  if (!custo || custo <= 0) return null;
  var p = state.params;
  if (state.method === 'markup') {
    if (p.markup === null) return null;
    return isNaN(p.markup) ? null : custo * (1 + p.markup / 100);
  } else if (state.method === 'fmult') {
    if (p.fmult === null) return null;
    return (isNaN(p.fmult) || p.fmult <= 0) ? null : custo * p.fmult;
  } else {
    if (p.fdiv === null) return null;
    return (isNaN(p.fdiv) || p.fdiv <= 0) ? null : custo / p.fdiv;
  }
}

/* ── Filtrar dados ─────────────────────────────────────────────── */
function getVisible() {
  var termo = (document.getElementById('prec-search').value || '').toLowerCase();
  var tipo  = state.tipoFiltro;
  var soAlterados = document.getElementById('chk-only-changed').checked;
  return PREC_DATA.filter(function(p) {
    if (tipo && p.tipo !== tipo) return false;
    if (soAlterados && !state.novosPrecos.hasOwnProperty(p.id)) return false;
    if (termo) {
      var haystack = (p.codigo + ' ' + p.descricao).toLowerCase();
      if (!haystack.includes(termo)) return false;
    }
    return true;
  });
}

/* ── Renderizar tabela ─────────────────────────────────────────── */
function avatarHtml(tipo) {
  var icons = {1: '📦', 2: '⚙️', 3: '📄'};
  var cls   = {1: 't1', 2: 't2', 3: 't3'};
  return '<div class="prec-avatar ' + (cls[tipo]||'t1') + '">' + (icons[tipo]||'?') + '</div>';
}
function tipoLabel(t) {
  return {1:'Produto',2:'Serviço',3:'Contrato'}[t] || 'Outro';
}
function margemClass(m) {
  if (m === null || isNaN(m)) return 'none';
  if (m >= 20) return 'good';
  if (m >= 10) return 'ok';
  return 'low';
}

function refreshTable() {
  var vis = getVisible();
  var tbody = document.getElementById('prec-tbody');
  var margemMin = parseFl(document.getElementById('margem-min').value) || 0;
  var hasLow = false;
  var totalChanged = Object.keys(state.novosPrecos).length;
  var totalSelected = vis.filter(function(p) { return state.selected.has(p.id); }).length;
  var impacto = 0;

  var rows = vis.map(function(p) {
    var novoPreco = state.novosPrecos.hasOwnProperty(p.id)
      ? state.novosPrecos[p.id]
      : calcNewPrice(p.custo);
    var changed  = state.novosPrecos.hasOwnProperty(p.id);
    var precoRef = p.vendaAtual;

    // Margem nova
    var novaMargem = null;
    if (novoPreco && p.custo > 0) {
      novaMargem = (1 - p.custo / novoPreco) * 100;
    }

    // Delta
    var delta = changed && novoPreco !== null ? (novoPreco - precoRef) : null;
    var deltaPct = (delta !== null && precoRef > 0) ? (delta / precoRef * 100) : null;

    // Markup atual
    var mkAtual = (p.custo > 0 && p.vendaAtual > 0) ? ((p.vendaAtual / p.custo - 1) * 100) : null;
    var barW = mkAtual !== null ? Math.min(mkAtual / 2, 100) : 0;  // escala: 200% = 100px

    // Impacto no estoque
    if (changed && novoPreco && p.qtde) {
      impacto += (novoPreco - precoRef) * p.qtde;
    }

    // Alerta margem
    if (novaMargem !== null && novaMargem < margemMin) hasLow = true;

    // Warn icon
    var showWarn = novaMargem !== null && novaMargem < margemMin;

    var sel = state.selected.has(p.id);
    var isPrecoZero = p.temCusto && (!p.vendaAtual || p.vendaAtual <= 0);
    var rowCls = (sel ? 'selected' : '') + (isPrecoZero ? (sel ? ' prec-row-zero-price' : 'prec-row-zero-price') : '');

    return '<tr class="' + rowCls + '" data-id="' + p.id + '">' +
      '<td><input type="checkbox" class="prec-check row-chk" data-id="' + p.id + '" name="row_chk_' + p.id + '" aria-label="Selecionar item ' + escHtml(p.codigo) + '" ' + (sel ? 'checked' : '') + ' onchange="toggleRow(' + p.id + ',this)"></td>' +
      '<td>' + avatarHtml(p.tipo) + '</td>' +
      '<td class="prec-td-code">' + escHtml(p.codigo) + '</td>' +
      '<td class="prec-td-desc" title="' + escHtml(p.descricao) + '">' + escHtml(p.descricao) + '</td>' +
      '<td><span style="font-size:.68rem;padding:2px 7px;border-radius:99px;background:var(--prd-surface2);color:var(--prd-muted);">' + tipoLabel(p.tipo) + '</span></td>' +
      '<td class="prec-td-custo' + (!p.temCusto ? ' no-data' : '') + '">' + (p.temCusto ? fmtBRL(p.custo) : 'Sem dado ERP') + '</td>' +
      '<td class="prec-td-mono">' + (isPrecoZero ? '<span class="prec-badge-zero">⚠ Sem preço</span>' : fmtBRL(p.vendaAtual)) + '</td>' +
      '<td>' +
        (mkAtual !== null
          ? ('<div style="display:flex;align-items:center;gap:5px;"><span class="prec-td-mono" style="font-size:.72rem;">' + fmt(mkAtual, 1) + '%</span><div class="prec-bar-mini"><div class="prec-bar-fill" style="width:' + Math.round(barW) + '%"></div></div></div>')
          : (isPrecoZero
              ? '<span style="color:#d29922;font-size:.68rem;font-weight:600;">Sem preço</span>'
              : '<span style="color:var(--prd-muted);font-size:.72rem;">Sem custo ERP</span>')) +
      '</td>' +
      '<td><input type="number" class="prec-novo-preco' + (changed ? ' changed' : '') + '" data-id="' + p.id + '" name="novo_preco_' + p.id + '" aria-label="Novo preço para item ' + escHtml(p.codigo) + '" value="' + (novoPreco !== null ? novoPreco.toFixed(2) : '') + '" step="0.01" min="0" oninput="onPrecoEdit(this)" onblur="onPrecoBlur(this)"></td>' +
      '<td class="prec-td-margem ' + margemClass(novaMargem) + '">' + (novaMargem !== null ? fmt(novaMargem, 2) + '%' : '—') + '</td>' +
      '<td class="prec-delta ' + (delta === null ? 'same' : delta > 0 ? 'up' : delta < 0 ? 'down' : 'same') + '">' +
        (delta !== null ? (delta >= 0 ? '+' : '') + fmtBRL(delta) + (deltaPct !== null ? '<br><span style="font-size:.65rem;">' + (deltaPct >= 0 ? '+' : '') + fmt(deltaPct, 1) + '%</span>' : '') : '—') +
      '</td>' +
      '<td><span class="prec-warn' + (showWarn ? ' show' : '') + '" title="Margem abaixo do mínimo">⚠</span></td>' +
    '</tr>';
  });

  tbody.innerHTML = rows.join('');

  // Atualizar footer
  document.getElementById('ft-total').textContent    = vis.length;
  document.getElementById('ft-changed').textContent  = totalChanged;
  document.getElementById('ft-selected').textContent = totalSelected;
  document.getElementById('ft-impacto').textContent  = fmtBRL(impacto);
  document.getElementById('sel-count').textContent   = totalSelected + ' selecionado' + (totalSelected !== 1 ? 's' : '');

  // Alerta margem
  var alertEl = document.getElementById('margem-alert');
  alertEl.classList.toggle('show', hasLow);

  // Floating selection bar
  var selBar = document.getElementById('prec-sel-bar');
  if (selBar) {
    selBar.classList.toggle('visible', totalSelected > 0);
    var selBarCount = document.getElementById('sel-bar-count');
    if (selBarCount) selBarCount.textContent = totalSelected;
  }

  // Btn salvar
  document.getElementById('btn-save').disabled = totalChanged === 0;

  // Checkbox all
  var chkAll = document.getElementById('chk-all');
  chkAll.indeterminate = totalSelected > 0 && totalSelected < vis.length;
  chkAll.checked = vis.length > 0 && totalSelected === vis.length;
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Edição manual de novo preço ──────────────────────────────── */
function onPrecoEdit(el) {
  var id  = parseInt(el.dataset.id);
  var val = parseFl(el.value);
  if (!isNaN(val) && val >= 0) {
    state.novosPrecos[id] = val;
    el.classList.add('changed');
  } else {
    delete state.novosPrecos[id];
    el.classList.remove('changed');
  }
  // Atualizar apenas o footer e a linha (leve)
  refreshFooterOnly();
}

function onPrecoBlur(el) {
  // Reformatar ao sair
  var val = parseFl(el.value);
  if (!isNaN(val)) el.value = val.toFixed(2);
  refreshTable();
}

function refreshFooterOnly() {
  var changed = Object.keys(state.novosPrecos).length;
  document.getElementById('ft-changed').textContent  = changed;
  document.getElementById('btn-save').disabled = changed === 0;
}

/* ── Aplicar cálculo aos selecionados / todos ─────────────────── */
function aplicarSelecionados() {
  if (state.selected.size === 0) { showToast('Selecione ao menos um item', 'error'); return; }
  if (state.params.markup === null && state.params.fmult === null && state.params.fdiv === null) {
    showToast('Preencha um parâmetro de cálculo antes de aplicar', 'error');
    return;
  }
  var aplicados = 0;
  state.selected.forEach(function(id) {
    var p = PREC_DATA.find(function(x) { return x.id === id; });
    if (!p) return;
    var novo = calcNewPrice(p.custo);
    if (novo !== null) { state.novosPrecos[id] = parseFloat(novo.toFixed(2)); aplicados++; }
  });
  refreshTable();
  showToast(aplicados + ' preço(s) calculado(s)', 'success');
}

function aplicarTodos() {
  if (state.params.markup === null && state.params.fmult === null && state.params.fdiv === null) {
    showToast('Preencha um parâmetro de cálculo antes de aplicar', 'error');
    return;
  }
  var vis = getVisible();
  var aplicados = 0;
  vis.forEach(function(p) {
    var novo = calcNewPrice(p.custo);
    if (novo !== null) { state.novosPrecos[p.id] = parseFloat(novo.toFixed(2)); aplicados++; }
  });
  refreshTable();
  showToast(aplicados + ' preço(s) calculado(s)', 'success');
}

function resetPrecos() {
  state.novosPrecos = {};
  refreshTable();
}

/* ── Seleção ───────────────────────────────────────────────────── */
function toggleRow(id, chk) {
  if (chk.checked) state.selected.add(id);
  else state.selected.delete(id);
  var tr = chk.closest('tr');
  if (tr) tr.classList.toggle('selected', chk.checked);
  refreshFooterOnly();
  document.getElementById('sel-count').textContent = state.selected.size + ' selecionado' + (state.selected.size !== 1 ? 's' : '');
  document.getElementById('ft-selected').textContent = state.selected.size;
}

function toggleAll(chk) {
  var vis = getVisible();
  vis.forEach(function(p) {
    if (chk.checked) state.selected.add(p.id);
    else state.selected.delete(p.id);
  });
  refreshTable();
}

function clearSelection() {
  state.selected.clear();
  refreshTable();
}

/* ── Filtros ───────────────────────────────────────────────────── */
function setTipo(t, btn) {
  state.tipoFiltro = t;
  document.querySelectorAll('.prec-tipo-pill').forEach(function(b) {
    b.classList.toggle('active', parseInt(b.dataset.tipo) === t);
  });
  refreshTable();
}

/* ── Salvar no servidor ────────────────────────────────────────── */
function salvarPrecos() {
  var ids = Object.keys(state.novosPrecos);
  if (!ids.length) return;

  var payload = ids.map(function(id) {
    return { id: parseInt(id), vlunitario: state.novosPrecos[id] };
  });

  var btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  fetch(saveUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]') ? document.querySelector('meta[name="csrfToken"]').content : ''
    },
    body: JSON.stringify({ precos: payload })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.salvos > 0) {
      // Atualizar vendaAtual nos dados locais
      payload.forEach(function(item) {
        var p = PREC_DATA.find(function(x) { return x.id === item.id; });
        if (p) p.vendaAtual = item.vlunitario;
      });
      state.novosPrecos = {};
      showToast(data.salvos + ' preço(s) salvo(s) com sucesso!', 'success');
      refreshTable();
    }
    if (data.erros && data.erros.length) {
      showToast(data.erros.length + ' erro(s) ao salvar', 'error');
    }
  })
  .catch(function(e) {
    showToast('Erro de comunicação: ' + e.message, 'error');
  })
  .finally(function() {
    btn.disabled = Object.keys(state.novosPrecos).length === 0;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Preços';
  });
}

/* ── Toast ─────────────────────────────────────────────────────── */
function showToast(msg, type) {
  var container = document.getElementById('prec-toast');
  var item = document.createElement('div');
  item.className = 'prec-toast-item ' + (type || 'success');
  item.textContent = msg;
  container.appendChild(item);
  setTimeout(function() { item.remove(); }, 4000);
}

function cleanupGhostBackdrop() {
  var hasOpenModal = !!document.querySelector('.modal.show, .bootbox.modal.show');
  if (hasOpenModal) return;
  document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    if (el && el.parentNode) el.parentNode.removeChild(el);
  });
  document.querySelectorAll('.preloader').forEach(function(el) {
    if (el) {
      el.style.display = 'none';
      el.style.opacity = '0';
      el.style.visibility = 'hidden';
      el.style.pointerEvents = 'none';
    }
  });
  document.body.classList.remove('modal-open');
}

function isDarkOverlayCandidate(el) {
  if (!el || el === document.body || el === document.documentElement) return false;
  if (el.classList && (el.classList.contains('modal') || el.classList.contains('bootbox') || el.classList.contains('prec-toast'))) return false;
  if (el.closest && el.closest('.modal.show, .bootbox.modal.show, .prec-root, .left-sidebar, .topbar')) return false;

  var st = window.getComputedStyle(el);
  if (st.display === 'none' || st.visibility === 'hidden') return false;
  if (st.position !== 'fixed' && st.position !== 'absolute') return false;

  var rect = el.getBoundingClientRect();
  var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
  var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
  var coversScreen = rect.width >= vw * 0.95 && rect.height >= vh * 0.95;
  if (!coversScreen) return false;

  var zi = parseInt(st.zIndex, 10);
  if (!isNaN(zi) && zi < 100) return false;

  var bg = st.backgroundColor || '';
  var isDarkBg = /rgba?\(\s*0\s*,\s*0\s*,\s*0(?:\s*,\s*(0\.[1-9]|1))?\s*\)/.test(bg);
  var hasBackdrop = st.backdropFilter && st.backdropFilter !== 'none';
  var hasBlend = st.mixBlendMode && st.mixBlendMode !== 'normal';
  return isDarkBg || hasBackdrop || hasBlend;
}

function neutralizeDarkOverlays() {
  var nodes = document.body ? document.body.querySelectorAll('*') : [];
  for (var i = 0; i < nodes.length; i++) {
    var el = nodes[i];
    if (isDarkOverlayCandidate(el)) {
      el.classList.add('prec-overlay-killed');
    }
  }
}

/* ── Altura exata: mede posição real do .prec-root no viewport (sem mexer em largura — evita deslocar o bloco para a esquerda) ── */
function fitPrecRoot() {
  var el = document.querySelector('.prec-root');
  if (!el) return;
  var top = el.getBoundingClientRect().top;
  var h = Math.max(300, window.innerHeight - top);
  el.style.height = h + 'px';
  el.style.removeProperty('margin-left');
  el.style.removeProperty('width');
}

/* ── Init ──────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  if (!document.body.classList.contains('prec-screen-active')) {
    document.body.classList.add('prec-screen-active');
  }
  cleanupGhostBackdrop();
  neutralizeDarkOverlays();
  setTimeout(cleanupGhostBackdrop, 250);
  setTimeout(neutralizeDarkOverlays, 300);
  setTimeout(cleanupGhostBackdrop, 1200);
  setTimeout(neutralizeDarkOverlays, 1300);

  // Altura real após o layout ter renderizado completamente
  fitPrecRoot();
  window.addEventListener('resize', fitPrecRoot);

  // Alguns scripts globais podem reinserir overlay após load.
  var obs = new MutationObserver(function() {
    cleanupGhostBackdrop();
    neutralizeDarkOverlays();
  });
  obs.observe(document.body, { childList: true, subtree: true });
  window.addEventListener('beforeunload', function() { obs.disconnect(); });

  // Markup padrão 30% — exibe colunas "Novo Preço / Nova Margem / Δ Diferença" imediatamente
  silentSet('inp-markup', '30');
  syncParams('markup'); // já chama refreshTable() internamente
});
</script>
