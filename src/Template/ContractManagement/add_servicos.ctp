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
?>

<div class="col-12 pgm-adv-page">
<div class="pgm-adv-panel card mb-3">
<div class="card-body">

	<h4 class="card-title mb-1"><?= h($title) ?> — <span class="text-muted"><?= h($contract->code) ?></span></h4>

	<?= $this->element('ContractManagement/wizard_steps', ['step' => 'servicos', 'contractId' => $contractId]) ?>

	<div class="mb-3">
		<?= $this->Html->link('Continuar para signatários', ['action' => 'addSignatarios', $contractId], ['class' => 'btn btn-sm btn-primary']) ?>
		<?= $this->Html->link('Ir para ficha do contrato', ['action' => 'view', $contractId], ['class' => 'btn btn-sm btn-default']) ?>
		<?= $this->Html->link('Ajustar dados', ['action' => 'edit', $contractId], ['class' => 'btn btn-sm btn-link']) ?>
	</div>

	<?php if (!empty($services)): ?>
	<div class="table-responsive mb-3">
		<table class="table table-sm table-striped table-bordered mb-0">
			<thead>
				<tr>
					<th>Serviço</th>
					<th>Tipo</th>
					<th>Horas / Qtde</th>
					<th>Unidade</th>
					<th class="text-right">Vl. Unitário</th>
					<th class="text-right">Vl. Total</th>
					<th class="text-center">Incluso</th>
					<th class="text-center" style="width:60px;">Ação</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$totalGeral = 0;
				foreach ($services as $s):
					$totalGeral += (float)($s->valor_total ?? 0);
				?>
				<tr>
					<td>
						<strong><?= h($s->service_name) ?></strong>
						<?php if (!empty($s->service_description)): ?>
						<br><small class="text-muted"><?= h($s->service_description) ?></small>
						<?php endif; ?>
					</td>
					<td><span class="label label-default"><?= h($tipoOpts[$s->tipo_item ?? ''] ?? ($s->tipo_item ?? '—')) ?></span></td>
					<td><?= h($s->max_hours ?? '—') ?></td>
					<td><?= h($s->unidade ?? 'unid') ?></td>
					<td class="text-right"><?= !empty($s->valor_unitario) ? 'R$ ' . number_format((float)$s->valor_unitario, 2, ',', '.') : '—' ?></td>
					<td class="text-right"><?= !empty($s->valor_total) ? 'R$ ' . number_format((float)$s->valor_total, 2, ',', '.') : '—' ?></td>
					<td class="text-center">
						<?= !empty($s->is_included)
							? '<span class="label label-success">Sim</span>'
							: '<span class="label label-default">Não</span>' ?>
					</td>
					<td class="text-center">
						<?= $this->Form->postLink(
							'<i class="fa fa-trash"></i>',
							'/modulo-contratos/servicos/delete/' . (int)$s->id . '/' . $contractId,
							[
								'class'   => 'btn btn-xs btn-danger',
								'escape'  => false,
								'confirm' => 'Remover "' . h($s->service_name) . '"?',
							]
						) ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<?php if ($totalGeral > 0): ?>
			<tfoot>
				<tr>
					<td colspan="5" class="text-right"><strong>Total mensal</strong></td>
					<td class="text-right"><strong>R$ <?= number_format($totalGeral, 2, ',', '.') ?></strong></td>
					<td colspan="2"></td>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
	</div>
	<?php else: ?>
	<div class="alert alert-info mb-3" style="padding:10px 14px;">
		<i class="fa fa-info-circle"></i> Nenhum serviço adicionado ainda. Use o formulário abaixo.
	</div>
	<?php endif; ?>

	<div class="panel panel-default">
		<div class="panel-heading" style="font-size:13px;font-weight:600;">
			<i class="fa fa-plus-circle"></i> Adicionar serviço
		</div>
		<div class="panel-body">
			<?= $this->Form->create($svc, ['novalidate' => true]) ?>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group<?= $svc->hasErrors() && $svc->getError('service_name') ? ' has-error' : '' ?>">
						<label class="control-label">Serviço <span class="text-danger">*</span></label>
						<?= $this->Form->control('service_name', [
							'label'       => false,
							'class'       => 'form-control input-sm',
							'placeholder' => 'Ex: Suporte Remoto N1',
							'required'    => true,
						]) ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">Tipo</label>
						<?= $this->Form->control('tipo_item', [
							'type'    => 'select',
							'options' => $tipoOpts,
							'label'   => false,
							'class'   => 'form-control input-sm',
							'empty'   => false,
						]) ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">Unidade</label>
						<?= $this->Form->control('unidade', [
							'label'       => false,
							'class'       => 'form-control input-sm',
							'placeholder' => 'Ex: h, unid, GB',
							'value'       => $svc->unidade ?? 'unid',
						]) ?>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label class="control-label">Descrição <small class="text-muted">(opcional)</small></label>
						<?= $this->Form->control('service_description', [
							'type'        => 'textarea',
							'label'       => false,
							'class'       => 'form-control input-sm',
							'rows'        => 2,
							'placeholder' => 'Detalhe o serviço se necessário',
						]) ?>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">Horas / Quantidade</label>
						<?= $this->Form->control('max_hours', [
							'type'        => 'number',
							'label'       => false,
							'class'       => 'form-control input-sm',
							'placeholder' => '0',
							'step'        => '0.5',
							'id'          => 'svc-qty',
							'value'       => $svc->max_hours ?? '',
						]) ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">Valor unitário (R$)</label>
						<?= $this->Form->control('valor_unitario', [
							'type'        => 'text',
							'label'       => false,
							'class'       => 'form-control input-sm money-field',
							'placeholder' => '0,00',
							'id'          => 'svc-vunit',
							'value'       => !empty($svc->valor_unitario) ? number_format((float)$svc->valor_unitario, 2, ',', '.') : '',
						]) ?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">Valor total linha (R$)</label>
						<?= $this->Form->control('valor_total', [
							'type'        => 'text',
							'label'       => false,
							'class'       => 'form-control input-sm money-field',
							'placeholder' => '0,00',
							'id'          => 'svc-vtotal',
							'value'       => !empty($svc->valor_total) ? number_format((float)$svc->valor_total, 2, ',', '.') : '',
						]) ?>
						<small class="text-muted">Calculado automaticamente ao preencher Horas e Vl. Unitário</small>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label class="control-label">&nbsp;</label>
						<div class="checkbox" style="margin-top:4px;">
							<label>
								<?= $this->Form->control('is_included', [
									'type'  => 'checkbox',
									'label' => false,
									'id'    => 'svc-incluso',
								]) ?>
								Incluso na franquia
							</label>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<?= $this->Form->button('<i class="fa fa-plus"></i> Adicionar serviço', [
						'class'  => 'btn btn-sm btn-success',
						'escape' => false,
					]) ?>
					<a href="<?= $this->Url->build(['action' => 'view', $contractId]) ?>" class="btn btn-sm btn-link">Cancelar</a>
				</div>
			</div>

			<?= $this->Form->end() ?>
		</div>
	</div>

</div>
</div>
</div>

<script>
(function () {
    function parseMoney(v) {
        if (!v) return 0;
        // aceita "1.500,00" ou "1500.00"
        var s = String(v).replace(/\./g, '').replace(',', '.');
        return parseFloat(s) || 0;
    }

    function formatMoney(n) {
        return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function calcTotal() {
        var qty   = parseFloat(document.getElementById('svc-qty').value)   || 0;
        var vunit = parseMoney(document.getElementById('svc-vunit').value);
        if (qty > 0 && vunit > 0) {
            document.getElementById('svc-vtotal').value = formatMoney(qty * vunit);
        }
    }

    document.getElementById('svc-qty').addEventListener('input', calcTotal);
    document.getElementById('svc-vunit').addEventListener('blur', calcTotal);
})();
</script>
