<?php
/**
 * Página pública de validação de parecer (sem login).
 * Carrega dados via /api/laudos/validar/:hash
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Validação de Parecer Técnico — PGM</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f4f6f9; }
    .card-validacao { max-width: 600px; margin: 60px auto; border-radius: 12px; }
    .status-badge { font-size: 1rem; padding: .5em 1em; }
    .logo-area { background: #1D9E75; color: #fff; border-radius: 12px 12px 0 0; padding: 24px; }
  </style>
</head>
<body>
<div class="container">
  <div class="card shadow card-validacao">
    <div class="logo-area text-center">
      <h4 class="mb-1">Parecer Técnico</h4>
      <small>Validação de autenticidade</small>
    </div>
    <div class="card-body p-4" id="validacao-body">
      <div class="text-center py-4 text-muted">
        <div class="spinner-border mb-3"></div>
        <p>Consultando documento…</p>
      </div>
    </div>
    <div class="card-footer text-muted small text-center">
      Sistema PGM Portal — laudos.pgm.inf.br
    </div>
  </div>
</div>

<script>
(function() {
  const hash = <?= json_encode($publicHash) ?>;
  const body = document.getElementById('validacao-body');

  if (!hash) {
    body.innerHTML = '<div class="alert alert-danger">Hash inválido ou ausente.</div>';
    return;
  }

  fetch('/api/laudos/validar/' + encodeURIComponent(hash))
    .then(r => r.json())
    .then(json => {
      if (!json.success || !json.data) {
        body.innerHTML = '<div class="alert alert-danger text-center"><strong>Documento não encontrado</strong><br>O hash informado não corresponde a nenhum parecer válido.</div>';
        return;
      }
      const d = json.data;
      const statusCls = { rascunho:'secondary', em_analise:'warning', aprovado:'info', concluido:'success', enviado:'primary' };
      const cls = statusCls[d.status] || 'secondary';

      body.innerHTML = `
        <div class="text-center mb-4">
          <span class="badge bg-${cls} status-badge">${escH(d.status_label || d.status)}</span>
        </div>
        <table class="table table-sm">
          <tr><th style="width:40%">Número</th><td><code>${escH(d.numero)}</code></td></tr>
          <tr><th>Data de emissão</th><td>${fmtDate(d.data_emissao)}</td></tr>
          <tr><th>Emitido por</th><td>${escH(d.emitido_por)}</td></tr>
          <tr><th>CNPJ emitente</th><td>${escH(d.cnpj_emitente)}</td></tr>
          <tr><th>Cliente</th><td>${escH(d.cliente_nome || '—')}</td></tr>
          <tr><th>CNPJ cliente</th><td>${escH(d.cliente_cnpj || '—')}</td></tr>
          <tr><th>Técnico</th><td>${escH(d.tecnico || '—')}</td></tr>
          <tr><th>Cidade</th><td>${escH(d.cidade || '—')}</td></tr>
        </table>
        <div class="alert alert-success text-center mt-3 mb-0">
          <strong>✓ Documento autêntico</strong><br>
          <small>Este parecer foi emitido pelo sistema PGM Portal e sua autenticidade foi verificada.</small>
        </div>
      `;
    })
    .catch(() => {
      body.innerHTML = '<div class="alert alert-danger text-center">Erro ao consultar documento. Tente novamente.</div>';
    });

  function escH(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function fmtDate(d) {
    if (!d) return '—';
    const parts = String(d).split('T')[0].split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : d;
  }
})();
</script>
</body>
</html>
