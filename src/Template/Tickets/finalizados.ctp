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
                  <?php $urlTicket = $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]); ?>
                  <tr>
                    <td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= (int)$reg->id ?></a></td>
                    <td><?= h($reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial) ?></td>
                    <td><?= h($reg->user->name ?? $reg->user->username ?? '') ?></td>
                    <td><?= !empty($reg->datafinalizado) ? h($reg->datafinalizado) : date_format($reg->modified ?? $reg->created, 'd/m/Y') ?></td>
                    <td class="dash-erp-actions">
                      <a class="btn btn-outline-info btn-sm" target="_blank" href="<?= $urlTicket ?>">Ver</a>
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
</script>

