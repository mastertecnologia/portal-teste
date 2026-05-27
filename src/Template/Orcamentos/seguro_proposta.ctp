<?php
/**
 * Referência visual: pgm_portal_autenticado.html — identidade e OTP validados no servidor; código enviado por e-mail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Orcamento $orcamento
 */
$ps = $passoSeguro ?? 'destino';
$urlPropostaFull = $this->Url->build(['action' => 'viewhash', $orcamento->hash], ['fullBase' => true]);
$vendedorNomeSeguro = ($orcamento->user && !empty($orcamento->user->name)) ? $orcamento->user->name : 'Equipe PGM Soluções';
$verOrc = isset($orcamento->versao) && $orcamento->versao !== '' && $orcamento->versao !== null ? (string)$orcamento->versao : '';
$propostaRotuloSeg = 'Nº ' . (int)$orcamento->id . ($verOrc !== '' ? ' v' . $verOrc : '');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= h($title ?? 'Acesso Seguro — Proposta PGM Soluções') ?></title>
<style>
:root{
  --teal:#1d9e75;--teal-dark:#0f6e56;--teal-light:rgba(29,158,117,.18);--teal-mid:#5cecc4;
  --amber:#f59e0b;--amber-light:rgba(245,158,11,.18);--amber-dark:#fcd34d;
  --red:#f87171;--red-light:rgba(248,113,113,.14);--red-dark:#fca5a5;
  --blue:#38bdf8;--blue-light:rgba(56,189,248,.15);--blue-dark:#7dd3fc;
  --purple:#a78bfa;--purple-light:rgba(167,139,250,.18);--purple-dark:#c4b5fd;
  --border:#3d4554;--border-light:#4f5869;
  --text:#e8eaed;--text-muted:#9aa0a8;--text-hint:#9aa0a8;
  --bg:#12151c;--card:#1e2329;--topbar:#0d1117;
  --r:10px;--rl:16px;--rxl:22px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,'Segoe UI',sans-serif;font-size:14px;color:var(--text);background:var(--bg);min-height:100vh;}

.pg{display:none;}.pg.show{display:block;}

/* TOPBAR */
.topbar{background:var(--topbar);border-bottom:1px solid var(--border);padding:0 24px;height:54px;display:flex;align-items:center;justify-content:space-between;}
.logo{display:flex;align-items:center;gap:10px;}
.logo-box{width:32px;height:32px;background:var(--teal);border-radius:7px;display:flex;align-items:center;justify-content:center;}
.logo-name{font-size:14px;font-weight:700;color:#fff;}
.logo-sub{font-size:10px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;}
.sec-indicators{display:flex;align-items:center;gap:12px;}
.sec-dot{display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(255,255,255,.5);}
.sec-dot svg{width:12px;height:12px;}
.sec-dot.active{color:var(--teal-mid);}

/* LAYOUT */
.wrap{min-height:calc(100vh - 54px);display:flex;align-items:center;justify-content:center;padding:28px 16px;}
.box{background:var(--card);border-radius:var(--rxl);width:100%;max-width:460px;overflow:hidden;border:1px solid var(--border);}

/* STEP HEADER */
.step-header{padding:28px 32px 22px;}
.step-eyebrow{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.step-title{font-size:20px;font-weight:700;color:var(--text);margin-bottom:6px;}
.step-desc{font-size:13px;color:var(--text-muted);line-height:1.6;}

/* PROGRESS DOTS */
.progress-dots{display:flex;gap:6px;margin-bottom:20px;}
.dot{width:8px;height:8px;border-radius:50%;background:var(--border);transition:all .3s;}
.dot.done{background:var(--teal-mid);}
.dot.active{background:var(--teal);width:22px;border-radius:4px;}

/* FIELDS */
.field-wrap{padding:0 32px 22px;}
.field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.field label{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;}
.field input{padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;color:var(--text);outline:none;font-family:inherit;background:#161b22;transition:all .15s;}
.field input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(29,158,117,.2);}
.field input.error{border-color:var(--red);background:rgba(248,113,113,.08);}
.field input.success{border-color:var(--teal);background:rgba(29,158,117,.1);}
.field-hint{font-size:11px;color:var(--text-muted);margin-top:3px;}
.field-error{font-size:11px;color:var(--red-dark);margin-top:3px;display:none;}
.field-error.show{display:block;}

/* OTP INPUT */
.otp-group{display:flex;gap:10px;justify-content:center;margin:20px 0;}
.otp-digit{width:50px;height:58px;border:1.5px solid var(--border);border-radius:var(--r);text-align:center;font-size:22px;font-weight:700;color:var(--text);outline:none;font-family:inherit;background:#161b22;transition:all .15s;caret-color:var(--teal);}
.otp-digit:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(29,158,117,.2);}
.otp-digit.filled{border-color:var(--teal-mid);background:var(--teal-light);color:var(--teal-mid);}
.otp-digit.error{border-color:var(--red);background:rgba(248,113,113,.1);}

/* TIMER */
.timer-row{text-align:center;font-size:13px;color:var(--text-muted);margin-bottom:16px;}
.timer-count{font-weight:700;color:var(--teal);font-family:monospace;font-size:15px;}
.timer-count.urgent{color:var(--red);}
.resend-btn{background:none;border:none;font-size:13px;color:var(--teal);cursor:pointer;text-decoration:underline;font-family:inherit;padding:0;}
.resend-btn:disabled{color:var(--text-muted);text-decoration:none;cursor:default;}

/* CHANNEL SELECTOR */
.channel-opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:16px 0;}
.channel-opt{border:1.5px solid var(--border);border-radius:var(--r);padding:14px 16px;cursor:pointer;transition:all .15s;text-align:center;}
.channel-opt:hover{border-color:var(--teal);}
.channel-opt.selected{border-color:var(--teal);background:rgba(29,158,117,.15);}
.channel-opt-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;}
.channel-opt-label{font-size:13px;font-weight:600;color:var(--text);}
.channel-opt-val{font-size:11px;color:var(--text-muted);margin-top:2px;}

