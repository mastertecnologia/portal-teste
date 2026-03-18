<?= $this->Html->css('dist/css/dashboard-erp.css') ?>

<div class="col-12 p-0">
  <div class="dash-erp">
    <div class="dash-erp-header">
      <div>
        <h2 class="dash-erp-title">Tickets finalizados</h2>
        <p class="dash-erp-subtitle">Listagem dos tickets em situação Resolvido/Fechado. Visualização rápida e corporativa (sem alterar integrações).</p>
      </div>
      <div>
        <a class="btn btn-outline-secondary m-r-10" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao dashboard</a>
        <button type="button" class="btn btn-outline-info" onclick="window.location.reload()">Atualizar</button>
      </div>
    </div>

    <div class="dash-erp-card">
      <div class="dash-erp-card-header">
        <h5 class="dash-erp-card-title">Finalizados</h5>
        <span class="dash-erp-card-badge"><?= count($ticketsFinalizados ?? []) ?></span>
      </div>
      <div class="dash-erp-card-body">
        <div class="row m-b-10">
          <div class="col-md-6 col-sm-12">
            <input type="text" class="form-control" id="filtro-finalizados" placeholder="Buscar por ID, cliente, assunto, autor..." />
          </div>
          <div class="col-md-6 col-sm-12 text-right text-muted" style="padding-top:8px;">
            Exibindo <span id="finalizados-count"><?= count($ticketsFinalizados ?? []) ?></span> registros
          </div>
        </div>

        <div class="dash-erp-scroll" id="finalizados-scroll" style="max-height: 70dvh;">
          <div class="table-responsive">
            <table class="dash-erp-table" id="table-finalizados">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Cliente</th>
                  <th>Autor</th>
                  <th>Finalizado</th>
                  <th style="width: 110px;">Abrir</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($ticketsFinalizados ?? []) as $reg): ?>
                  <?php
                    $urlTicketEdit = $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]);
                    $urlTicketView = $this->Url->build(["controller" => "Tickets", "action" => "viewModal", $reg->id]);
                    $urlTicketPrint = $this->Url->build(["controller" => "Tickets", "action" => "imprimir", $reg->id]);
                    $urlTicketEmail = $this->Url->build(["controller" => "Tickets", "action" => "email", $reg->id, "redirect"]);
                  ?>
                  <tr>
                    <td><a class="dash-erp-link" target="_blank" href="<?= $urlTicketEdit ?>"><?= (int)$reg->id ?></a></td>
                    <td><?= h($reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial) ?></td>
                    <td><?= h($reg->user->name ?? $reg->user->username ?? '') ?></td>
                    <td><?= !empty($reg->datafinalizado) ? h($reg->datafinalizado) : date_format($reg->modified ?? $reg->created, 'd/m/Y') ?></td>
                    <td class="dash-erp-actions">
                      <button
                        type="button"
                        class="btn btn-outline-info btn-sm btn-ver-ticket"
                        data-toggle="modal"
                        data-target="#modal-ticket-finalizado"
                        data-id="<?= (int)$reg->id ?>"
                        data-url-view="<?= h($urlTicketView) ?>"
                        data-url-edit="<?= h($urlTicketEdit) ?>"
                        data-url-print="<?= h($urlTicketPrint) ?>"
                        data-url-email="<?= h($urlTicketEmail) ?>"
                      >
                        Ver
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: visualizar ticket finalizado (popup) -->
<div class="modal fade" id="modal-ticket-finalizado" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title m-0">Ticket <span id="modal-ticket-id">-</span></h5>
          <small class="text-muted">Visualização rápida. Use os botões para imprimir/PDF ou enviar por e-mail.</small>
        </div>
        <div class="ml-auto d-flex align-items-center" style="gap:8px;">
          <a class="btn btn-outline-secondary btn-sm" id="modal-ticket-abrir" target="_blank" href="#">Abrir completo</a>
          <a class="btn btn-outline-info btn-sm" id="modal-ticket-imprimir" target="_blank" href="#">Imprimir / PDF</a>
          <a class="btn btn-outline-success btn-sm" id="modal-ticket-email" target="_blank" href="#">Enviar e-mail</a>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="modal-body p-0" style="height: 72vh;">
        <iframe
          id="modal-ticket-iframe"
          title="Ticket"
          src="about:blank"
          style="width:100%; height:100%; border:0;"
        ></iframe>
      </div>
    </div>
  </div>
</div>

<script>
  $("#finalizados-scroll").perfectScrollbar();
  (function(){
    var $input = $('#filtro-finalizados');
    var $rows = $('#table-finalizados tbody tr');
    function apply(){
      var q = ($input.val() || '').toLowerCase().trim();
      var shown = 0;
      $rows.each(function(){
        var t = $(this).text().toLowerCase();
        var ok = q === '' || t.indexOf(q) !== -1;
        $(this).toggle(ok);
        if(ok) shown++;
      });
      $('#finalizados-count').text(shown);
    }
    $input.on('input', apply);
  })();

  (function(){
    function setHref(id, href){
      var el = document.getElementById(id);
      if (el) el.setAttribute('href', href || '#');
    }
    $('.btn-ver-ticket').on('click', function(){
      var $b = $(this);
      $('#modal-ticket-id').text($b.data('id') || '-');
      setHref('modal-ticket-abrir', $b.data('url-edit'));
      setHref('modal-ticket-imprimir', $b.data('url-print'));
      setHref('modal-ticket-email', $b.data('url-email'));
      var iframe = document.getElementById('modal-ticket-iframe');
      if (iframe) iframe.src = $b.data('url-view') || 'about:blank';
    });
    $('#modal-ticket-finalizado').on('hidden.bs.modal', function(){
      var iframe = document.getElementById('modal-ticket-iframe');
      if (iframe) iframe.src = 'about:blank';
    });
  })();
</script>

