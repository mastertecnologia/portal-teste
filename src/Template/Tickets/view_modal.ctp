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
  .tv-section{ padding: 0 16px 16px; }
  .tv-section h3{ margin: 16px 0 10px; font-size: 13px; font-weight: 900; color:#0f172a; }
  .tv-list{
    border:1px solid rgba(15,23,42,.08);
    border-radius: 12px;
    background:#fff;
    overflow:hidden;
  }
  .tv-row{
    padding: 10px 12px;
    border-top:1px solid rgba(15,23,42,.08);
  }
  .tv-row:first-child{ border-top:0; }
  .tv-row-top{
    display:flex;
    align-items:baseline;
    justify-content:space-between;
    gap:10px;
    margin-bottom:6px;
  }
  .tv-row-title{ font-weight: 900; color:#0f172a; font-size: 12px; }
  .tv-row-meta{ color:#64748b; font-size: 11px; white-space: nowrap; }
  .tv-row-body{ color:#0f172a; font-size: 12px; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
  .tv-table-wrap{
    border:1px solid rgba(15,23,42,.08);
    border-radius: 12px;
    overflow:auto;
    background:#fff;
  }
  .tv-table{ width:100%; border-collapse: collapse; }
  .tv-table th, .tv-table td{ padding: 10px 12px; border-bottom:1px solid rgba(15,23,42,.08); font-size: 12px; text-align:left; }
  .tv-table th{ font-size: 11px; color:#64748b; text-transform: uppercase; letter-spacing:.04em; }
  .tv-table td{ color:#0f172a; }
  .tv-pill{
    display:inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    background:#eef2ff;
    color:#3730a3;
  }
  @media (max-width: 900px){
    .tv-item{ grid-column: span 6; }
  }
  @media (max-width: 560px){
    .tv-item{ grid-column: span 12; }
  }
</style>

<div class="tv-wrap">
  <div class="tv-card">
    <?php
      $tvPlain = function ($v) {
        $s = (string)$v;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = strip_tags($s);
        $s = preg_replace("/[ \\t]+/", " ", $s);
        $s = preg_replace("/\\R{3,}/", "\n\n", $s);
        return trim($s);
      };
    ?>
    <div class="tv-head">
      <div>
        <h2 class="tv-title">Ticket nº <?= (int)$ticket->id ?></h2>
        <?php $situacaoTexto = trim(strip_tags((string)SituacaoTicket($ticket->situacao))); ?>
        <p class="tv-sub"><?= h($situacaoTexto) ?> • Abertura: <?= h(date_format($ticket->created, 'd/m/Y')) ?></p>
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

    <div class="tv-section">
      <h3>Comentários <span class="tv-pill"><?= (int)count($ticketcomentarios ?? []) ?></span></h3>
      <div class="tv-list">
        <?php if (!empty($ticketcomentarios)) { ?>
          <?php foreach (array_reverse($ticketcomentarios) as $comentario) { ?>
            <div class="tv-row">
              <div class="tv-row-top">
                <div class="tv-row-title"><?= h($comentario['Users']['name'] ?? '') ?></div>
                <div class="tv-row-meta">
                  <?= !empty($comentario->created) ? h(date_format($comentario->created, 'd/m/Y H:i')) : '' ?>
                </div>
              </div>
              <div class="tv-row-body"><?= h($tvPlain($comentario->comentario ?? '')) ?></div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="tv-row">
            <div class="tv-row-body">Nenhum comentário encontrado.</div>
          </div>
        <?php } ?>
      </div>
    </div>

    <div class="tv-section">
      <h3>Movimentações <span class="tv-pill"><?= (int)count($ticketsmovs ?? []) ?></span></h3>
      <div class="tv-list">
        <?php if (!empty($ticketsmovs)) { ?>
          <?php foreach (array_reverse($ticketsmovs) as $reg) { ?>
            <?php
              $data = $reg['datetime'] ?? null;
              $dataTexto = '';
              if ($data) {
                try {
                  $dataTexto = $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                  $dataTexto = (string)$data;
                }
              }
              $autorMov = $reg['user']->name ?? '';
              $obs = $reg['observacao'] ?? '';
              $sitantiga = $reg['sitantiga'] ?? null;
              $sitnova = $reg['sitnova'] ?? null;
              $msg = null;
              if ($sitnova === C_TicketAnexoAdicionado) $msg = "Anexo adicionado: " . $obs;
              else if ($sitnova === C_TicketAnexoDeletado) $msg = "Anexo deletado: " . $obs;
              else if ($sitantiga !== null && $sitnova !== null) {
                $sitAntTxt = trim(strip_tags((string)SituacaoTicket($sitantiga)));
                $sitNovTxt = trim(strip_tags((string)SituacaoTicket($sitnova)));
                if ($sitAntTxt !== '' && $sitNovTxt !== '' && $sitAntTxt !== $sitNovTxt) {
                  $msg = "Situação: " . $sitAntTxt . " → " . $sitNovTxt;
                } elseif (!empty($obs)) {
                  $msg = $obs;
                } else {
                  $msg = "Atualização registrada: " . ($sitNovTxt !== '' ? $sitNovTxt : 'situação mantida');
                }
                if (!empty($obs) && $msg !== $obs) $msg .= "\nObs.: " . $obs;
              } else {
                $msg = !empty($obs) ? $obs : 'Movimentação registrada.';
              }
            ?>
            <div class="tv-row">
              <div class="tv-row-top">
                <div class="tv-row-title"><?= h($autorMov) ?></div>
                <div class="tv-row-meta"><?= h($dataTexto) ?></div>
              </div>
              <div class="tv-row-body"><?= h($msg) ?></div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="tv-row">
            <div class="tv-row-body">Nenhuma movimentação encontrada.</div>
          </div>
        <?php } ?>
      </div>
    </div>

    <div class="tv-section">
      <h3>Anexos <span class="tv-pill"><?= (int)count($ticketanexos ?? []) ?></span></h3>
      <div class="tv-table-wrap">
        <table class="tv-table">
          <thead>
            <tr>
              <th>Arquivo</th>
              <th style="width:120px">Download</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($ticketanexos)) { ?>
              <?php foreach ($ticketanexos as $reg) { ?>
                <tr>
                  <td><?= h($reg->arquivo ?? '') ?></td>
                  <td>
                    <?= $this->Html->link('Baixar', ["controller" => "Tickets", "action" => "downloadAnexo", $reg->id], ['target' => '_blank', 'rel' => 'noopener', 'class' => 'btn btn-info btn-sm', 'escape' => false]) ?>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="2">Nenhum anexo encontrado.</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (($role ?? null) == 0) { ?>
      <div class="tv-section">
        <h3>Horas cadastradas <span class="tv-pill"><?= (int)count($ticketshoras ?? []) ?></span></h3>
        <div class="tv-table-wrap">
          <table class="tv-table">
            <thead>
              <tr>
                <th>Usuário</th>
                <th>Data</th>
                <th>Horário</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($ticketshoras)) { ?>
                <?php foreach ($ticketshoras as $reg) { ?>
                  <tr>
                    <td><?= h($reg->user->username ?? '') ?></td>
                    <td><?= !empty($reg->data) ? h(date_format($reg->data, 'd/m/Y')) : '' ?></td>
                    <td>
                      <?php
                        $ini = !empty($reg->horaini) ? date_format($reg->horaini, 'H:i') : '';
                        $fim = !empty($reg->horafin) ? date_format($reg->horafin, 'H:i') : '';
                        echo h(trim($ini . ($fim ? " - $fim" : '')));
                      ?>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="3">Nenhum registro de horas encontrado.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>
  </div>
</div>