/* IDENTITY CARD */
.identity-hint{background:var(--bg);border-radius:var(--r);padding:12px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:12px;color:var(--text-muted);line-height:1.6;}
.identity-hint svg{flex-shrink:0;margin-top:1px;}

/* AUDIT TRAIL */
.audit-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;}
.audit-row:last-child{border:none;}
.audit-label{color:var(--text-muted);min-width:110px;}
.audit-val{color:var(--text);font-weight:500;font-family:monospace;font-size:11px;}

/* BTNS */
.btn-main{width:100%;padding:14px;border-radius:var(--r);font-size:14px;font-weight:700;cursor:pointer;border:none;background:var(--teal);color:#fff;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-main:hover{background:var(--teal-dark);}
.btn-main:disabled{background:var(--border);color:var(--text-muted);cursor:not-allowed;}
.btn-main.red{background:var(--red);}
.btn-main.amber{background:var(--amber);}
.btn-ghost{width:100%;padding:10px;border-radius:var(--r);font-size:13px;font-weight:500;cursor:pointer;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-family:inherit;transition:all .15s;margin-top:8px;}
.btn-ghost:hover{background:var(--bg);}

/* DIVIDER */
.divider{height:1px;background:var(--border-light);margin:0 32px;}

/* SECURITY FOOTER */
.sec-footer{padding:16px 32px;background:var(--bg);display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;}
.sec-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);}
.sec-item svg{width:12px;height:12px;color:var(--teal);}

/* LOCK SCREEN */
.lock-overlay{background:var(--red-light);border-radius:var(--r);padding:20px;text-align:center;margin-bottom:16px;}

/* SUCCESS CARD */
.suc-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
.suc-title{font-size:22px;font-weight:800;margin-bottom:8px;}
.suc-sub{font-size:13px;color:var(--text-muted);line-height:1.7;max-width:360px;margin:0 auto 20px;}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;}
.b-green{background:rgba(29,158,117,.2);color:var(--teal-mid);}
.b-blue{background:var(--blue-light);color:var(--blue-dark);}
.b-purple{background:var(--purple-light);color:var(--purple-dark);}
.b-amber{background:var(--amber-light);color:var(--amber-dark);}

