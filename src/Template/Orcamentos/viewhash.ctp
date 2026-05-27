<?php
    use Cake\Routing\Router;
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));

    $dval = pgm_format_date_br($orcamento['validoate'] ?? null);
    $orcamento['validoate'] = $dval;
    $nomeCliente = $orcamento->cliente->tipo == C_ClientesTipoJuridica ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome;
	$contatoFone = '';
	if (!empty($orcamento->cliente->fone)) {
		$contatoFone = (string)$orcamento->cliente->fone;
	} elseif (!empty($orcamento->cliente->fone2)) {
		$contatoFone = (string)$orcamento->cliente->fone2;
	} else {
		$contatoFone = '—';
	}
	$portalSubtotal = 0.0;
	$portalMensal = 0.0;
	if (isset($carrinho)) {
		foreach ($carrinho as $_reg) {
			$portalSubtotal += (float)($_reg->valordoservico ?? 0);
			$portalMensal += (float)($_reg->valormensal ?? 0);
		}
	}
	$portalDescPct = 5;
	$portalDescVal = round($portalSubtotal * ($portalDescPct / 100), 2);
	$portalTotalVista = max(0, $portalSubtotal - $portalDescVal);
	$portalFmt = function ($v) {
		return 'R$ ' . number_format((float)$v, 2, ',', '.');
	};
