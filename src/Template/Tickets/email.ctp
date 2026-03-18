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
                <select class="form-control" id="email-sugestao-add">
                  <option value="">Selecionar destinatário…</option>
                  <?php foreach (($sugestoes ?? []) as $e): ?>
                    <option value="<?= h($e) ?>"><?= h($e) ?></option>
                  <?php endforeach; ?>
                </select>

                <div id="email-sugestoes-selecionadas" class="m-t-10"></div>

                <small class="text-muted">
                  Selecione os destinatários extras (não altera o campo “Para”). Você pode adicionar vários.
                </small>
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
    var addSel = document.getElementById('email-sugestao-add');
    var box = document.getElementById('email-sugestoes-selecionadas');
    if (!addSel || !box) return;

    function normList(list){
      var out = [];
      var seen = {};
      for (var i=0;i<list.length;i++){
        var v = (list[i] || '').trim();
        if (!v) continue;
        var k = v.toLowerCase();
        if (seen[k]) continue;
        seen[k] = true;
        out.push(v);
      }
      return out;
    }

    function currentValues(){
      var inputs = box.querySelectorAll('input[name="sugestoes[]"]');
      var vals = [];
      for (var i=0;i<inputs.length;i++) vals.push(inputs[i].value || '');
      return normList(vals);
    }

    function render(vals){
      vals = normList(vals || []);
      var html = '';
      if (!vals.length) {
        box.innerHTML = '<div class="text-muted" style="font-size:12px;">Nenhum destinatário selecionado.</div>';
        return;
      }
      for (var i=0;i<vals.length;i++){
        var v = vals[i];
        html += '' +
          '<div class="d-flex align-items-center justify-content-between border rounded p-5 m-b-5" style="gap:10px;background:#fff;">' +
            '<div style="font-size:12px;word-break:break-word;">' + v.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>' +
            '<button type="button" class="btn btn-outline-danger btn-sm email-sug-remove" data-email="' + v.replace(/"/g,'&quot;') + '">Remover</button>' +
            '<input type="hidden" name="sugestoes[]" value="' + v.replace(/"/g,'&quot;') + '" />' +
          '</div>';
      }
      box.innerHTML = html;
    }

    addSel.addEventListener('change', function(){
      var v = (addSel.value || '').trim();
      if (!v) return;
      var vals = currentValues();
      vals.push(v);
      render(vals);
      addSel.value = '';
    });

    box.addEventListener('click', function(e){
      var btn = e.target && e.target.closest ? e.target.closest('.email-sug-remove') : null;
      if (!btn) return;
      var email = (btn.getAttribute('data-email') || '').trim();
      var vals = currentValues().filter(function(x){ return x.toLowerCase() !== email.toLowerCase(); });
      render(vals);
    });

    // estado inicial
    render(currentValues());
  })();
</script>