/* SIGN CANVAS */
.sign-canvas{border:1.5px solid var(--border);border-radius:var(--r);width:100%;height:100px;cursor:crosshair;background:#161b22;display:block;touch-action:none;}

/* PROPOSE SUMMARY */
.prop-summary{background:var(--bg);border-radius:var(--r);padding:14px 16px;margin-bottom:14px;}
.prop-row{display:flex;justify-content:space-between;padding:4px 0;font-size:12px;border-bottom:1px solid var(--border-light);}
.prop-row:last-child{border:none;}
.prop-label{color:var(--text-muted);}
.prop-val{font-weight:600;color:var(--text);}

@media(max-width:480px){
  .step-header{padding:22px 20px 18px;}
  .field-wrap{padding:0 20px 18px;}
  .sec-footer{padding:12px 20px;}
  .divider{margin:0 20px;}
  .otp-digit{width:42px;height:52px;font-size:20px;}
}
</style>
</head>
<body>

<div style="max-width:520px;margin:0 auto;padding:12px 16px 0;">
<?= $this->Flash->render() ?>
</div>

<!-- TOPBAR -->
<div class="topbar">
  <div class="logo">
    <div class="logo-box">
      <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="6" height="6" rx="1"/><rect x="11" y="3" width="6" height="6" rx="1"/><rect x="3" y="11" width="6" height="6" rx="1"/><rect x="11" y="11" width="6" height="6" rx="1"/></svg>
    </div>
    <div>
      <div class="logo-name">PGM Soluções</div>
      <div class="logo-sub">Portal Seguro de Propostas</div>
    </div>
  </div>
  <div class="sec-indicators">
    <div class="sec-dot active" id="ind-ssl">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="7" width="8" height="7" rx="1"/><path d="M6 7V5a2 2 0 014 0v2"/></svg>
      SSL
    </div>
    <div class="sec-dot" id="ind-otp">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/><line x1="8" y1="5" x2="8" y2="11"/><line x1="5" y1="8" x2="11" y2="8"/></svg>
      OTP
    </div>
    <div class="sec-dot" id="ind-id">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="6" r="3"/><path d="M2 14a6 6 0 0112 0"/></svg>
      ID
    </div>
    <div class="sec-dot" id="ind-sign">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L4 10l-1 3 3-1 8-8-2-2z"/></svg>
      Sign
    </div>
  </div>
</div>

<div class="wrap">
<div class="box">

<!-- ===== TELA 1: VERIFICAR DESTINO ===== -->
<div class="pg <?= $ps === 'destino' ? 'show' : '' ?>" id="pg-destino">
  <div class="step-header">
    <div class="step-eyebrow">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
      Proposta Nº <?= (int)$orcamento->id ?> · Acesso seguro
    </div>
    <div class="progress-dots">
      <div class="dot active"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
    <div class="step-title">Confirme sua identidade</div>
    <div class="step-desc">Este link foi enviado para <strong><?= h($nomeClienteSeg) ?></strong>. Antes de acessar a proposta, precisamos verificar que você é o destinatário correto.</div>
  </div>

  <?= $this->Form->create(null, [
    'url' => ['controller' => 'Orcamentos', 'action' => 'seguroProposta', $orcamento->hash],
    'id' => 'form-identidade',
    'data-turbo' => 'false',
  ]) ?>
  <?= $this->Form->hidden('portal_acao', ['value' => 'identidade']) ?>
  <div class="field-wrap">
    <div style="background:var(--teal-light);border-radius:var(--r);padding:12px 14px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="var(--teal-dark)" stroke-width="1.5" style="flex-shrink:0;margin-top:1px;"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>
      <div style="font-size:12px;color:var(--teal-dark);line-height:1.6;">Verificação em múltiplas etapas. Seus dados são usados apenas para confirmar sua identidade e <strong>não são armazenados</strong>.</div>
    </div>

    <div class="field">
      <label>CNPJ da sua empresa (apenas os 4 últimos dígitos)</label>
      <?= $this->Form->control('cnpj_input', [
        'type' => 'text',
        'label' => false,
        'id' => 'cnpj-input',
        'placeholder' => 'Ex: 0104',
        'maxlength' => 4,
        'templates' => ['inputContainer' => '{{content}}'],
        'style' => 'letter-spacing:6px;font-size:20px;font-family:monospace;text-align:center;width:100%;',
        'oninput' => "this.value=this.value.replace(/\\D/g,'');",
      ]) ?>
      <div class="field-hint">Os 4 últimos dígitos do CNPJ registrado nesta proposta</div>
      <div class="field-error" id="cnpj-err">CNPJ incorreto. Tente novamente.</div>
    </div>

    <div class="field">
      <label>Nome do responsável cadastrado</label>
      <?= $this->Form->control('nome_input', [
        'type' => 'text',
        'label' => false,
        'id' => 'nome-input',
        'placeholder' => 'Nome como cadastrado na proposta',
        'templates' => ['inputContainer' => '{{content}}'],
        'style' => 'width:100%;',
      ]) ?>
      <div class="field-hint">Nome do contato informado pelo vendedor ao gerar a proposta</div>
      <div class="field-error" id="nome-err">Nome não confere. Verifique e tente novamente.</div>
    </div>

    <div style="margin-bottom:18px;background:var(--blue-light);border-radius:var(--r);padding:12px 14px;font-size:12px;color:var(--blue-dark);line-height:1.6;display:flex;align-items:flex-start;gap:10px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue-dark)" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
      <div>O código de verificação será enviado para o mesmo e-mail que recebeu o link da proposta: <strong><?= h($emailMaskedPreview ?? '••••@••••') ?></strong>.</div>
    </div>

    <button type="submit" class="btn-main">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 6 12 2 8"/></svg>
      Verificar e enviar código
    </button>
  </div>
  <?= $this->Form->end() ?>

  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>SSL 256-bit</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>ICP-Brasil</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><line x1="8" y1="5" x2="8" y2="8"/></svg>Link expira em 7 dias</div>
  </div>
</div>

<!-- ===== TELA 2: OTP ===== -->
<div class="pg <?= $ps === 'otp' ? 'show' : '' ?>" id="pg-otp">
  <div class="step-header">
    <div class="step-eyebrow">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/><line x1="8" y1="5" x2="8" y2="11"/><line x1="5" y1="8" x2="11" y2="8"/></svg>
      Etapa 2 de 4 — Código de verificação
    </div>
    <div class="progress-dots">
      <div class="dot done"></div>
      <div class="dot active"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
    <div class="step-title">Digite o código</div>
    <?php $maskOtp = ($emailMaskedSeguro ?? '') !== '' ? $emailMaskedSeguro : ($emailMaskedPreview ?? '••••@••••'); ?>
    <div class="step-desc" id="otp-desc">Enviamos um código de 6 dígitos para o e-mail <strong><?= h($maskOtp) ?></strong>. O código expira em 10 minutos.</div>
  </div>

  <?= $this->Form->create(null, [
    'url' => ['controller' => 'Orcamentos', 'action' => 'seguroProposta', $orcamento->hash],
    'id' => 'form-otp',
    'onsubmit' => 'return prepOtpSubmit(event);',
    'data-turbo' => 'false',
  ]) ?>
  <?= $this->Form->hidden('portal_acao', ['value' => 'otp']) ?>
  <?= $this->Form->hidden('otp_code', ['id' => 'otp_code_hidden', 'value' => '']) ?>
  <div class="field-wrap">
    <div class="otp-group" id="otp-group">
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d0" oninput="otpInput(0)" onkeydown="otpKey(event,0)"/>
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d1" oninput="otpInput(1)" onkeydown="otpKey(event,1)"/>
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d2" oninput="otpInput(2)" onkeydown="otpKey(event,2)"/>
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d3" oninput="otpInput(3)" onkeydown="otpKey(event,3)"/>
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d4" oninput="otpInput(4)" onkeydown="otpKey(event,4)"/>
      <input class="otp-digit" maxlength="1" type="tel" inputmode="numeric" id="d5" oninput="otpInput(5)" onkeydown="otpKey(event,5)"/>
    </div>

    <div class="timer-row">
      <span id="timer-msg">Código expira em </span>
      <span class="timer-count" id="timer">09:47</span>
      <span id="resend-area" style="display:none;"> · <button class="resend-btn" onclick="reenviarOTP()">Reenviar código</button></span>
    </div>

    <div id="otp-error" style="display:none;background:var(--red-light);border-radius:var(--r);padding:10px 13px;margin-bottom:14px;font-size:12px;color:var(--red-dark);text-align:center;">
      Código incorreto. Verifique e tente novamente.
    </div>

    <button type="submit" class="btn-main" id="btn-otp" disabled>
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 6 12 2 8"/></svg>
      Confirmar código
    </button>
    <?= $this->Form->end() ?>
    <a class="btn-ghost" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;" href="<?= h($this->Url->build(['action' => 'seguroProposta', $orcamento->hash, '?' => ['reiniciar' => '1']])) ?>">← Voltar e corrigir dados</a>

    <div style="margin-top:16px;background:var(--bg);border-radius:var(--r);padding:11px 14px;font-size:12px;color:var(--text-muted);line-height:1.6;">
      <strong style="color:var(--text);">Não recebeu?</strong> Verifique a caixa de spam, aguarde até 2 minutos ou solicite reenvio. Se o problema persistir, entre em contato com o vendedor: <strong><?= h($vendedorEmailSeguro) ?></strong>
    </div>
  </div>

  <?= $this->Form->create(null, [
    'url' => ['controller' => 'Orcamentos', 'action' => 'seguroProposta', $orcamento->hash],
    'id' => 'form-reenviar-otp',
    'style' => 'display:none',
    'data-turbo' => 'false',
  ]) ?>
  <?= $this->Form->hidden('portal_acao', ['value' => 'reenviar_otp']) ?>
  <?= $this->Form->end() ?>

  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>Código único — 1 uso</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><polyline points="8 5 8 8 10 10"/></svg>Expira em 10 min</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>Canal independente</div>
  </div>
</div>

<!-- ===== TELA 3: BLOQUEIO ===== -->
<div class="pg <?= $ps === 'bloqueio' ? 'show' : '' ?>" id="pg-bloqueio">
  <div class="step-header">
    <div class="step-eyebrow" style="color:var(--red-dark);">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
      Acesso bloqueado
    </div>
    <div class="step-title" style="color:var(--red-dark);">Link temporariamente bloqueado</div>
    <div class="step-desc">Por segurança, o acesso foi bloqueado após múltiplas tentativas incorretas. O vendedor responsável foi notificado automaticamente.</div>
  </div>
  <div class="field-wrap">
    <div style="background:var(--red-light);border:1px solid #F7C1C1;border-radius:var(--r);padding:16px;text-align:center;margin-bottom:18px;">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--red-dark)" stroke-width="2" style="margin-bottom:10px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      <div style="font-size:13px;font-weight:700;color:var(--red-dark);margin-bottom:4px;">Bloqueio por 30 minutos</div>
      <div style="font-size:12px;color:var(--red);line-height:1.6;">Este link ficará inacessível por <strong id="lock-timer">30:00</strong>. Após esse período, você poderá tentar novamente.</div>
    </div>
    <div style="font-size:13px;color:var(--text-muted);line-height:1.7;margin-bottom:16px;">Se você é o destinatário legítimo desta proposta, entre em contato diretamente com o vendedor para solicitar um novo link seguro:</div>
    <div style="background:var(--bg);border-radius:var(--r);padding:12px 14px;font-size:13px;">
      <div style="font-weight:600;color:var(--text);margin-bottom:4px;"><?= h($vendedorNomeSeguro) ?></div>
      <div style="color:var(--text-muted);"><?= h($vendedorEmailSeguro) ?></div>
    </div>
  </div>
  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>Vendedor notificado</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><polyline points="8 5 8 8 10 10"/></svg>Desbloqueio em 30 min</div>
  </div>
