<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= h(__('Solicitar licença')) ?></h1>
<div class="card">
	<?= $this->Form->create(null, ['url' => ['action' => 'salvarSolicitacao']]) ?>
	<div class="g2">
		<div class="field"><label><?= h(__('Produto / software')) ?> *</label><?= $this->Form->text('produto', ['required' => true]) ?></div>
		<div class="field"><label><?= h(__('Quantidade de assentos')) ?></label><?= $this->Form->number('assentos', ['value' => 1, 'min' => 1]) ?></div>
		<div class="field" style="grid-column:1/-1;"><label><?= h(__('Observações')) ?></label><?= $this->Form->textarea('observacao', ['rows' => 3]) ?></div>
	</div>
	<button type="submit" class="btn btn-primary btn-sm" style="margin-top:14px;"><?= h(__('Enviar solicitação')) ?></button>
	<?= $this->Form->end() ?>
</div>
