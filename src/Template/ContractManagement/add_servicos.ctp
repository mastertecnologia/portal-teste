<?php
$this->assign('title', $title ?? 'Serviços');
$svc        = $service;
$services   = $contract->contract_services ?? [];
$contractId = (int)$contract->id;

$tipoOpts = [
    'servico'  => 'Serviço',
    'licenca'  => 'Licença',
    'hardware' => 'Hardware',
    'cloud'    => 'Cloud',
    'suporte'  => 'Suporte',
];
$unidOpts = [
    'unid' => 'Unidade',
    'h'    => 'Hora',
    'mes'  => 'Mês',
    'gb'   => 'GB',
    'item' => 'Item',
];
?>
<div class="col-12 pgm-adv-page">
<div class="pgm-adv-panel card mb-3">
<div class="card-body">

	<h4 class="card-title mb-1"><?= h($title) ?> — <span class="text-muted"><?= h($contract->code) ?></span></h4>

	<?= $this->element('ContractManagement/wizard_steps', [
		'step' => 'servicos',
		'contractId' => $contractId,
		'podeEditarDadosPasso' => !empty($contractMayEditCore),
	]) ?>

	<div class="mb-3" style="display:flex;flex-wrap:wrap;gap:6px;">
		<?= $this->Html->link('Continuar para signatários', ['action' => 'addSignatarios', $contractId], ['class' => 'btn btn-sm btn-primary']) ?>
		<?= $this->Html->link('Ir para ficha do contrato',  ['action' => 'view',           $contractId], ['class' => 'btn btn-sm btn-default']) ?>
		<?php if (!empty($contractMayEditCore)): ?>
		<?= $this->Html->link('Ajustar dados', ['action' => 'edit', $contractId], ['class' => 'btn btn-sm btn-link']) ?>
		<?php endif; ?>
	</div>

	<?php /* ── LISTA DE SERVIÇOS CADASTRADOS ─── */ ?>
	<?php if (!empty($services)): ?>
	<?php
	$totalMensal = 0;
	foreach ($services as $cs) { $totalMensal += (float)($cs->valor_total ?? 0); }
	?>
	<div class="table-responsive mb-3">
		<table class="table table-sm table-striped table-bordered mb-0">
			<thead>
				<tr>
					<th>Serviço</th>
					<th>Tipo</th>
					<th class="text-center">Horas/Qtde</th>
					<th class="text-center">Incluso</th>
					<th class="text-right">Vl. Unit.</th>
					<th class="text-right">Vl. Total</th>
					<th class="text-center" style="width:50px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($services as $cs): ?>
				<tr>
					<td>
						<?= h($cs->service_name) ?>
						<?php if (!empty($cs->service_description)): ?>
						<br><small class="text-muted"><?= h($cs->service_description) ?></small>
						<?php endif; ?>
					</td>
					<td><span class="label label-default"><?= h($tipoOpts[$cs->tipo_item ?? ''] ?? ($cs->tipo_item ?? '—')) ?></span></td>
					<td class="text-center"><?= h($cs->max_hours ?? '—') ?> <?= h($cs->unidade ?? '') ?></td>
					<td class="text-center">
						<?= !empty($cs->is_included)
							? '<span class="label label-success">Sim</span>'
							: '<span class="label label-default">Não</span>' ?>
					</td>
					<td class="text-right"><?= $cs->valor_unitario ? 'R$ ' . number_format((float)$cs->valor_unitario, 2, ',', '.') : '—' ?></td>
					<td class="text-right"><?= $cs->valor_total    ? 'R$ ' . number_format((float)$cs->valor_total,    2, ',', '.') : '—' ?></td>
					<td class="text-center">
						<?= $this->Form->postLink('✕', '/modulo-contratos/servicos/delete/' . (int)$cs->id . '/' . $contractId, [
							'class'   => 'btn btn-xs btn-danger',
							'title'   => 'Remover',
							'confirm' => 'Remover ' . h($cs->service_name) . '?',
						]) ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<?php if ($totalMensal > 0): ?>
			<tfoot>
				<tr>
					<td colspan="5" class="text-right"><strong>Total</strong></td>
					<td class="text-right"><strong>R$ <?= number_format($totalMensal, 2, ',', '.') ?></strong></td>
					<td></td>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
	</div>
	<?php endif; ?>

	<hr>
	<h5>Adicionar linha</h5>

	<?= $this->Form->create($svc, ['novalidate' => true]) ?>
	<div class="row">
		<div class="col-md-8">
			<div class="form-group">
				<label class="control-label">Serviço</label>
				<?= $this->Form->text('service_name', ['class' => 'form-control input-sm', 'placeholder' => 'Nome do serviço']) ?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
				<label class="control-label">Tipo item</label>
				<?= $this->Form->select('tipo_item', $tipoOpts, ['class' => 'form-control input-sm', 'value' => 'servico']) ?>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label">Descrição</label>
		<?= $this->Form->textarea('service_description', ['class' => 'form-control input-sm', 'rows' => 2, 'placeholder' => 'Opcional']) ?>
	</div>

	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">Horas / quantidade</label>
				<?= $this->Form->number('max_hours', ['class' => 'form-control input-sm', 'id' => 'svc_max_hours', 'value' => 0, 'step' => '0.5']) ?>
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label class="control-label">Unidade</label>
				<?= $this->Form->select('unidade', $unidOpts, ['class' => 'form-control input-sm', 'value' => 'unid']) ?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">Valor unitário</label>
				<div class="input-group input-group-sm">
					<span class="input-group-addon">R$</span>
					<?= $this->Form->number('valor_unitario', ['class' => 'form-control', 'id' => 'svc_vl_unit', 'value' => '0.00', 'step' => '0.01']) ?>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">Valor total linha</label>
				<div class="input-group input-group-sm">
					<span class="input-group-addon">R$</span>
					<?= $this->Form->number('valor_total', ['class' => 'form-control', 'id' => 'svc_vl_total', 'value' => '0.00', 'step' => '0.01', 'readonly' => true]) ?>
				</div>
			</div>
		</div>
		<div class="col-md-1 d-flex align-items-end">
			<div class="form-group">
				<label class="control-label">&nbsp;</label>
			</div>
		</div>
	</div>

	<div class="form-group">
		<div class="checkbox">
			<label>
				<?= $this->Form->checkbox('is_included') ?>
				Incluso na franquia
			</label>
		</div>
	</div>

	<?= $this->Form->button('Adicionar', ['class' => 'btn btn-primary btn-sm']) ?>
	<?= $this->Form->end() ?>

</div>
</div>
</div>

<script>
(function () {
	var qtde = document.getElementById('svc_max_hours');
	var unit = document.getElementById('svc_vl_unit');
	var tot  = document.getElementById('svc_vl_total');
	function calc() {
		var q = parseFloat(qtde.value) || 0;
		var u = parseFloat(unit.value) || 0;
		tot.value = (q * u).toFixed(2);
	}
	if (qtde && unit && tot) {
		qtde.addEventListener('input', calc);
		unit.addEventListener('input', calc);
	}
})();
</script>