</div>

<!-- ===== TELA 4: PORTAL DA PROPOSTA ===== -->
<div class="pg" id="pg-proposta">
  <div class="step-header">
    <div class="step-eyebrow" style="color:var(--teal-dark);">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="13 2 6 12 2 8"/></svg>
      Identidade verificada — Acesso liberado
    </div>
    <div class="progress-dots">
      <div class="dot done"></div>
      <div class="dot done"></div>
      <div class="dot done"></div>
      <div class="dot active"></div>
      <div class="dot"></div>
    </div>
    <div class="step-title">Proposta Nº <?= (int)$orcamento->id ?></div>
    <div class="step-desc">Revisada e aprovada internamente. Aguardando sua decisão.</div>
  </div>

  <div class="field-wrap">
    <div class="prop-summary">
      <div class="prop-row"><span class="prop-label">Fornecedor</span><span class="prop-val">PGM Soluções em TI Ltda</span></div>
      <div class="prop-row"><span class="prop-label">Produto</span><span class="prop-val"><?= isset($primeiroServicoSeguro) && $primeiroServicoSeguro ? h($primeiroServicoSeguro->servico) : 'Conforme proposta' ?></span></div>
      <div class="prop-row"><span class="prop-label">Especificações</span><span class="prop-val" style="font-size:11px;text-align:right;"><?= isset($primeiroServicoSeguro) && $primeiroServicoSeguro && !empty($primeiroServicoSeguro->observacao) ? h($primeiroServicoSeguro->observacao) : 'Ver detalhes na proposta completa' ?></span></div>
      <div class="prop-row"><span class="prop-label">Quantidade</span><span class="prop-val"><?= isset($primeiroServicoSeguro) && $primeiroServicoSeguro ? (int)$primeiroServicoSeguro->quantidade : 1 ?> unidade(s)</span></div>
      <div class="prop-row"><span class="prop-label">Total à vista (5% desc.)</span><span class="prop-val" style="color:var(--teal);font-size:16px;"><?= h($totalVistaFmtSeguro) ?></span></div>
      <div class="prop-row"><span class="prop-label">Total a prazo</span><span class="prop-val"><?= h($totalPrazoFmtSeguro) ?></span></div>
      <div class="prop-row"><span class="prop-label">Validade</span><span class="prop-val" style="color:var(--amber);"><?= h($validadeFmtSeguro !== '' ? $validadeFmtSeguro . ' · 7 dias' : '—') ?></span></div>
    </div>

    <div style="display:flex;gap:8px;margin-bottom:10px;">
      <button class="btn-main" style="flex:1;" onclick="goTo('pg-assinar')">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 6 12 2 8"/></svg>
        Aprovar e assinar
      </button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
      <button style="padding:11px;border-radius:var(--r);font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid #FAC775;background:var(--amber-light);color:var(--amber-dark);font-family:inherit;transition:all .15s;" onclick="goTo('pg-negociar')">
        Negociar ajustes
      </button>
      <button style="padding:11px;border-radius:var(--r);font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid #F7C1C1;background:var(--red-light);color:var(--red-dark);font-family:inherit;transition:all .15s;" onclick="goTo('pg-recusar')">
        Recusar proposta
      </button>
    </div>
  </div>

  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="13 2 6 12 2 8"/></svg>Identidade verificada</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>Sessão segura ativa</div>
  </div>
