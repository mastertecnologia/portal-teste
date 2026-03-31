<?php
    use Cake\Routing\Router;
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));

    $dval = date_format(date_create($orcamento['validoate']), "d/m/Y");
    $orcamento['validoate'] = $dval;
    $nomeCliente = $orcamento->cliente->tipo == C_ClientesTipoJuridica ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome;
?>
<style>
/* Portal cliente — isolado da layout do ERP */
.orc-portal-root { font-family:-apple-system,'Segoe UI',sans-serif; font-size:14px; color:#1a1a18; background:#f5f4f0; min-height:100vh; margin:-20px -30px; }
.orc-portal-header { background:#1a1a18; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; }
.orc-portal-logo { display:flex; align-items:center; gap:10px; }
.orc-portal-logo-box { width:34px; height:34px; background:#00C08B; border-radius:8px; display:flex; align-items:center; justify-content:center; }
.orc-portal-logo-name { font-size:15px; font-weight:700; color:#fff; }
.orc-portal-logo-sub { font-size:10px; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:.6px; }
.orc-portal-header-badge { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.08); padding:8px 14px; border-radius:99px; font-size:12px; color:rgba(255,255,255,.7); }
.orc-portal-header-badge .dot { width:8px; height:8px; border-radius:50%; background:#FFC107; animation:orc-pulse 2s infinite; }
@keyframes orc-pulse { 0%,100%{opacity:1}50%{opacity:.5} }
.orc-portal-container { max-width:780px; margin:0 auto; padding:28px 16px 60px; }

/* States */
.orc-pg { display:none; }
.orc-pg.show { display:block; }

/* Hero */
.orc-hero { background:#fff; border-radius:20px; padding:28px 32px; margin-bottom:16px; border:1px solid #e8e7e3; }
.orc-hero-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
.orc-prop-num { font-size:12px; color:#6b6a65; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
.orc-prop-title { font-size:20px; font-weight:700; color:#1a1a18; }
.orc-validity-pill { display:flex; align-items:center; gap:6px; background:#FFF8E1; border:1px solid #FAC775; padding:8px 14px; border-radius:99px; font-size:12px; color:#8A6A00; font-weight:600; white-space:nowrap; }
.orc-client-block { background:#f5f4f0; border-radius:10px; padding:14px 18px; margin-bottom:20px; }
.orc-client-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6b6a65; font-weight:600; margin-bottom:6px; }
.orc-client-name { font-size:16px; font-weight:700; color:#1a1a18; }
.orc-client-doc { font-size:12px; color:#6b6a65; margin-top:2px; }
.orc-info-row { display:flex; gap:0; border:1px solid #e8e7e3; border-radius:10px; overflow:hidden; flex-wrap:wrap; }
.orc-info-cell { flex:1; min-width:120px; padding:12px 16px; border-right:1px solid #e8e7e3; }
.orc-info-cell:last-child { border-right:none; }
.orc-info-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6b6a65; font-weight:600; margin-bottom:4px; }
.orc-info-value { font-size:13px; font-weight:600; color:#1a1a18; }
.orc-progress-bar { height:4px; background:#e8e7e3; border-radius:2px; margin:20px 0 6px; overflow:hidden; }
.orc-progress-fill { height:100%; background:#00C08B; border-radius:2px; }
.orc-progress-labels { display:flex; justify-content:space-between; font-size:10px; color:#6b6a65; }

/* Cards */
.orc-portal-card { background:#fff; border-radius:14px; padding:22px 26px; margin-bottom:14px; border:1px solid #e8e7e3; }
.orc-portal-card-title { font-size:11px; font-weight:600; color:#6b6a65; text-transform:uppercase; letter-spacing:.7px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.orc-portal-card-title::after { content:''; flex:1; height:1px; background:#f0efec; }

/* Items table */
.orc-items-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.orc-items-tbl thead th { font-size:11px; color:#6b6a65; text-transform:uppercase; letter-spacing:.4px; padding:8px 12px; text-align:left; border-bottom:1px solid #e8e7e3; font-weight:600; background:#fafaf8; }
.orc-items-tbl thead th.r { text-align:right; }
.orc-items-tbl tbody tr { border-bottom:1px solid #f0efec; }
.orc-items-tbl tbody tr:last-child { border-bottom:none; }
.orc-items-tbl td { padding:12px; vertical-align:middle; }
.orc-items-tbl td.r { text-align:right; }
.orc-item-name { font-weight:600; color:#1a1a18; margin-bottom:3px; }
.orc-item-desc { font-size:12px; color:#6b6a65; line-height:1.5; }
.orc-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:10px; font-weight:600; }
.orc-b-prod { background:#E1F5EE; color:#085041; }
.orc-b-serv { background:#E6F1FB; color:#0C447C; }
.orc-totals { display:flex; justify-content:flex-end; padding-top:14px; border-top:1px solid #e8e7e3; margin-top:8px; }
.orc-totals-inner { min-width:260px; }
.orc-tot-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:#6b6a65; border-bottom:1px solid #f0efec; }
.orc-tot-row:last-child { border:none; font-size:16px; font-weight:700; color:#1a1a18; padding-top:10px; }
.orc-tot-row .g { color:#00C08B; }

/* Decision */
.orc-decision-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.orc-decision-btn { padding:16px; border-radius:14px; font-size:14px; font-weight:700; cursor:pointer; border:none; display:flex; flex-direction:column; align-items:center; gap:6px; transition:all .2s; font-family:inherit; }
.orc-decision-btn svg { width:24px; height:24px; }
.orc-btn-approve { background:#00C08B; color:#fff; }
.orc-btn-approve:hover { background:#008F68; transform:translateY(-1px); }
.orc-btn-decline { background:#FCEBEB; color:#791F1F; border:2px solid #F7C1C1; }
.orc-btn-decline:hover { background:#F7C1C1; }
.orc-btn-negotiate { width:100%; padding:14px; border-radius:14px; font-size:14px; font-weight:600; cursor:pointer; border:2px solid #FFC107; background:#FFF8E1; color:#8A6A00; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; font-family:inherit; }
.orc-btn-negotiate:hover { background:#FAC775; }
.orc-btn-main { width:100%; padding:14px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:none; background:#00C08B; color:#fff; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; font-family:inherit; margin-top:10px; }
.orc-btn-main:hover { background:#008F68; }
.orc-btn-main.red { background:#E24B4A; }
.orc-btn-main.amber { background:#FFC107; color:#1a1a18; }
.orc-btn-ghost { width:100%; padding:10px; border-radius:10px; font-size:13px; font-weight:500; cursor:pointer; border:1px solid #e5e4e0; background:transparent; color:#6b6a65; font-family:inherit; transition:all .15s; margin-top:8px; }
.orc-btn-ghost:hover { background:#f5f4f0; }

/* Conditions */
.orc-cond-text { background:#f5f4f0; border-radius:10px; padding:14px 16px; font-size:13px; color:#1a1a18; line-height:1.8; margin-bottom:12px; border-left:3px solid #00C08B; }
.orc-obs-html p { margin: 0 0 0.65em; }
.orc-obs-html p:last-child { margin-bottom: 0; }

/* Motivos recusa */
.orc-radio-opt { display:flex; align-items:center; gap:10px; padding:12px 14px; border:1.5px solid #e8e7e3; border-radius:10px; cursor:pointer; font-size:13px; margin-bottom:8px; transition:all .15px; }
.orc-radio-opt:hover { border-color:#00C08B; }

/* Negociar textarea */
.orc-textarea { width:100%; padding:12px 14px; border:1.5px solid #e8e7e3; border-radius:10px; font-size:13px; color:#1a1a18; outline:none; font-family:inherit; resize:vertical; min-height:90px; line-height:1.6; background:#fff; }
.orc-textarea:focus { border-color:#00C08B; box-shadow:0 0 0 3px rgba(0,192,139,.1); }

/* Success */
.orc-success-card { background:#fff; border-radius:20px; padding:40px 32px; text-align:center; border:1px solid #e8e7e3; }
.orc-success-icon { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
.orc-success-title { font-size:24px; font-weight:800; margin-bottom:8px; }
.orc-success-sub { font-size:14px; color:#6b6a65; line-height:1.7; max-width:420px; margin:0 auto 28px; }
.orc-contract-preview { background:#f5f4f0; border:1px solid #e8e7e3; border-radius:10px; padding:16px 20px; margin-bottom:20px; text-align:left; }
.orc-contract-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; border-bottom:1px solid #f0efec; }
.orc-contract-row:last-child { border:none; }
.orc-contract-label { color:#6b6a65; }
.orc-contract-value { font-weight:600; color:#1a1a18; }

/* Security footer */
.orc-portal-footer { text-align:center; padding:24px 0 12px; font-size:12px; color:#6b6a65; }
.orc-security-bar { background:#fff; border-top:1px solid #e8e7e3; padding:12px 24px; display:flex; align-items:center; justify-content:center; gap:16px; font-size:11px; color:#6b6a65; flex-wrap:wrap; }
.orc-security-item { display:flex; align-items:center; gap:5px; }
.orc-security-item svg { color:#00C08B; }

@media(max-width:600px){
  .orc-hero{padding:20px 18px;}
  .orc-portal-card{padding:18px 16px;}
  .orc-decision-grid{grid-template-columns:1fr;}
  .orc-info-cell{min-width:50%;border-right:none;border-bottom:1px solid #e8e7e3;}
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
  <div class="orc-portal-header-badge" style="background:rgba(0,192,139,.15);color:#5CDBC0;">
    <span class="dot" style="background:#00C08B;animation:none;"></span>
    Proposta aprovada
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?>
  <div class="orc-portal-header-badge" style="background:rgba(226,75,74,.15);color:#E24B4A;">
    <span class="dot" style="background:#E24B4A;animation:none;"></span>
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
        <div class="orc-prop-title">Proposta Comercial PGM Soluções</div>
      </div>
      <div class="orc-validity-pill">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><polyline points="8 5 8 8 10 10"/></svg>
        Válido até <?= $orcamento->validoate ?>
      </div>
    </div>

    <div class="orc-client-block">
      <div class="orc-client-label">Destinatário</div>
      <div class="orc-client-name"><?= $nomeCliente ?></div>
      <?php if(!empty($orcamento->cliente->cpfcnpj)): ?>
        <div class="orc-client-doc">CNPJ/CPF: <?= $orcamento->cliente->cpfcnpj ?></div>
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
        <div class="orc-info-label">Status</div>
        <div class="orc-info-value"><?= orcamentoStatus($orcamento->status) ?></div>
      </div>
    </div>

    <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
    <div class="orc-progress-bar"><div class="orc-progress-fill" style="width:33%;"></div></div>
    <div class="orc-progress-labels">
      <span>Proposta enviada</span>
      <span style="color:#FFC107;font-weight:600;">Aguardando sua resposta</span>
      <span>Contrato finalizado</span>
    </div>
    <?php elseif($orcamento->status == C_OrcamentoStatusAprovado): ?>
    <div class="orc-progress-bar"><div class="orc-progress-fill" style="width:100%;"></div></div>
    <div class="orc-progress-labels">
      <span>Proposta enviada</span>
      <span>Resposta recebida</span>
      <span style="color:#00C08B;font-weight:600;">Proposta aprovada ✓</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Produtos -->
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Produtos e serviços inclusos</div>
    <div style="overflow-x:auto;">
      <table class="orc-items-tbl">
        <thead>
          <tr>
            <th style="width:60px;">Código</th>
            <th>Produto / Serviço</th>
            <th style="width:80px;">Tipo</th>
            <th class="r" style="width:50px;">Qtd.</th>
            <th class="r" style="width:110px;">Vl. Unit.</th>
            <th class="r" style="width:120px;">Vl. Total</th>
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
            <td style="font-size:11px;color:#6b6a65;font-family:monospace;"><?= $reg->idproduto ?: '—' ?></td>
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
            <td class="r" style="font-weight:700;color:#00C08B;">R$ <?= number_format($reg->valordoservico, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="orc-totals">
      <div class="orc-totals-inner">
        <?php if($totalMensal > 0): ?>
          <div class="orc-tot-row"><span>Total mensal</span><span>R$ <?= number_format($totalMensal, 2, ',', '.') ?></span></div>
        <?php endif; ?>
        <div class="orc-tot-row"><span>Total geral</span><span class="g">R$ <?= number_format($totalGeral, 2, ',', '.') ?></span></div>
      </div>
    </div>
  </div>

  <!-- Condições -->
  <?php if(!empty($orcamento->solicitacao)): ?>
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Condições comerciais</div>
    <div class="orc-cond-text orc-obs-html"><?= $orcamento->solicitacao ?></div>
  </div>
  <?php endif; ?>

  <!-- Decisão -->
  <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
  <div class="orc-portal-card">
    <div class="orc-portal-card-title">Sua decisão</div>
    <div class="orc-decision-grid">
      <a href="<?= $this->Url->build(['action' => 'aprovarhash', $orcamento->hash]) ?>" class="orc-decision-btn orc-btn-approve" id="btn-aprovar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Aprovar proposta
        <span style="font-size:11px;font-weight:400;opacity:.85;">Aceito as condições acima</span>
      </a>
      <button type="button" class="orc-decision-btn orc-btn-decline" onclick="orcGoTo('orc-pg-recusar')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Recusar proposta
        <span style="font-size:11px;font-weight:400;opacity:.7;">Não tenho interesse</span>
      </button>
    </div>
    <button type="button" class="orc-btn-negotiate" onclick="orcGoTo('orc-pg-negociar')">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Quero negociar / solicitar ajustes
    </button>
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusAprovado): ?>
  <div class="orc-portal-card" style="text-align:center;">
    <div style="width:52px;height:52px;background:#E6FAF4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#008F68" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div style="font-size:16px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Proposta já aprovada</div>
    <div style="font-size:13px;color:#6b6a65;">Esta proposta foi aprovada. A PGM Soluções irá entrar em contato em breve.</div>
  </div>
  <?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?>
  <div class="orc-portal-card" style="text-align:center;">
    <div style="width:52px;height:52px;background:#FCEBEB;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
    <div style="font-size:16px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Proposta recusada</div>
    <div style="font-size:13px;color:#6b6a65;">Se mudar de ideia, entre em contato com nossa equipe.</div>
  </div>
  <?php endif; ?>

</div><!-- /orc-pg-proposta -->

<!-- ===== TELA: RECUSAR ===== -->
<div class="orc-pg" id="orc-pg-recusar">
  <div class="orc-portal-card">
    <div style="text-align:center;margin-bottom:24px;">
      <div style="width:52px;height:52px;background:#FCEBEB;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <div style="font-size:18px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Recusar proposta</div>
      <div style="font-size:13px;color:#6b6a65;">Lamentamos que não tenha dado certo. Poderia nos informar o motivo?</div>
    </div>
    <div style="margin-bottom:16px;">
      <div style="font-size:13px;font-weight:700;color:#1a1a18;margin-bottom:10px;">Motivo da recusa</div>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="preco" style="accent-color:#00C08B;"/> Preço acima do orçamento disponível</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="prazo"/> Prazo de entrega não atende</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="spec"/> Especificações técnicas não atendem</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="outro"/> Escolhemos outro fornecedor</label>
      <label class="orc-radio-opt"><input type="radio" name="motivo" value="cancelado"/> Projeto cancelado internamente</label>
    </div>
    <div style="margin-bottom:20px;">
      <div style="font-size:13px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Observação adicional (opcional)</div>
      <textarea class="orc-textarea" id="rec-obs" placeholder="Deixe um comentário para nossa equipe..."></textarea>
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
    <div style="text-align:center;margin-bottom:24px;">
      <div style="width:52px;height:52px;background:#FFF8E1;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#8A6A00" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      </div>
      <div style="font-size:18px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Solicitar ajustes</div>
      <div style="font-size:13px;color:#6b6a65;">Informe o que precisa ser ajustado. Nossa equipe retornará em até 24h.</div>
    </div>
    <div style="margin-bottom:14px;">
      <div style="font-size:13px;font-weight:700;color:#1a1a18;margin-bottom:6px;">Descreva os ajustes necessários</div>
      <textarea class="orc-textarea" id="neg-obs" placeholder="Ex: Preciso de desconto adicional, prazo máximo de entrega de 15 dias..."></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
      <div>
        <div style="font-size:11px;font-weight:600;color:#6b6a65;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Seu nome</div>
        <input type="text" id="neg-nome" placeholder="Nome completo" style="width:100%;padding:10px 14px;border:1.5px solid #e8e7e3;border-radius:10px;font-size:13px;outline:none;font-family:inherit;" onfocus="this.style.borderColor='#00C08B'" onblur="this.style.borderColor='#e8e7e3'"/>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:#6b6a65;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Telefone</div>
        <input type="tel" id="neg-tel" placeholder="(54) 99999-9999" style="width:100%;padding:10px 14px;border:1.5px solid #e8e7e3;border-radius:10px;font-size:13px;outline:none;font-family:inherit;" onfocus="this.style.borderColor='#00C08B'" onblur="this.style.borderColor='#e8e7e3'"/>
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
    <div class="orc-success-icon" style="background:#FCEBEB;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#791F1F" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
    <div class="orc-success-title" style="color:#1a1a18;">Recusa registrada</div>
    <div class="orc-success-sub">Agradecemos o retorno. Se mudar de ideia, entre em contato com nossa equipe.</div>
  </div>
</div>

<!-- ===== SUCESSO NEGOCIAÇÃO ===== -->
<div class="orc-pg" id="orc-pg-negociado">
  <div class="orc-success-card">
    <div class="orc-success-icon" style="background:#FFF8E1;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#8A6A00" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="orc-success-title" style="color:#1a1a18;">Solicitação enviada!</div>
    <div class="orc-success-sub">Sua solicitação de ajuste foi registrada. Nossa equipe entrará em contato em até 24h com uma nova proposta.</div>
  </div>
</div>

<div class="orc-portal-footer">
  <p>© <?= date('Y') ?> PGM Soluções em TI Ltda</p>
</div>

</div><!-- /orc-portal-container -->

<div class="orc-security-bar">
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
    SSL 256-bit
  </div>
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>
    Proposta autêntica PGM
  </div>
  <div class="orc-security-item">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><line x1="8" y1="5" x2="8" y2="8"/></svg>
    Link com validade
  </div>
</div>

</div><!-- /orc-portal-root -->

<script>
function orcGoTo(id) {
  document.querySelectorAll('.orc-pg').forEach(el => el.classList.remove('show'));
  document.getElementById(id).classList.add('show');
  window.scrollTo(0, 0);
}

function orcConfirmarRecusa() {
  var motivo = document.querySelector('input[name="motivo"]:checked');
  if (!motivo) { alert('Selecione o motivo da recusa.'); return; }
  // Pode-se enviar via AJAX para um endpoint futuro
  orcGoTo('orc-pg-recusado');
}

function orcConfirmarNeg() {
  var obs = document.getElementById('neg-obs').value;
  if (!obs.trim()) { alert('Descreva os ajustes necessários.'); return; }
  // Pode-se enviar via AJAX para um endpoint futuro
  orcGoTo('orc-pg-negociado');
}

// Confirmar antes de aprovar
document.getElementById('btn-aprovar-link') && document.getElementById('btn-aprovar-link').addEventListener('click', function(e){
  if(!confirm('Confirma a aprovação da proposta Nº <?= $orcamento->id ?>?')) e.preventDefault();
});
</script>