?>
<style>
/* Portal cliente — tema escuro (hash público) */
.orc-portal-root { font-family:-apple-system,'Segoe UI',sans-serif; font-size:14px; color:#e8eaed; background:#12151c; min-height:100vh; margin:-20px -30px; }
.orc-portal-header { background:#0d1117; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #3d4554; }
.orc-portal-logo { display:flex; align-items:center; gap:10px; }
.orc-portal-logo-box { width:34px; height:34px; background:#1D9E75; border-radius:8px; display:flex; align-items:center; justify-content:center; }
.orc-portal-logo-name { font-size:15px; font-weight:700; color:#fff; }
.orc-portal-logo-sub { font-size:10px; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:.6px; }
.orc-portal-header-badge { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.08); padding:8px 14px; border-radius:99px; font-size:12px; color:rgba(255,255,255,.7); }
.orc-portal-header-badge .dot { width:8px; height:8px; border-radius:50%; background:#E9A025; animation:orc-pulse 2s infinite; }
@keyframes orc-pulse { 0%,100%{opacity:1}50%{opacity:.5} }
.orc-portal-container { max-width:780px; margin:0 auto; padding:28px 16px 60px; }

/* States */
.orc-pg { display:none; }
.orc-pg.show { display:block; }

/* Hero */
.orc-hero { background:#1e2329; border-radius:20px; padding:28px 32px; margin-bottom:16px; border:1px solid #3d4554; box-shadow:0 4px 24px rgba(0,0,0,.35); }
.orc-hero-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
.orc-prop-num { font-size:12px; color:#9aa0a8; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
.orc-prop-title { font-size:22px; font-weight:700; color:#e8eaed; }
.orc-validity-pill { display:flex; align-items:center; gap:6px; background:rgba(245,158,11,.15); border:1px solid rgba(245,158,11,.45); padding:8px 14px; border-radius:99px; font-size:12px; color:#fcd34d; font-weight:600; white-space:nowrap; }
.orc-client-block { background:#161b22; border-radius:10px; padding:14px 18px; margin-bottom:20px; border:1px solid #3d4554; }
.orc-client-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#9aa0a8; font-weight:600; margin-bottom:6px; }
.orc-client-name { font-size:16px; font-weight:700; color:#e8eaed; }
.orc-client-doc { font-size:12px; color:#9aa0a8; margin-top:2px; }
.orc-info-row { display:flex; gap:0; border:1px solid #3d4554; border-radius:10px; overflow:hidden; flex-wrap:wrap; }
.orc-info-cell { flex:1; min-width:120px; padding:12px 16px; border-right:1px solid #3d4554; }
.orc-info-cell:last-child { border-right:none; }
.orc-info-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#9aa0a8; font-weight:600; margin-bottom:4px; }
.orc-info-value { font-size:13px; font-weight:600; color:#e8eaed; }
.orc-progress-bar { height:4px; background:#3d4554; border-radius:2px; margin:20px 0 6px; overflow:hidden; }
.orc-progress-fill { height:100%; background:#1D9E75; border-radius:2px; }
.orc-progress-labels { display:flex; justify-content:space-between; font-size:10px; color:#9aa0a8; }

/* Cards */
.orc-portal-card { background:#1e2329; border-radius:14px; padding:22px 26px; margin-bottom:14px; border:1px solid #3d4554; box-shadow:0 4px 20px rgba(0,0,0,.28); }
.orc-portal-card-title { font-size:13px; font-weight:700; color:#e8eaed; text-transform:uppercase; letter-spacing:.5px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.orc-portal-card-title::after { content:''; flex:1; height:1px; background:#3d4554; }

/* Items table */
.orc-items-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.orc-items-tbl thead th { font-size:11px; color:#9aa0a8; text-transform:uppercase; letter-spacing:.4px; padding:8px 12px; text-align:left; border-bottom:1px solid #3d4554; font-weight:600; background:#262c35; }
.orc-items-tbl thead th.r { text-align:right; }
.orc-items-tbl tbody tr { border-bottom:1px solid #3d4554; }
.orc-items-tbl tbody tr:last-child { border-bottom:none; }
.orc-items-tbl td { padding:12px; vertical-align:middle; }
.orc-items-tbl td.r { text-align:right; }
.orc-item-name { font-weight:600; color:#e8eaed; margin-bottom:3px; }
.orc-item-desc { font-size:12px; color:#9aa0a8; line-height:1.5; }
.orc-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:10px; font-weight:600; }
.orc-b-prod { background:rgba(29,158,117,.2); color:#5cecc4; }
.orc-b-serv { background:rgba(56,189,248,.15); color:#7dd3fc; }
.orc-totals { display:flex; justify-content:flex-end; padding-top:14px; border-top:1px solid #3d4554; margin-top:8px; }
.orc-totals-inner { min-width:260px; }
.orc-tot-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:#9aa0a8; border-bottom:1px solid #3d4554; }
.orc-tot-row:last-child { border:none; font-size:17px; font-weight:700; color:#e8eaed; padding-top:10px; }
.orc-tot-row .g { color:#5cecc4; }
.orc-tot-row .rd { color:#f87171; }

/* Decision */
.orc-decision-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.orc-decision-btn { padding:16px; border-radius:14px; font-size:14px; font-weight:700; cursor:pointer; border:none; display:flex; flex-direction:column; align-items:center; gap:6px; transition:all .2s; font-family:inherit; }
.orc-decision-btn svg { width:24px; height:24px; }
.orc-btn-approve { background:#1D9E75; color:#fff; }
.orc-btn-approve:hover { background:#0F6E56; transform:translateY(-1px); }
.orc-btn-decline { background:rgba(248,113,113,.12); color:#fca5a5; border:2px solid rgba(248,113,113,.35); }
.orc-btn-decline:hover { background:rgba(248,113,113,.22); }
.orc-btn-negotiate { width:100%; padding:14px; border-radius:14px; font-size:14px; font-weight:600; cursor:pointer; border:2px solid rgba(245,158,11,.5); background:rgba(245,158,11,.12); color:#fcd34d; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; font-family:inherit; }
.orc-btn-negotiate:hover { background:rgba(245,158,11,.22); }
.orc-btn-main { width:100%; padding:14px; border-radius:14px; font-size:14px; font-weight:700; cursor:pointer; border:none; background:#1D9E75; color:#fff; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; font-family:inherit; margin-top:12px; }
.orc-btn-main:hover { background:#0F6E56; }
.orc-btn-main.red { background:#dc2626; }
.orc-btn-main.amber { background:#d97706; color:#fff; }
.orc-btn-ghost { width:100%; padding:10px; border-radius:10px; font-size:13px; font-weight:500; cursor:pointer; border:1px solid #3d4554; background:transparent; color:#9aa0a8; font-family:inherit; transition:all .15s; margin-top:8px; }
.orc-btn-ghost:hover { background:#262c35; }

/* Conditions */
.orc-cond-text { background:#161b22; border-radius:10px; padding:14px 16px; font-size:13px; color:#c4c9d1; line-height:1.8; margin-bottom:12px; border:1px solid #3d4554; border-left:3px solid #1D9E75; }
.orc-obs-html p { margin: 0 0 0.65em; }
.orc-obs-html p:last-child { margin-bottom: 0; }

/* Motivos recusa */
.orc-radio-opt { display:flex; align-items:center; gap:10px; padding:12px 14px; border:1.5px solid #3d4554; border-radius:10px; cursor:pointer; font-size:13px; margin-bottom:8px; transition:all .15px; color:#e8eaed; }
.orc-radio-opt:hover { border-color:#1D9E75; }

/* Negociar textarea */
.orc-textarea { width:100%; padding:12px 14px; border:1.5px solid #3d4554; border-radius:10px; font-size:13px; color:#e8eaed; outline:none; font-family:inherit; resize:vertical; min-height:90px; line-height:1.6; background:#161b22; }
.orc-textarea:focus { border-color:#1D9E75; box-shadow:0 0 0 3px rgba(29,158,117,.2); }

/* Success */
.orc-success-card { background:#1e2329; border-radius:20px; padding:40px 32px; text-align:center; border:1px solid #3d4554; box-shadow:0 4px 24px rgba(0,0,0,.35); }
.orc-success-icon { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
.orc-success-title { font-size:26px; font-weight:800; margin-bottom:8px; color:#e8eaed; }
.orc-success-sub { font-size:14px; color:#9aa0a8; line-height:1.7; max-width:420px; margin:0 auto 28px; }
.orc-contract-preview { background:#161b22; border:1px solid #3d4554; border-radius:10px; padding:16px 20px; margin-bottom:20px; text-align:left; }
.orc-contract-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; border-bottom:1px solid #3d4554; }
.orc-contract-row:last-child { border:none; }
.orc-contract-label { color:#9aa0a8; }
.orc-contract-value { font-weight:600; color:#e8eaed; }

/* Security footer */
.orc-portal-footer { text-align:center; padding:24px 0 12px; font-size:12px; color:#9aa0a8; }
.orc-security-bar { background:#1e2329; border-top:1px solid #3d4554; padding:12px 24px; display:flex; align-items:center; justify-content:center; gap:16px; font-size:11px; color:#9aa0a8; flex-wrap:wrap; }
.orc-security-item { display:flex; align-items:center; gap:5px; }
.orc-security-item svg { color:#1D9E75; }

.orc-portal-header-badge--ok { background:rgba(29,158,117,.15); color:#5cecc4; }
.orc-portal-header-badge--ok .dot { background:#1D9E75; animation:none; }
.orc-portal-header-badge--no { background:rgba(248,113,113,.15); color:#f87171; }
.orc-portal-header-badge--no .dot { background:#f87171; animation:none; }
.orc-progress-fill--w33 { width:33%; }
.orc-progress-fill--w100 { width:100%; }
.orc-plab-amber { color:#fcd34d; font-weight:600; }
.orc-plab-teal { color:#1D9E75; font-weight:600; }
.orc-items-scroll { overflow-x:auto; }
.orc-items-tbl th.orc-th-w60 { width:60px; }
.orc-items-tbl th.orc-th-w80 { width:80px; }
.orc-items-tbl th.orc-th-w46 { width:46px; }
.orc-b-lic { background:rgba(167,139,250,.2); color:#c4b5fd; }
.orc-payment-opts { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.orc-payment-opt { border:2px solid #3d4554; border-radius:14px; padding:16px 20px; cursor:pointer; transition:all .2s; position:relative; background:#161b22; }
.orc-payment-opt:hover { border-color:#1D9E75; background:rgba(29,158,117,.12); }
.orc-payment-opt.selected { border-color:#1D9E75; background:rgba(29,158,117,.15); }
.orc-payment-check { position:absolute; top:12px; right:12px; width:20px; height:20px; border-radius:50%; border:2px solid #3d4554; background:#1e2329; display:flex; align-items:center; justify-content:center; }
.orc-payment-opt.selected .orc-payment-check { background:#1D9E75; border-color:#1D9E75; }
.orc-payment-opt-label { font-size:13px; font-weight:700; color:#e8eaed; margin-bottom:4px; }
.orc-payment-opt-val { font-size:20px; font-weight:800; color:#5cecc4; margin-bottom:4px; }
.orc-payment-opt-desc { font-size:12px; color:#9aa0a8; }
.orc-payment-opt-tag { display:inline-flex; padding:2px 8px; border-radius:99px; font-size:10px; font-weight:700; background:#1D9E75; color:#fff; margin-bottom:6px; }
.orc-cond-bullets { display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:12px; color:#9aa0a8; }
.orc-cond-li { display:flex; gap:6px; align-items:flex-start; }
.orc-cond-dot { width:5px; height:5px; border-radius:50%; background:#1D9E75; margin-top:5px; flex-shrink:0; }
.orc-q-block { background:#161b22; border:1px solid #3d4554; border-radius:10px; padding:14px 16px; margin-bottom:10px; }
.orc-q-text { font-size:13px; font-weight:600; color:#e8eaed; margin-bottom:10px; }
.orc-q-opts { display:flex; gap:8px; flex-wrap:wrap; }
.orc-q-opt { padding:7px 16px; border-radius:99px; font-size:12px; font-weight:500; border:1.5px solid #3d4554; cursor:pointer; background:#1e2329; color:#9aa0a8; transition:all .15s; }
.orc-q-opt:hover { border-color:#1D9E75; color:#5cecc4; }
.orc-q-opt.selected { background:#1D9E75; border-color:#1D9E75; color:#fff; }
.orc-q-opt.selected-red { background:rgba(248,113,113,.15); border-color:#f87171; color:#fca5a5; }
.orc-q-opt.selected-amber { background:rgba(245,158,11,.15); border-color:#f59e0b; color:#fcd34d; }
.orc-sign-canvas { border:1.5px solid #3d4554; border-radius:10px; width:100%; height:110px; cursor:crosshair; background:#161b22; touch-action:none; display:block; }
.orc-doc-badge { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:99px; font-size:12px; font-weight:700; border:1px solid; }
.orc-doc-valid { background:rgba(29,158,117,.2); color:#5cecc4; border-color:rgba(29,158,117,.45); }
.orc-doc-icp { background:rgba(167,139,250,.2); color:#c4b5fd; border-color:rgba(167,139,250,.4); }
.orc-items-tbl th.orc-th-w110 { width:110px; }
.orc-items-tbl th.orc-th-w120 { width:120px; }
.orc-item-code { font-size:11px; color:#9aa0a8; font-family:monospace; }
.orc-item-vtot { font-weight:700; color:#5cecc4; }
.orc-dec-sub { font-size:11px; font-weight:400; }
.orc-dec-sub--a { opacity:.85; }
.orc-dec-sub--d { opacity:.7; }
.orc-portal-card--txtcenter { text-align:center; }
.orc-state-ico { width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
.orc-state-ico--ok { background:rgba(29,158,117,.2); }
.orc-state-ico--no { background:rgba(248,113,113,.15); }
.orc-state-ico--amb { background:rgba(245,158,11,.15); }
.orc-state-h { font-size:16px; font-weight:700; color:#e8eaed; margin-bottom:6px; }
.orc-state-h--lg { font-size:18px; }
.orc-state-p { font-size:13px; color:#9aa0a8; }
.orc-stack-center { text-align:center; margin-bottom:24px; }
.orc-stack-mb16 { margin-bottom:16px; }
.orc-stack-mb20 { margin-bottom:20px; }
.orc-stack-mb14 { margin-bottom:14px; }
.orc-fld-h { font-size:13px; font-weight:700; color:#e8eaed; margin-bottom:10px; }
.orc-fld-h--tight { margin-bottom:6px; }
.orc-radio-opt input { accent-color:#1D9E75; }
.orc-neg-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
.orc-neg-lbl { font-size:11px; font-weight:600; color:#9aa0a8; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
.orc-neg-inp { width:100%; padding:10px 14px; border:1.5px solid #3d4554; border-radius:10px; font-size:13px; outline:none; font-family:inherit; background:#161b22; color:#e8eaed; }
.orc-neg-inp:focus { border-color:#1D9E75; box-shadow:0 0 0 3px rgba(29,158,117,.2); }
.orc-success-icon--no { background:rgba(248,113,113,.15); }
.orc-success-icon--amb { background:rgba(245,158,11,.15); }

@media(max-width:600px){
  .orc-hero{padding:20px 18px;}
  .orc-portal-card{padding:18px 16px;}
  .orc-decision-grid{grid-template-columns:1fr;}
  .orc-info-cell{min-width:50%;border-right:none;border-bottom:1px solid #3d4554;}
}
</style>

<div class="orc-portal-root">

<!-- HEADER -->
<div class="orc-portal-header">
  <div class="orc-portal-logo">
    <div class="orc-portal-logo-box">
      <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="6" height="6" rx="1"/><rect x="11" y="3" width="6" height="6" rx="1"/><rect x="3" y="11" width="6" height="6" rx="1"/><rect x="11" y="11" width="6" height="6" rx="1"/></svg>
    </div>
    <div>
      <div class="orc-portal-logo-name">PGM Soluções</div>
      <div class="orc-portal-logo-sub">Portal de Propostas</div>
    </div>
  </div>
  <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
  <div class="orc-portal-header-badge">
    <span class="dot"></span>
    Proposta aguardando sua resposta
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusAprovado): ?>
  <div class="orc-portal-header-badge orc-portal-header-badge--ok">
    <span class="dot"></span>
    Proposta aprovada
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?>
  <div class="orc-portal-header-badge orc-portal-header-badge--no">
    <span class="dot"></span>
    Proposta recusada
  </div>
  <?php endif; ?>
</div>

<div class="orc-portal-container">

<!-- ===== TELA: PROPOSTA ===== -->
<div class="orc-pg show" id="orc-pg-proposta">

  <!-- Hero card -->
  <div class="orc-hero">
    <div class="orc-hero-top">
      <div>
        <div class="orc-prop-num">Proposta Nº <?= $orcamento->id ?><?= !empty($orcamento->versao) ? ' · v' . $orcamento->versao : '' ?></div>
        <div class="orc-prop-title">Proposta de Orçamento</div>
      </div>
      <div class="orc-validity-pill">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><polyline points="8 5 8 8 10 10"/></svg>
        Válido até <?= $orcamento->validoate ?>
      </div>
    </div>

    <div class="orc-client-block">
      <div class="orc-client-label">Destinatário</div>
      <div class="orc-client-name"><?= $nomeCliente ?></div>
      <?php
        $cliDocLblHash = ($orcamento->cliente->tipo == C_ClientesTipoJuridica) ? 'CNPJ' : 'CPF';
        $cliDocRawHash = ($orcamento->cliente->tipo == C_ClientesTipoJuridica)
          ? ($orcamento->cliente->cnpj ?? '')
          : ($orcamento->cliente->cpf ?? '');
        $cliDocFmtHash = function_exists('formatCnpjCpf') ? formatCnpjCpf($cliDocRawHash) : (string)$cliDocRawHash;
      ?>
      <?php if ($cliDocFmtHash !== ''): ?>
        <div class="orc-client-doc"><?= h($cliDocLblHash) ?>: <?= h($cliDocFmtHash) ?></div>
      <?php endif; ?>
    </div>

    <div class="orc-info-row">
      <div class="orc-info-cell">
        <div class="orc-info-label">Emitido por</div>
        <div class="orc-info-value"><?= ($orcamento->user && !empty($orcamento->user->name)) ? $orcamento->user->name : 'PGM Soluções' ?></div>
      </div>
      <div class="orc-info-cell">
        <div class="orc-info-label">Emissão</div>
        <div class="orc-info-value"><?= $orcamento->created->format('d/m/Y') ?></div>
      </div>
      <div class="orc-info-cell">
        <div class="orc-info-label">Pagamento</div>
        <div class="orc-info-value"><?= !empty($orcamento->formapagamento) ? $orcamento->formapagamento : '—' ?></div>
      </div>
      <div class="orc-info-cell">
        <div class="orc-info-label">Contato</div>
        <div class="orc-info-value"><?= h($contatoFone) ?></div>
      </div>
    </div>

    <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
    <div class="orc-progress-bar"><div class="orc-progress-fill orc-progress-fill--w33"></div></div>
    <div class="orc-progress-labels">
      <span>Proposta enviada</span>
      <span class="orc-plab-amber">Aguardando sua resposta</span>
      <span>Contrato finalizado</span>
    </div>
    <?php elseif($orcamento->status == C_OrcamentoStatusAprovado): ?>
    <div class="orc-progress-bar"><div class="orc-progress-fill orc-progress-fill--w100"></div></div>
    <div class="orc-progress-labels">
      <span>Proposta enviada</span>
      <span>Resposta recebida</span>
      <span class="orc-plab-teal">Proposta aprovada ✓</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Produtos -->
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Produtos e serviços inclusos</div>
    <div class="orc-items-scroll">
      <table class="orc-items-tbl">
        <thead>
          <tr>
            <th class="orc-th-w60">Código</th>
            <th>Produto / Serviço</th>
            <th class="orc-th-w80">Tipo</th>
            <th class="r orc-th-w46">Qtd.</th>
            <th class="r orc-th-w110">Vl. Unit.</th>
            <th class="r orc-th-w120">Vl. Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $totalGeral = 0;
          $totalMensal = 0;
          if(isset($carrinho)) foreach($carrinho as $reg):
            $vUnit = $reg->valoruni > 0 ? $reg->valoruni : ($reg->valormensal > 0 ? $reg->valormensal / max(1, $reg->quantidade) : 0);
            $totalGeral += $reg->valordoservico;
            $totalMensal += $reg->valormensal;
          ?>
          <tr>
            <td class="orc-item-code"><?= $reg->idproduto ?: '—' ?></td>
            <td>
              <div class="orc-item-name"><?= htmlspecialchars($reg->servico) ?></div>
              <?php if(!empty($reg->observacao)): ?><div class="orc-item-desc"><?= htmlspecialchars($reg->observacao) ?></div><?php endif; ?>
            </td>
            <td>
              <?php if($reg->valormensal > 0): ?>
                <span class="orc-badge orc-b-serv">Mensal</span>
              <?php else: ?>
                <span class="orc-badge orc-b-prod">Único</span>
              <?php endif; ?>
            </td>
            <td class="r"><?= $reg->quantidade ?></td>
            <td class="r">R$ <?= number_format($vUnit, 2, ',', '.') ?></td>
            <td class="r orc-item-vtot">R$ <?= number_format($reg->valordoservico, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="orc-totals">
      <div class="orc-totals-inner">
        <?php if ($totalMensal > 0): ?>
          <div class="orc-tot-row"><span>Total mensal</span><span>R$ <?= number_format($totalMensal, 2, ',', '.') ?></span></div>
        <?php endif; ?>
        <div class="orc-tot-row"><span>Subtotal</span><span><?= h($portalFmt($portalSubtotal)) ?></span></div>
        <?php if ($portalSubtotal > 0): ?>
        <div class="orc-tot-row"><span>Desconto à vista (<?= (int)$portalDescPct ?>%)</span><span class="rd">— <?= h($portalFmt($portalDescVal)) ?></span></div>
        <div class="orc-tot-row"><span style="color:#1D9E75;">Total à vista</span><span class="g"><?= h($portalFmt($portalTotalVista)) ?></span></div>
        <?php endif; ?>
        <div class="orc-tot-row"><span>Total a prazo</span><span><?= h($portalFmt($portalSubtotal)) ?></span></div>
      </div>
    </div>
  </div>

  <?php if ($orcamento->status == C_OrcamentoStatusEnviado): ?>
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Escolha a condição de pagamento</div>
    <div class="orc-payment-opts">
      <div class="orc-payment-opt" id="orc-opt-prazo" role="button" tabindex="0" onclick="orcSelectPayment('prazo')">
        <div class="orc-payment-check"></div>
        <div class="orc-payment-opt-label">Pagamento a prazo</div>
        <div class="orc-payment-opt-val"><?= h($portalFmt($portalSubtotal)) ?></div>
        <div class="orc-payment-opt-desc">Condição conforme informada pelo vendedor no orçamento.</div>
      </div>
      <div class="orc-payment-opt selected" id="orc-opt-vista" role="button" tabindex="0" onclick="orcSelectPayment('vista')">
        <div class="orc-payment-check"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="#fff" stroke-width="2.5" aria-hidden="true"><polyline points="13 2 6 12 2 8"/></svg></div>
        <div class="orc-payment-opt-tag">5% OFF</div>
        <div class="orc-payment-opt-label">Pagamento à vista</div>
        <div class="orc-payment-opt-val"><?= h($portalFmt($portalTotalVista)) ?></div>
        <div class="orc-payment-opt-desc">Boleto único · Vencimento conforme acordo<br>Economia de <?= h($portalFmt($portalDescVal)) ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Condições comerciais</div>
    <?php if (!empty($orcamento->solicitacao)): ?>
    <div class="orc-cond-text orc-obs-html"><?= $orcamento->solicitacao ?></div>
    <?php else: ?>
    <div class="orc-cond-text">Apresentamos nossa proposta comercial para a venda dos equipamentos solicitados, com condições especiais de pagamento. Permanecemos à disposição para quaisquer esclarecimentos adicionais.</div>
    <?php endif; ?>
    <div class="orc-cond-bullets">
      <div class="orc-cond-li"><span class="orc-cond-dot"></span>Proposta válida por 7 dias da emissão</div>
      <div class="orc-cond-li"><span class="orc-cond-dot"></span>Garantia de 12 meses contra defeitos</div>
      <div class="orc-cond-li"><span class="orc-cond-dot"></span>Suporte técnico 90 dias após implantação</div>
      <div class="orc-cond-li"><span class="orc-cond-dot"></span>NF emitida após confirmação do pedido</div>
    </div>
  </div>

  <?php if ($orcamento->status == C_OrcamentoStatusEnviado): ?>
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Antes de responder</div>
    <div class="orc-q-block">
      <div class="orc-q-text">As especificações técnicas atendem sua necessidade?</div>
      <div class="orc-q-opts">
        <div class="orc-q-opt" id="orc-q1-sim" onclick="orcSelectQ(1,'sim')">Sim, atende</div>
        <div class="orc-q-opt" id="orc-q1-par" onclick="orcSelectQ(1,'par')">Parcialmente</div>
        <div class="orc-q-opt" id="orc-q1-nao" onclick="orcSelectQ(1,'nao')">Preciso de ajustes</div>
      </div>
    </div>
    <div class="orc-q-block">
      <div class="orc-q-text">O prazo de entrega é adequado para você?</div>
      <div class="orc-q-opts">
        <div class="orc-q-opt" id="orc-q2-sim" onclick="orcSelectQ(2,'sim')">Sim</div>
        <div class="orc-q-opt" id="orc-q2-nao" onclick="orcSelectQ(2,'nao')">Preciso de urgência</div>
        <div class="orc-q-opt" id="orc-q2-flex" onclick="orcSelectQ(2,'flex')">Tenho flexibilidade</div>
      </div>
    </div>
    <div class="orc-q-block">
      <div class="orc-q-text">Como avalia o valor apresentado?</div>
      <div class="orc-q-opts">
        <div class="orc-q-opt" id="orc-q3-ok" onclick="orcSelectQ(3,'ok')">Adequado</div>
        <div class="orc-q-opt" id="orc-q3-alto" onclick="orcSelectQ(3,'alto')">Acima do esperado</div>
        <div class="orc-q-opt" id="orc-q3-neg" onclick="orcSelectQ(3,'neg')">Quero negociar</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Decisão -->
  <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Sua decisão</div>
    <div class="orc-decision-grid">
      <button type="button" class="orc-decision-btn orc-btn-approve" id="btn-aprovar-open" onclick="orcIrAprovar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Aprovar proposta
        <span class="orc-dec-sub orc-dec-sub--a">Aceito as condições acima</span>
      </button>
      <button type="button" class="orc-decision-btn orc-btn-decline" onclick="orcGoTo('orc-pg-recusar')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Recusar proposta
        <span class="orc-dec-sub orc-dec-sub--d">Não tenho interesse</span>
      </button>
    </div>
    <button type="button" class="orc-btn-negotiate" onclick="orcGoTo('orc-pg-negociar')">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Quero negociar / solicitar ajustes
    </button>
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusAprovado): ?>
  <div class="orc-portal-card orc-portal-card--txtcenter">
    <div class="orc-state-ico orc-state-ico--ok">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#0F6E56" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="orc-state-h">Proposta já aprovada</div>
    <div class="orc-state-p">Esta proposta foi aprovada. A PGM Soluções irá entrar em contato em breve.</div>
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?>
  <div class="orc-portal-card orc-portal-card--txtcenter">
    <div class="orc-state-ico orc-state-ico--no">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
    <div class="orc-state-h">Proposta recusada</div>
    <div class="orc-state-p">Se mudar de ideia, entre em contato com nossa equipe.</div>
  </div>
  <?php endif; ?>

</div><!-- /orc-pg-proposta -->

<!-- ===== TELA: APROVAR / ASSINAR ===== -->
<div class="orc-pg" id="orc-pg-aprovar">
  <div class="orc-portal-card">
    <div class="orc-stack-center">
      <div class="orc-state-ico orc-state-ico--ok">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#0F6E56" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="orc-state-h orc-state-h--lg">Assinar e aprovar proposta</div>
      <div class="orc-state-p">Ao assinar, você confirma a aceitação das condições desta proposta comercial com validade jurídica.</div>
    </div>
    <div style="background:#161b22;border:1px solid #3d4554;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
      <div style="font-size:12px;font-weight:700;color:#e8eaed;margin-bottom:10px;text-transform:uppercase;letter-spacing:.4px;">Resumo do que está sendo aprovado</div>
      <div class="orc-contract-row"><span class="orc-contract-label">Proposta</span><span class="orc-contract-value">Nº <?= (int)$orcamento->id ?> — PGM Soluções</span></div>
      <div class="orc-contract-row"><span class="orc-contract-label">Cliente</span><span class="orc-contract-value"><?= h($nomeCliente) ?></span></div>
      <div class="orc-contract-row"><span class="orc-contract-label">Pagamento escolhido</span><span class="orc-contract-value" id="orc-resumo-pag">À vista — <?= h($portalFmt($portalTotalVista)) ?></span></div>
      <div class="orc-contract-row"><span class="orc-contract-label">Validade</span><span class="orc-contract-value" style="color:#fcd34d;"><?= h($orcamento->validoate) ?></span></div>
    </div>
    <?= $this->Form->create(null, ['url' => ['action' => 'aprovarhash', $orcamento->hash], 'id' => 'form-orc-assinar']) ?>
    <div class="orc-stack-mb20">
      <div class="orc-fld-h orc-fld-h--tight">Nome completo do signatário</div>
      <input type="text" name="sign_nome" id="orc-sign-name" class="orc-neg-inp" placeholder="Nome completo conforme documento..." autocomplete="name" />
    </div>
    <div class="orc-stack-mb20">
      <div class="orc-fld-h orc-fld-h--tight">CPF do signatário</div>
      <input type="text" name="sign_cpf" id="orc-sign-cpf" class="orc-neg-inp" placeholder="000.000.000-00" maxlength="14" autocomplete="off" oninput="orcMaskCPF(this)" />
    </div>
    <div class="orc-stack-mb20">
      <div class="orc-fld-h orc-fld-h--tight">Assinatura digital</div>
      <div style="font-size:12px;color:#9aa0a8;margin-bottom:8px;">Assine com o mouse ou dedo (tela touch) no espaço abaixo:</div>
      <canvas id="orc-client-canvas" class="orc-sign-canvas" width="700" height="110"></canvas>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
        <div style="font-size:12px;color:#9aa0a8;">← Assine aqui dentro →</div>
        <button type="button" onclick="orcClearClientCanvas()" style="background:none;border:none;color:#9aa0a8;font-size:12px;cursor:pointer;text-decoration:underline;">Limpar</button>
      </div>
      <?= $this->Form->hidden('sign_pad', ['id' => 'orc-sign-pad', 'value' => '']) ?>
    </div>
    <div style="background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.35);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#7dd3fc" stroke-width="1.5" style="flex-shrink:0;margin-top:1px;"><circle cx="8" cy="8" r="6"/><line x1="8" y1="5" x2="8" y2="8"/><circle cx="8" cy="11" r=".5" fill="#7dd3fc"/></svg>
      <div style="font-size:12px;color:#c4c9d1;line-height:1.6;">Sua assinatura tem validade jurídica conforme a <strong>MP 2.200-2/2001</strong> e é processada sob os padrões <strong>ICP-Brasil</strong>. O documento assinado será enviado para seu e-mail.</div>
    </div>
    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;">
      <input type="checkbox" id="orc-check-termos" name="aceite_termos" value="1" style="width:16px;height:16px;margin-top:2px;cursor:pointer;accent-color:#1D9E75;"/>
      <label for="orc-check-termos" style="font-size:12px;color:#9aa0a8;line-height:1.6;cursor:pointer;">Li e aceito as condições desta proposta comercial, incluindo prazo de entrega, garantia e condições de pagamento selecionadas.</label>
    </div>
    <button type="button" class="orc-btn-main" id="orc-btn-assinar" onclick="orcConfirmarAssinatura()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Assinar e confirmar aprovação
    </button>
    <?= $this->Form->end() ?>
    <button type="button" class="orc-btn-ghost" onclick="orcGoTo('orc-pg-proposta')">← Voltar para a proposta</button>
  </div>
</div>

<!-- ===== TELA: RECUSAR ===== -->
<div class="orc-pg" id="orc-pg-recusar">
  <div class="orc-portal-card">
    <div class="orc-stack-center">
      <div class="orc-state-ico orc-state-ico--no">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <div class="orc-state-h orc-state-h--lg">Recusar proposta</div>
      <div class="orc-state-p">Lamentamos que não tenha dado certo. Poderia nos informar o motivo?</div>
    </div>
    <div class="orc-stack-mb16">
      <div class="orc-fld-h">Motivo da recusa</div>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="preco" /> Preço acima do orçamento disponível</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="prazo"/> Prazo de entrega não atende</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="spec"/> Especificações técnicas não atendem</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="outro"/> Escolhemos outro fornecedor</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="cancelado"/> Projeto cancelado internamente</label>
    </div>
    <div class="orc-stack-mb20">
      <div class="orc-fld-h orc-fld-h--tight">Observação adicional (opcional)</div>
      <textarea class="orc-textarea" id="rec-obs" placeholder="Deixe um comentário para o vendedor..."></textarea>
    </div>
    <button type="button" class="orc-btn-main red" onclick="orcConfirmarRecusa()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      Confirmar recusa
    </button>
    <button class="orc-btn-ghost" onclick="orcGoTo('orc-pg-proposta')">← Voltar — ainda posso reconsiderar</button>
  </div>
</div>

<!-- ===== TELA: NEGOCIAR ===== -->
<div class="orc-pg" id="orc-pg-negociar">
  <div class="orc-portal-card">
    <div class="orc-stack-center">
      <div class="orc-state-ico orc-state-ico--amb">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#633806" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      </div>
      <div class="orc-state-h orc-state-h--lg">Solicitar ajustes</div>
      <div class="orc-state-p">Informe o que precisa ser ajustado. Nossa equipe retornará em até 24h com uma nova proposta.</div>
    </div>
    <div class="orc-stack-mb14">
      <div class="orc-fld-h orc-fld-h--tight">Descreva os ajustes necessários</div>
      <textarea class="orc-textarea" id="neg-obs" placeholder="Ex: Precisamos de no mínimo 16GB de RAM e o prazo máximo é 15 dias..."></textarea>
    </div>
    <div class="orc-neg-2col">
      <div>
        <div class="orc-neg-lbl">Seu nome</div>
        <input type="text" id="neg-nome" class="orc-neg-inp" placeholder="Nome completo" />
      </div>
      <div>
        <div class="orc-neg-lbl">Telefone</div>
        <input type="tel" id="neg-tel" class="orc-neg-inp" placeholder="(54) 99999-9999" />
      </div>
    </div>
    <button type="button" class="orc-btn-main amber" onclick="orcConfirmarNeg()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Enviar solicitação de ajuste
    </button>
    <button class="orc-btn-ghost" onclick="orcGoTo('orc-pg-proposta')">← Voltar para a proposta</button>
  </div>
</div>

<!-- ===== SUCESSO RECUSA ===== -->
<div class="orc-pg" id="orc-pg-recusado">
  <div class="orc-success-card">
    <div class="orc-success-icon orc-success-icon--no">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
    <div class="orc-success-title">Recusa registrada</div>
    <div class="orc-success-sub">Agradecemos o retorno. Sua resposta foi enviada à equipe da PGM Soluções. Esperamos poder atendê-lo em uma próxima oportunidade.</div>
    <div style="background:#161b22;border:1px solid #3d4554;border-radius:10px;padding:14px 18px;max-width:440px;margin:0 auto;font-size:13px;color:#9aa0a8;">Se mudar de ideia ou precisar de algo, entre em contato:<br><strong style="color:#e8eaed;">contato@pgm.inf.br</strong></div>
  </div>
</div>

<!-- ===== SUCESSO NEGOCIAÇÃO ===== -->
<div class="orc-pg" id="orc-pg-negociado">
  <div class="orc-success-card">
    <div class="orc-success-icon orc-success-icon--amb">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#633806" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
    </div>
    <div class="orc-success-title" style="color:#fcd34d;">Solicitação enviada!</div>
    <div class="orc-success-sub">Recebemos sua solicitação de ajuste. Nossa equipe comercial analisará e retornará com uma nova proposta em até <strong>24 horas úteis</strong>.</div>
    <div style="background:rgba(245,158,11,.12);border-radius:10px;padding:14px 18px;max-width:440px;margin:0 auto;font-size:13px;color:#fcd34d;line-height:1.7;border:1px solid rgba(245,158,11,.4);">
      <strong>O que acontece agora:</strong><br>
      1. Vendedor recebe notificação imediata<br>
      2. Proposta atualizada enviada por e-mail em até 24h<br>
      3. Novo link de aprovação será gerado
    </div>
  </div>
</div>

<div class="orc-portal-footer">
  <strong>PGM Soluções em TI Ltda</strong> · CNPJ: 00.000.000/0001-00<br>
  Bento Gonçalves, RS · <a href="#" style="color:#5cecc4;text-decoration:none;">contato@pgm.inf.br</a> · (54) 0000-0000<br>
  <span style="font-size:11px;opacity:.7;">Este portal é exclusivo para aprovação da proposta Nº <?= (int)$orcamento->id ?>. Link válido até <?= h($orcamento->validoate) ?>.</span>
</div>

</div><!-- /orc-portal-container -->

<div class="orc-security-bar">
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
    Conexão segura SSL
  </div>
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>
    Assinatura ICP-Brasil
  </div>
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><polyline points="8 5 8 8 10 10"/></svg>
    Link exclusivo e intransferível
  </div>
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="13 2 6 12 2 8"/></svg>
    Válido até <?= h($orcamento->validoate) ?>
  </div>
</div>

</div><!-- /orc-portal-root -->

<script>
var orcSelectedPayment = 'vista';
var orcPainting = false, orcCtx, orcLastX, orcLastY, orcHasSignature = false;
var orcFmtVista = <?= json_encode($portalFmt($portalTotalVista)) ?>;
var orcFmtPrazo = <?= json_encode($portalFmt($portalSubtotal)) ?>;

function orcGoTo(id) {
  document.querySelectorAll('.orc-pg').forEach(function (el) { el.classList.remove('show'); });
  var el = document.getElementById(id);
  if (el) el.classList.add('show');
  window.scrollTo(0, 0);
}

function orcSelectPayment(opt) {
  orcSelectedPayment = opt;
  var v = document.getElementById('orc-opt-vista');
  var p = document.getElementById('orc-opt-prazo');
  if (v) v.classList.toggle('selected', opt === 'vista');
  if (p) p.classList.toggle('selected', opt === 'prazo');
  document.querySelectorAll('.orc-payment-opt .orc-payment-check').forEach(function (c, i) {
    var sel = (i === 0 && opt === 'prazo') || (i === 1 && opt === 'vista');
    c.innerHTML = sel ? '<svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="13 2 6 12 2 8"/></svg>' : '';
  });
  var rp = document.getElementById('orc-resumo-pag');
  if (rp) {
    rp.textContent = opt === 'vista' ? ('À vista — ' + orcFmtVista) : ('A prazo — ' + orcFmtPrazo);
  }
}

function orcSelectQ(q, val) {
  var prefix = 'orc-q' + q + '-';
  document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (el) {
    el.className = 'orc-q-opt';
  });
  var el = document.getElementById('orc-q' + q + '-' + val);
  if (!el) return;
  if (val === 'nao' || val === 'alto') el.className = 'orc-q-opt selected-red';
  else if (val === 'par' || val === 'neg') el.className = 'orc-q-opt selected-amber';
  else el.className = 'orc-q-opt selected';
}

function orcIrAprovar() {
  orcGoTo('orc-pg-aprovar');
  setTimeout(orcInitClientCanvas, 150);
}

function orcMaskCPF(el) {
  var v = el.value.replace(/\D/g, '');
  if (v.length > 11) v = v.slice(0, 11);
  if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
  else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
  else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
  el.value = v;
}

function orcInitClientCanvas() {
  var c = document.getElementById('orc-client-canvas');
  if (!c || c._orcInited) return;
  c._orcInited = true;
  var dpr = window.devicePixelRatio || 1;
  var w = c.offsetWidth || 600;
  c.width = w * dpr;
  c.height = 110 * dpr;
  orcCtx = c.getContext('2d');
  orcCtx.scale(dpr, dpr);
  orcCtx.strokeStyle = '#e8eaed';
  orcCtx.lineWidth = 2.2;
  orcCtx.lineCap = 'round';
  orcCtx.lineJoin = 'round';

  function pos(e) {
    var r = c.getBoundingClientRect();
    if (e.touches) return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
    return { x: e.offsetX, y: e.offsetY };
  }
  c.addEventListener('mousedown', function (e) {
    orcPainting = true;
    var p = pos(e);
    orcLastX = p.x;
    orcLastY = p.y;
  });
  c.addEventListener('mousemove', function (e) {
    if (!orcPainting || !orcCtx) return;
    var p = pos(e);
    orcCtx.beginPath();
    orcCtx.moveTo(orcLastX, orcLastY);
    orcCtx.lineTo(p.x, p.y);
    orcCtx.stroke();
    orcLastX = p.x;
    orcLastY = p.y;
    orcHasSignature = true;
  });
  c.addEventListener('mouseup', function () { orcPainting = false; });
  c.addEventListener('mouseleave', function () { orcPainting = false; });
  c.addEventListener('touchstart', function (e) { e.preventDefault(); orcPainting = true; var p = pos(e); orcLastX = p.x; orcLastY = p.y; }, { passive: false });
  c.addEventListener('touchmove', function (e) {
    e.preventDefault();
    if (!orcPainting || !orcCtx) return;
    var p = pos(e);
    orcCtx.beginPath();
    orcCtx.moveTo(orcLastX, orcLastY);
    orcCtx.lineTo(p.x, p.y);
    orcCtx.stroke();
    orcLastX = p.x;
    orcLastY = p.y;
    orcHasSignature = true;
  }, { passive: false });
  c.addEventListener('touchend', function () { orcPainting = false; });
}

function orcClearClientCanvas() {
  var c = document.getElementById('orc-client-canvas');
  if (!orcCtx || !c) return;
  var w = c.offsetWidth || 600;
  orcCtx.clearRect(0, 0, w + 50, 130);
  orcHasSignature = false;
}

function orcConfirmarAssinatura() {
  var nome = document.getElementById('orc-sign-name');
  var cpf = document.getElementById('orc-sign-cpf');
  var termos = document.getElementById('orc-check-termos');
  if (!nome || !nome.value.trim()) { alert('Por favor, informe seu nome completo.'); return; }
  if (!cpf || cpf.value.replace(/\D/g, '').length < 11) { alert('Por favor, informe um CPF válido.'); return; }
  if (!termos || !termos.checked) { alert('Por favor, aceite os termos da proposta.'); return; }
  if (!orcHasSignature) { alert('Por favor, assine no campo de assinatura acima.'); return; }
  var pad = document.getElementById('orc-sign-pad');
  var cv = document.getElementById('orc-client-canvas');
  if (pad && cv) {
    try { pad.value = cv.toDataURL('image/png'); } catch (e) { pad.value = ''; }
  }
  var f = document.getElementById('form-orc-assinar');
  if (f) f.submit();
}

function orcConfirmarRecusa() {
  var motivo = document.querySelector('input[name="motivo"]:checked');
  if (!motivo) { alert('Selecione o motivo da recusa.'); return; }
  orcGoTo('orc-pg-recusado');
}

function orcConfirmarNeg() {
  var obs = document.getElementById('neg-obs').value;
  if (!obs.trim()) { alert('Descreva os ajustes necessários.'); return; }
  orcGoTo('orc-pg-negociado');
}

orcSelectPayment('vista');
</script>