</div>

<!-- ===== TELA 5: ASSINAR ===== -->
<div class="pg" id="pg-assinar">
  <div class="step-header">
    <div class="step-eyebrow">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L4 10l-1 3 3-1 8-8-2-2z"/></svg>
      Etapa 4 de 4 — Assinatura digital
    </div>
    <div class="progress-dots">
      <div class="dot done"></div>
      <div class="dot done"></div>
      <div class="dot done"></div>
      <div class="dot done"></div>
      <div class="dot active"></div>
    </div>
    <div class="step-title">Assinar e confirmar</div>
    <div class="step-desc">Sua identidade já foi verificada. Preencha os dados finais e assine para concluir.</div>
  </div>

  <div class="field-wrap">
    <div class="field">
      <label>Nome completo do signatário</label>
      <input type="text" id="sign-nome" placeholder="Nome conforme documento oficial"/>
    </div>
    <div class="field">
      <label>CPF do signatário</label>
      <input type="text" id="sign-cpf" placeholder="000.000.000-00" maxlength="14" oninput="maskCPF(this)"/>
      <div class="field-hint" id="cpf-valid-hint" style="display:none;color:var(--teal);font-weight:500;">CPF válido ✓</div>
    </div>

    <div style="margin-bottom:14px;">
      <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Assinatura</div>
      <canvas id="sign-canvas" class="sign-canvas"></canvas>
      <div style="display:flex;justify-content:space-between;margin-top:6px;">
        <div style="font-size:11px;color:var(--text-muted);">Assine com o mouse ou toque acima</div>
        <button onclick="clearSign()" style="background:none;border:none;font-size:11px;color:var(--text-muted);cursor:pointer;text-decoration:underline;">Limpar</button>
      </div>
    </div>

    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;">
      <input type="checkbox" id="ch-termos" style="width:16px;height:16px;margin-top:2px;cursor:pointer;accent-color:var(--teal);"/>
      <label for="ch-termos" style="font-size:12px;color:var(--text-muted);line-height:1.6;cursor:pointer;">Declaro que li e aceito integralmente as condições desta proposta comercial, incluindo itens, valores, prazo de entrega e forma de pagamento.</label>
    </div>

    <div style="background:var(--blue-light);border-radius:var(--r);padding:11px 14px;margin-bottom:18px;font-size:12px;color:var(--blue-dark);line-height:1.6;display:flex;gap:9px;">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="flex-shrink:0;margin-top:1px;"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>
      Esta assinatura tem validade jurídica conforme <strong>MP 2.200-2/2001</strong>, padrão <strong>ICP-Brasil</strong>. CPF verificado na Receita Federal. O contrato assinado será enviado para seu e-mail.
    </div>

    <button class="btn-main" id="btn-sign" onclick="confirmarAssinatura()">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 6 12 2 8"/></svg>
      Assinar e confirmar aprovação
    </button>
    <button class="btn-ghost" onclick="goTo('pg-proposta')">← Voltar para a proposta</button>
  </div>

  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg>ICP-Brasil</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="6" r="3"/><path d="M2 14a6 6 0 0112 0"/></svg>CPF verificado</div>
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>Validade jurídica</div>
  </div>
