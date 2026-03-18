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
                'value' => !empty($sugestoes[0]) ? $sugestoes[0] : '',
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
              <select class="form-control" id="email-select-sugestao">
                <option value="">Selecionar…</option>
                <?php foreach (($sugestoes ?? []) as $e): ?>
                  <option value="<?= h($e) ?>"><?= h($e) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Selecionar preenche o campo “Para”.</small>
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
    var sel = document.getElementById('email-select-sugestao');
    var inp = document.getElementById('email-para');
    if (sel && inp) {
      sel.addEventListener('change', function(){
        var v = (sel.value || '').trim();
        if (!v) return;

        var cur = (inp.value || '').trim();
        if (!cur) {
          inp.value = v;
        } else {
          // Normaliza separadores e evita duplicados
          var list = cur
            .replace(/,/g, ';')
            .split(';')
            .map(function(x){ return (x || '').trim(); })
            .filter(Boolean);

          var lower = list.map(function(x){ return x.toLowerCase(); });
          if (lower.indexOf(v.toLowerCase()) === -1) {
            list.push(v);
          }
          inp.value = list.join(';');
        }

        // mantém cursor no fim e volta o select para "Selecionar…"
        try {
          inp.focus();
          inp.selectionStart = inp.selectionEnd = inp.value.length;
        } catch (e) {}
        sel.value = '';
      });
    }
  })();
</script>

