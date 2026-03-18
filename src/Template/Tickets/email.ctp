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
              <?php if (!empty($sugestoes)) { ?>
                <?php $btnId = 'email-sugestoes-dd'; ?>
                <div class="dropdown">
                  <button
                    class="btn btn-outline-secondary dropdown-toggle btn-block"
                    type="button"
                    id="<?= $btnId ?>"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    Destinatários extras (0)
                  </button>
                  <div
                    class="dropdown-menu"
                    style="width: 100%; max-height: 240px; overflow:auto; padding: 12px;"
                    aria-labelledby="<?= $btnId ?>"
                  >
                    <?php foreach (($sugestoes ?? []) as $i => $e): ?>
                      <div class="custom-control custom-checkbox m-b-10">
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
    var btn = document.getElementById('<?= $btnId ?>');
    if (!btn) return;

    function updateBtn(){
      var checked = document.querySelectorAll('input[name="sugestoes[]"]:checked');
      var n = checked ? checked.length : 0;
      btn.textContent = 'Destinatários extras (' + n + ')';
    }

    // Não fechar o dropdown ao clicar no checkbox
    var menu = btn.parentElement ? btn.parentElement.querySelector('.dropdown-menu') : null;
    if (menu) {
      menu.querySelectorAll('input[type="checkbox"]').forEach(function(cb){
        cb.addEventListener('click', function(e){ e.stopPropagation(); });
      });
    }

    document.querySelectorAll('input[name="sugestoes[]"]').forEach(function(cb){
      cb.addEventListener('change', updateBtn);
    });

    updateBtn();
  })();
</script>