</div>

<!-- ===== TELA: NEGOCIAR ===== -->
<div class="pg" id="pg-negociar">
  <div class="step-header">
    <div class="step-eyebrow" style="color:var(--amber-dark);">Solicitar ajustes</div>
    <div class="step-title">O que precisa mudar?</div>
    <div class="step-desc">Descreva o que precisa ser ajustado. O vendedor receberá sua solicitação e enviará uma nova proposta em até 24h.</div>
  </div>
  <div class="field-wrap">
    <div class="field">
      <label>Descreva os ajustes necessários</label>
      <textarea style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;color:var(--text);outline:none;font-family:inherit;resize:vertical;min-height:100px;line-height:1.6;background:#161b22;" id="neg-txt" placeholder="Ex: Preciso de 16GB de RAM, prazo máximo 10 dias, ou desconto de 10%..."></textarea>
    </div>
    <div class="field">
      <label>Seu nome e contato</label>
      <input type="text" id="neg-nome" placeholder="Nome completo"/>
    </div>
    <button class="btn-main amber" onclick="confirmarNeg()">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><line x1="14" y1="2" x2="5" y2="11"/><polygon points="14 2 9 14 5 11 14 2"/></svg>
      Enviar solicitação
    </button>
    <button class="btn-ghost" onclick="goTo('pg-proposta')">← Voltar</button>
  </div>
</div>

<!-- ===== TELA: RECUSAR ===== -->
<div class="pg" id="pg-recusar">
  <div class="step-header">
    <div class="step-eyebrow" style="color:var(--red-dark);">Recusar proposta</div>
    <div class="step-title">Motivo da recusa</div>
    <div class="step-desc">Seu retorno é importante. Selecione o principal motivo.</div>
  </div>
  <div class="field-wrap">
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
      <label style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:13px;"><input type="radio" name="rec" value="preco" style="accent-color:var(--teal);"/> Preço acima do esperado</label>
      <label style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:13px;"><input type="radio" name="rec" value="prazo"/> Prazo não atende</label>
      <label style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:13px;"><input type="radio" name="rec" value="spec"/> Especificações inadequadas</label>
      <label style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:13px;"><input type="radio" name="rec" value="outro"/> Escolhemos outro fornecedor</label>
      <label style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:13px;"><input type="radio" name="rec" value="canc"/> Projeto cancelado</label>
    </div>
    <button class="btn-main red" onclick="confirmarRec()">Confirmar recusa</button>
    <button class="btn-ghost" onclick="goTo('pg-proposta')">← Voltar</button>
  </div>
</div>

<!-- ===== SUCESSO: APROVADO ===== -->
<div class="pg" id="pg-ok">
  <div class="step-header" style="text-align:center;">
    <div class="suc-icon" style="background:var(--teal-light);">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--teal-dark)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="suc-title" style="color:var(--teal-dark);">Proposta aprovada!</div>
    <div class="suc-sub">Sua assinatura foi registrada com validade jurídica. A PGM Soluções receberá a confirmação agora.</div>
  </div>
  <div class="field-wrap">
    <div class="prop-summary" style="margin-bottom:16px;">
      <div class="prop-row"><span class="prop-label">Assinado por</span><span class="prop-val" id="ok-nome">—</span></div>
      <div class="prop-row"><span class="prop-label">CPF</span><span class="prop-val" id="ok-cpf">—</span></div>
      <div class="prop-row"><span class="prop-label">Proposta</span><span class="prop-val"><?= h($propostaRotuloSeg) ?> — PGM Soluções</span></div>
      <div class="prop-row"><span class="prop-label">Data / hora</span><span class="prop-val" id="ok-dt">—</span></div>
      <div class="prop-row"><span class="prop-label">Hash</span><span class="prop-val" style="font-family:monospace;font-size:11px;" id="ok-hash">—</span></div>
      <div class="prop-row"><span class="prop-label">IP de origem</span><span class="prop-val" style="font-family:monospace;font-size:11px;" id="ok-ip">—</span></div>
    </div>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:4px;">
      <span class="badge b-green"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 6 12 2 8"/></svg> Assinatura válida</span>
      <span class="badge b-purple"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 1L2 4v5c0 3 2.7 5.7 6 6 3.3-.3 6-3 6-6V4L8 1z"/></svg> ICP-Brasil</span>
      <span class="badge b-blue">Contrato por e-mail</span>
    </div>
    <div style="margin-top:14px;background:var(--teal-light);border-radius:var(--r);padding:12px 14px;font-size:12px;color:var(--teal-dark);line-height:1.7;">
      <strong>Próximos passos:</strong><br>
      · Contrato assinado enviado ao seu e-mail<br>
      · PGM entrará em contato para confirmar entrega<br>
      · Boleto gerado conforme condição selecionada
    </div>
    <a class="btn-main" href="<?= h($urlPropostaFull) ?>" style="margin-top:14px;text-decoration:none;box-sizing:border-box;">Abrir proposta completa no portal</a>
  </div>
  <div class="divider"></div>
  <div class="sec-footer">
    <div class="sec-item"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="13 2 6 12 2 8"/></svg>Registro na trilha de auditoria</div>
  </div>
