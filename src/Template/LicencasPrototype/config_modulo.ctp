<?php /** @var array<string,mixed> $licConfig */ $cfg = (array)($licConfig ?? []); ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;">⚙ <?= h(__('Configurações do módulo')) ?></h1>
<div class="card">
	<?= $this->Form->create(null, ['url' => ['action' => 'salvarConfig']]) ?>
	<div class="g2">
		<div class="field"><label><?= h(__('Alerta de vencimento (dias)')) ?></label>
			<?= $this->Form->number('alerta_vencimento_dias', ['value' => (int)($cfg['alerta_vencimento_dias'] ?? 30), 'min' => 1, 'max' => 365]) ?>
		</div>
		<div class="field"><label><?= h(__('E-mail para alertas')) ?></label>
			<?= $this->Form->email('notificar_email', ['value' => $cfg['notificar_email'] ?? '']) ?>
		</div>
		<div class="field" style="grid-column:1/-1;">
			<label><?= $this->Form->checkbox('cofre_exige_aprovacao', ['checked' => !empty($cfg['cofre_exige_aprovacao'])]) ?> <?= h(__('Exigir aprovação antes de revelar itens do cofre (fase futura)')) ?></label>
		</div>
	</div>
	<button type="submit" class="btn btn-primary btn-sm" style="margin-top:14px;"><?= h(__('Salvar')) ?></button>
	<?= $this->Form->end() ?>
</div>
