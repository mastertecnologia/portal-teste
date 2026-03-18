<?php
  $ticketId = (int)($ticket->id ?? 0);
  $clienteNome = '';
  if (!empty($ticket->cliente)) {
    $clienteNome = ($ticket->cliente->tipo == C_ClientesTipoFisica) ? $ticket->cliente->nome : $ticket->cliente->razaosocial;
  }
  $autorNome = $ticket->user->name ?? $ticket->user->username ?? '';
?>

<div class="col-md-12">
  <div class="card">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <h3 class="m-b-0">Enviar e-mail</h3>
          <p class="text-muted m-b-0">
            Ticket nº <?= $ticketId ?> • Cliente: <?= h($clienteNome) ?> • Autor: <?= h($autorNome) ?>
          </p>
        </div>
        <div class="text-right">
          <?= $this->Html->link('Voltar', ['action' => !empty($redirectAfter) ? 'edit' : 'finalizados', $ticketId], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
      </div>

      <hr />

      <?= $this->Form->create(null, ['url' => ['action' => 'email', $ticketId, $situacao, $redirectAfter], 'class' => 'form-material']) ?>
        <?= $this->Form->hidden('redirect', ['value' => $redirectAfter]) ?>

        <div class="row">
          <div class="col-lg-8">
            <div class="form-group">
              <label class="font-weight-bold">Para</label>
              <?= $this->Form->text('para', [
                'id' => 'email-para',
                'class' => 'form-control',
                'placeholder' => 'Digite um ou mais e-mails (separe por ; ou ,)',
                'value' => !empty($defaultPara) ? $defaultPara : (!empty($sugestoes[0]) ? $sugestoes[0] : ''),
                'autocomplete' => 'off',
                'list' => 'email-sugestoes',
              ]) ?>
              <small class="text-muted">Você pode digitar vários e-mails separados por <strong>;</strong> ou <strong>,</strong>.</small>
              <datalist id="email-sugestoes">
                <?php foreach (($sugestoes ?? []) as $e): ?>
                  <option value="<?= h($e) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="form-group">
              <label class="font-weight-bold">Sugestões</label>
              <div class="m-b-10">
                <span class="text-muted" id="email-sugestoes-selected-count">Destinatários extras (0)</span>
              </div>

              <?php if (!empty($sugestoes)) { ?>
                <input
                  type="text"
                  id="email-sugestoes-search"
                  class="form-control"
                  placeholder="Buscar e-mail..."
                  autocomplete="off"
                />

                <div
                  id="email-sugestoes-panel"
                  style="display:none; position: relative; background: #fff; border: 1px solid rgba(15,23,42,.10); border-radius: 12px; box-shadow: 0 10px 24px rgba(15,23,42,.08); margin-top: 10px; padding: 12px;"
                >
                  <div class="d-flex" style="gap:10px; align-items:center; margin-bottom: 10px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="email-sugestoes-select-all">
                      Selecionar tudo
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="email-sugestoes-deselect-all">
                      Desselecionar tudo
                    </button>
                  </div>

                  <div
                    id="email-sugestoes-list"
                    style="max-height: 240px; overflow:auto; padding-right: 4px;"
                  >
                    <?php foreach (($sugestoes ?? []) as $i => $e): ?>
                      <div
                        class="custom-control custom-checkbox m-b-10 email-sugestoes-item"
                        data-email="<?= h((string)$e) ?>"
                      >
                        <?= $this->Form->checkbox('sugestoes[]', [
                          'id' => 'sug-extra-' . $i,
                          'value' => $e,
                          'hiddenField' => false,
                          'class' => 'custom-control-input',
                        ]) ?>
                        <label class="custom-control-label" for="<?= 'sug-extra-' . $i ?>">
                          <?= h($e) ?>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <small class="text-muted">Selecione os destinatários extras (não altera o campo “Para”).</small>
              <?php } else { ?>
                <div class="text-muted">Sem sugestões.</div>
              <?php } ?>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="font-weight-bold">Prévia</label>
              <div class="alert alert-light" style="border:1px solid #e5e7eb;">
                <div class="text-muted" style="font-size:12px;">O conteúdo será o mesmo padrão do sistema (ticket resolvido/cancelado/andamento).</div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex" style="gap:10px;">
          <?= $this->Form->button('Enviar e-mail', ['class' => 'btn btn-success']) ?>
          <?= $this->Html->link('Cancelar', ['action' => !empty($redirectAfter) ? 'edit' : 'finalizados', $ticketId], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<script>
  (function(){
    var countEl = document.getElementById('email-sugestoes-selected-count');
    var panelEl = document.getElementById('email-sugestoes-panel');
    var listEl = document.getElementById('email-sugestoes-list');
    var searchEl = document.getElementById('email-sugestoes-search');
    var btnAll = document.getElementById('email-sugestoes-select-all');
    var btnNone = document.getElementById('email-sugestoes-deselect-all');

    function updateCount(){
      if (!countEl) return;
      var checked = document.querySelectorAll('input[name="sugestoes[]"]:checked');
      var n = checked ? checked.length : 0;
      countEl.textContent = 'Destinatários extras (' + n + ')';
    }

    function openPanel(){
      if (panelEl) panelEl.style.display = 'block';
    }

    function closePanel(){
      if (panelEl) panelEl.style.display = 'none';
    }

    if (searchEl && panelEl) {
      searchEl.addEventListener('focus', openPanel);
      searchEl.addEventListener('click', openPanel);

      // fecha ao clicar fora (mantém estilo premium e UX consistente)
      document.addEventListener('click', function(ev){
        if (!panelEl) return;
        var t = ev.target;
        if (t === searchEl) return;
        if (panelEl.contains(t)) return;
        closePanel();
      });
    }

    if (searchEl && listEl) {
      searchEl.addEventListener('input', function(){
        var q = (searchEl.value || '').toLowerCase().trim();
        var items = listEl.querySelectorAll('.email-sugestoes-item');
        items.forEach(function(item){
          var email = (item.getAttribute('data-email') || '').toLowerCase();
          var ok = q === '' || email.indexOf(q) !== -1;
          item.style.display = ok ? '' : 'none';
        });
      });
    }

    if (btnAll) {
      btnAll.addEventListener('click', function(){
        document.querySelectorAll('input[name="sugestoes[]"]').forEach(function(cb){
          cb.checked = true;
        });
        updateCount();
      });
    }

    if (btnNone) {
      btnNone.addEventListener('click', function(){
        document.querySelectorAll('input[name="sugestoes[]"]').forEach(function(cb){
          cb.checked = false;
        });
        updateCount();
      });
    }

    document.querySelectorAll('input[name="sugestoes[]"]').forEach(function(cb){
      cb.addEventListener('change', updateCount);
    });

    updateCount();
  })();
</script>