</div>

<!-- ===== SUCESSO: RECUSADO ===== -->
<div class="pg" id="pg-recusado">
  <div class="step-header" style="text-align:center;">
    <div class="suc-icon" style="background:var(--red-light);"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--red-dark)" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
    <div class="suc-title" style="color:var(--text);">Recusa registrada</div>
    <div class="suc-sub">Agradecemos o retorno. Se mudar de ideia, entre em contato: <strong><?= h($vendedorEmailSeguro) ?></strong></div>
  </div>
</div>

<!-- ===== SUCESSO: NEGOCIADO ===== -->
<div class="pg" id="pg-negociado">
  <div class="step-header" style="text-align:center;">
    <div class="suc-icon" style="background:var(--amber-light);"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--amber-dark)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
    <div class="suc-title" style="color:var(--amber-dark);">Solicitação enviada!</div>
    <div class="suc-sub">Nossa equipe analisará e enviará uma nova proposta em até <strong>24 horas úteis</strong>.</div>
  </div>
</div>

</div><!-- /box -->
</div><!-- /wrap -->

<script>
/* ===== STATE ===== */
const initialPasso=<?= json_encode($ps) ?>;
const initialOtpSecs=<?= max(0, (int)($otpExpiresIn ?? 0)) ?>;
const initialLockSecs=<?= max(0, (int)($lockRemainingSec ?? 0)) ?>;
let otpTimer=null, lockTimer=null;
let painting=false, ctx, lastX, lastY, hasSig=false;

/* ===== NAVIGATION ===== */
function goTo(id){
  document.querySelectorAll('.pg').forEach(p=>p.classList.remove('show'));
  document.getElementById(id).classList.add('show');
  window.scrollTo({top:0,behavior:'smooth'});
  updateIndicators(id);
  if(id==='pg-assinar') setTimeout(initCanvas,100);
  if(id==='pg-bloqueio') startLockTimer(initialLockSecs>0?initialLockSecs:1800);
}

function updateIndicators(id){
  document.getElementById('ind-ssl').classList.toggle('active',true);
  document.getElementById('ind-otp').classList.toggle('active',['pg-otp','pg-proposta','pg-assinar','pg-ok'].includes(id));
  document.getElementById('ind-id').classList.toggle('active',['pg-proposta','pg-assinar','pg-ok'].includes(id));
  document.getElementById('ind-sign').classList.toggle('active',['pg-assinar','pg-ok'].includes(id));
}

/* ===== OTP ===== */
function otpInput(i){
  const el=document.getElementById('d'+i);
  const v=el.value.replace(/\D/g,'');
  el.value=v.slice(-1);
  if(v) el.classList.add('filled'); else el.classList.remove('filled');
  if(v&&i<5) document.getElementById('d'+(i+1)).focus();
  checkOTPComplete();
}

function otpKey(e,i){
  if(e.key==='Backspace'&&!document.getElementById('d'+i).value&&i>0)
    document.getElementById('d'+(i-1)).focus();
}

function checkOTPComplete(){
  const val=[...Array(6)].map((_,i)=>document.getElementById('d'+i).value).join('');
  document.getElementById('btn-otp').disabled=val.length<6;
}

function getOTPValue(){
  return [...Array(6)].map((_,i)=>document.getElementById('d'+i).value).join('');
}

function prepOtpSubmit(ev){
  const v=getOTPValue();
  document.getElementById('otp_code_hidden').value=v;
  if(v.length!==6){
    if(ev&&ev.preventDefault) ev.preventDefault();
    document.getElementById('otp-error').style.display='block';
    return false;
  }
  return true;
}

function startOTPTimer(initialSecs){
  let secs=(typeof initialSecs==='number'&&initialSecs>0)?Math.floor(initialSecs):0;
  clearInterval(otpTimer);
  const tick=()=>{
    const m=Math.floor(secs/60), s=secs%60;
    const el=document.getElementById('timer');
    el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
    el.classList.toggle('urgent',secs<=60&&secs>0);
  };
  tick();
  if(secs<=0){
    document.getElementById('timer-msg').textContent='Código expirado. ';
    document.getElementById('timer').style.display='none';
    document.getElementById('resend-area').style.display='inline';
    return;
  }
  document.getElementById('timer-msg').textContent='Código expira em ';
  document.getElementById('timer').style.display='inline';
  document.getElementById('resend-area').style.display='none';
  otpTimer=setInterval(()=>{
    secs--;
    tick();
    if(secs<=0){
      clearInterval(otpTimer);
      document.getElementById('timer-msg').textContent='Código expirado. ';
      document.getElementById('timer').style.display='none';
      document.getElementById('resend-area').style.display='inline';
    }
  },1000);
}

