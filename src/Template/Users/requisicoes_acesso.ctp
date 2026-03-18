<?= $this->Html->css('dist/css/dashboard-erp.css') ?>

<div class="col-12 p-0">
  <div class="dash-erp">
    <div class="dash-erp-header">
      <div>
        <h2 class="dash-erp-title">Requisições de acesso</h2>
        <p class="dash-erp-subtitle">Solicitações feitas por clientes ao se cadastrarem. Libere com segurança mantendo a integração existente.</p>
      </div>
      <div>
        <a class="btn btn-outline-secondary" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao dashboard</a>
      </div>
    </div>

    <div class="dash-erp-card">
      <div class="dash-erp-card-header">
        <h5 class="dash-erp-card-title">Pendentes</h5>
        <span class="dash-erp-card-badge"><?= count($usuariosBloqueadosTable ?? []) ?></span>
      </div>
      <div class="dash-erp-card-body">
        <div class="dash-erp-scroll" id="req-acesso-scroll" style="max-height: 70dvh;">
          <div class="table-responsive">
            <table class="dash-erp-table">
              <thead>
                <tr>
                  <th>Login</th>
                  <th>Nome do Cliente</th>
                  <th>CNPJ do Cliente</th>
                  <th>Empresa</th>
                  <th style="width: 140px;">Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($usuariosBloqueadosTable ?? []) as $reg): ?>
                  <tr>
                    <td><?= h($reg->username) ?></td>
                    <td><?= h($reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial) ?></td>
                    <td><?= h($reg->cliente->tipo == C_ClientesTipoFisica ? formatCnpjCpf($reg->cliente->cpf) : formatCnpjCpf($reg->cliente->cnpj)) ?></td>
                    <td><?= h($reg->empresasusers[0]->empresa->nomefantasia ?? '') ?></td>
                    <td class="dash-erp-actions">
                      <a class="btn btn-success btn-sm" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'desbloquear', $reg->id]) ?>">Liberar</a>
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
  $("#req-acesso-scroll").perfectScrollbar();
</script>

