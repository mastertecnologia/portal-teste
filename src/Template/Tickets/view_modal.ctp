<style>
  body { background: #f6f8fb; }
  .tv-wrap { padding: 14px; }
  .tv-card {
    background:#fff;
    border:1px solid rgba(15,23,42,.10);
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(15,23,42,.08);
    overflow:hidden;
  }
  .tv-head{
    padding: 14px 16px;
    border-bottom:1px solid rgba(15,23,42,.10);
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
  }
  .tv-title{
    margin:0;
    font-weight: 900;
    letter-spacing:-.02em;
    color:#0f172a;
  }
  .tv-sub{
    margin: 4px 0 0;
    color:#64748b;
    font-size: 12px;
  }
  .tv-grid{
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 10px;
    padding: 14px 16px;
  }
  .tv-item{
    grid-column: span 4;
    border:1px solid rgba(15,23,42,.08);
    border-radius: 12px;
    padding: 10px 12px;
    background: #f8fafc;
  }
  .tv-label{ font-size: 11px; color:#64748b; font-weight:800; text-transform: uppercase; letter-spacing:.04em; margin:0; }
  .tv-value{ margin:4px 0 0; font-size: 13px; color:#0f172a; font-weight:700; word-break: break-word; }
  .tv-desc{
    padding: 0 16px 16px;
  }
  .tv-desc h3{
    margin: 10px 0 8px;
    font-size: 13px;
    font-weight: 900;
    color:#0f172a;
  }
  .tv-desc .tv-box{
    border:1px solid rgba(15,23,42,.08);
    border-radius: 12px;
    padding: 12px;
    background:#fff;
  }
  .tv-desc img{ max-width: 100%; height:auto; }
  @media (max-width: 900px){
    .tv-item{ grid-column: span 6; }
  }
  @media (max-width: 560px){
    .tv-item{ grid-column: span 12; }
  }
</style>

<div class="tv-wrap">
  <div class="tv-card">
    <div class="tv-head">
      <div>
        <h2 class="tv-title">Ticket nº <?= (int)$ticket->id ?></h2>
        <p class="tv-sub"><?= h(SituacaoTicket($ticket->situacao)) ?> • Abertura: <?= h(date_format($ticket->created, 'd/m/Y')) ?></p>
      </div>
    </div>

    <div class="tv-grid">
      <div class="tv-item">
        <p class="tv-label">Autor</p>
        <p class="tv-value"><?= h($ticket['users']['name'] ?? '') ?></p>
      </div>
      <div class="tv-item">
        <p class="tv-label">Cliente</p>
        <p class="tv-value"><?= h($clienteNome ?? '') ?></p>
      </div>
      <div class="tv-item">
        <p class="tv-label">Solicitante</p>
        <p class="tv-value"><?= h($solicitante ?? '-') ?></p>
      </div>
      <div class="tv-item">
        <p class="tv-label">E-mail</p>
        <p class="tv-value"><?= h($ticket->email ?? '-') ?></p>
      </div>
      <div class="tv-item">
        <p class="tv-label">Assunto</p>
        <p class="tv-value"><?= h(AssuntoTicket($ticket->assunto)) ?></p>
      </div>
      <div class="tv-item">
        <p class="tv-label">Finalizado</p>
        <p class="tv-value"><?= !empty($ticket->datafinalizado) ? h($ticket->datafinalizado) : '-' ?></p>
      </div>
    </div>

    <div class="tv-desc">
      <h3>Descrição</h3>
      <div class="tv-box">
        <?= $ticket->solicitacao ?>
      </div>
    </div>
  </div>
</div>