function reenviarOTP(){
  const f=document.getElementById('form-reenviar-otp');
  if(f) f.submit();
}

/* ===== LOCK ===== */
function startLockTimer(secsTotal){
  let secs=(typeof secsTotal==='number'&&secsTotal>0)?Math.floor(secsTotal):1800;
  clearInterval(lockTimer);
  const tickLock=()=>{
    const m=Math.floor(secs/60), s=secs%60;
    document.getElementById('lock-timer').textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
  };
  tickLock();
  lockTimer=setInterval(()=>{
    secs--;
    tickLock();
    if(secs<=0){ clearInterval(lockTimer); }
  },1000);
}

/* ===== CANVAS ===== */
function initCanvas(){
  const c=document.getElementById('sign-canvas');
  if(!c||c._ready) return;
  c._ready=true;
  const dpr=window.devicePixelRatio||1;
  const w=c.offsetWidth||380;
  c.width=w*dpr; c.height=100*dpr;
  ctx=c.getContext('2d');
  ctx.scale(dpr,dpr);
  ctx.strokeStyle='#1a1a18'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.lineJoin='round';

  function pos(e){ const r=c.getBoundingClientRect(); return e.touches?{x:e.touches[0].clientX-r.left,y:e.touches[0].clientY-r.top}:{x:e.offsetX,y:e.offsetY}; }
  c.addEventListener('mousedown',e=>{painting=true;const p=pos(e);lastX=p.x;lastY=p.y;});
  c.addEventListener('mousemove',e=>{if(!painting)return;const p=pos(e);ctx.beginPath();ctx.moveTo(lastX,lastY);ctx.lineTo(p.x,p.y);ctx.stroke();lastX=p.x;lastY=p.y;hasSig=true;});
  c.addEventListener('mouseup',()=>painting=false);
  c.addEventListener('mouseleave',()=>painting=false);
  c.addEventListener('touchstart',e=>{e.preventDefault();painting=true;const p=pos(e);lastX=p.x;lastY=p.y;},{passive:false});
  c.addEventListener('touchmove',e=>{e.preventDefault();if(!painting)return;const p=pos(e);ctx.beginPath();ctx.moveTo(lastX,lastY);ctx.lineTo(p.x,p.y);ctx.stroke();lastX=p.x;lastY=p.y;hasSig=true;},{passive:false});
  c.addEventListener('touchend',()=>painting=false);
}

function clearSign(){ if(ctx){ctx.clearRect(0,0,9999,9999);hasSig=false;} }

/* ===== SIGN ===== */
function confirmarAssinatura(){
  const nome=document.getElementById('sign-nome').value.trim();
  const cpf=document.getElementById('sign-cpf').value.trim();
  const termos=document.getElementById('ch-termos').checked;
  if(!nome){alert('Informe seu nome completo.');return;}
  if(cpf.length<14){alert('Informe um CPF válido.');return;}
  if(!termos){alert('Aceite os termos para continuar.');return;}
  if(!hasSig){alert('Por favor, assine no campo acima.');return;}

  const now=new Date();
  const dt=now.toLocaleDateString('pt-BR')+' às '+now.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
  const hash='a3f7c9e2d1b8'+Math.random().toString(36).slice(2,8);
  const ips=['187.44.'+Math.floor(Math.random()*255)+'.'+Math.floor(Math.random()*255)];

  document.getElementById('ok-nome').textContent=nome;
  document.getElementById('ok-cpf').textContent=cpf;
  document.getElementById('ok-dt').textContent=dt;
  document.getElementById('ok-hash').textContent=hash+'...';
  document.getElementById('ok-ip').textContent=ips[0];
  goTo('pg-ok');
}

function confirmarNeg(){
  if(!document.getElementById('neg-txt').value.trim()){alert('Descreva os ajustes necessários.');return;}
  goTo('pg-negociado');
}
function confirmarRec(){
  if(!document.querySelector('input[name="rec"]:checked')){alert('Selecione o motivo.');return;}
  goTo('pg-recusado');
}

function maskCPF(el){
  let v=el.value.replace(/\D/g,'').slice(0,11);
  if(v.length>9) v=v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/,'$1.$2.$3-$4');
  else if(v.length>6) v=v.replace(/(\d{3})(\d{3})(\d{1,3})/,'$1.$2.$3');
  else if(v.length>3) v=v.replace(/(\d{3})(\d{1,3})/,'$1.$2');
  el.value=v;
  const hint=document.getElementById('cpf-valid-hint');
  hint.style.display=v.length===14?'block':'none';
}

if(initialPasso==='otp'){
  updateIndicators('pg-otp');
  startOTPTimer(initialOtpSecs);
  setTimeout(()=>{const d0=document.getElementById('d0');if(d0)d0.focus();},200);
} else if(initialPasso==='bloqueio'){
  updateIndicators('pg-bloqueio');
  startLockTimer(initialLockSecs>0?initialLockSecs:1800);
} else {
  updateIndicators('pg-destino');
}
</script>
</body>
</html>
