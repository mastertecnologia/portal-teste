<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ordensservico[] $ordens
 */
require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

$this->Html->css('/dist/css/pages/prefaturamento-shell.css', ['block' => true]);

$pfBool = static function ($reg, $prop) {
	return !empty($reg->{$prop});
};
?>
<div class="col-md-12 pgm-prefaturamento-shell">
	<div class="card">
		<div class="card-body">
			<p class="pf-intro m-b-15">
				Ordens de serviço em <strong><?= h(strip_tags((string)SituacaoOrdem(C_OrdensSituacaoLiberadaParaFaturamento))) ?></strong>.
				Clique em <strong>Gerar Faturamento</strong> para criar o documento de faturamento a partir da OS.
			</p>
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-material m-b-20']); ?>
			<div class="row">
				<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
					<p class="pf-field-label m-t-10">Cliente:</p>
				</div>
				<div class="col-md-5 col-xs-12 p-l-0 float-right">
					<?= $this->Form->control('cliente', [
						'data-live-search' => true,
						'title' => 'Todos',
						'value' => $cliente,
						'class' => 'form-control selectpicker',
						'id' => 'pf-cliente',
						'options' => $clientesOpt1,
						'label' => false,
					]) ?>
				</div>
				<div class="col-md-3 col-xs-12 m-t-5">
					<?= $this->Form->button('Filtrar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
				</div>
			</div>
			<?= $this->Form->end(); ?>

			<div class="m-b-10">
				<button type="button" class="btn btn-sm btn-pgm btn-pgm-salvar" id="pf-batch-faturamento" disabled title="Selecione OS do mesmo cliente">
					<i class="fas fa-file-alt"></i> Gerar Faturamento (selecionadas)
				</button>
			</div>

			<div class="table-responsive">
				<table class="table table-hover" id="table-prefaturamento">
					<thead>
						<tr>
							<th class="pf-th-select"><span class="sr-only">Selecionar</span></th>
							<th>OS</th>
							<th>Cliente</th>
							<th>Técnico</th>
							<th>Abertura</th>
							<th>Valor</th>
							<th colspan="3">Conferências</th>
							<th class="text-right">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($ordens)) : ?>
							<tr>
								<td colspan="10" class="text-center text-muted">Nenhuma OS nesta fila.</td>
							</tr>
						<?php else : ?>
							<?php foreach ($ordens as $reg) :
								$nomeCli = '—';
								if (!empty($reg->cliente)) {
									$nomeCli = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
								}
								?>
								<tr>
									<td>
										<input type="checkbox" class="pf-os-chk" data-idcliente="<?= (int)$reg->idcliente ?>" data-idos="<?= (int)$reg->id ?>" title="Selecionar para lote" />
									</td>
									<td>
										<?= $this->Html->link(
											(string)$reg->id,
											['controller' => 'Ordensservico', 'action' => 'edit', $reg->id],
											['class' => 'link', 'target' => '_blank', 'rel' => 'noopener noreferrer']
										) ?>
									</td>
									<td><?= h($nomeCli) ?></td>
									<td><?= h(!empty($reg->user) ? $reg->user->name : '—') ?></td>
									<td data-order="<?= $reg->dataabertura ? date_format($reg->dataabertura, 'Ymd') : '' ?>">
										<?= $reg->dataabertura ? h(date_format($reg->dataabertura, 'd/m/Y')) : '—' ?>
									</td>
									<td><?= $reg->valortotal !== null ? h(number_format((float)$reg->valortotal, 2, ',', '.')) : '—' ?></td>
									<td colspan="3" class="pf-td-conferencias">
										<?= $this->Form->create(null, ['url' => ['action' => 'conferencia'], 'class' => 'form-inline d-flex flex-wrap align-items-center']); ?>
										<?= $this->Form->hidden('idordem', ['value' => $reg->id]); ?>
										<label class="small m-r-10 m-b-0"><?= $this->Form->checkbox('prefat_conf_exec', ['value' => '1', 'checked' => $pfBool($reg, 'prefat_conf_exec')]) ?> Exec.</label>
										<label class="small m-r-10 m-b-0"><?= $this->Form->checkbox('prefat_conf_comercial', ['value' => '1', 'checked' => $pfBool($reg, 'prefat_conf_comercial')]) ?> Com.</label>
										<label class="small m-r-10 m-b-0"><?= $this->Form->checkbox('prefat_conf_fiscal', ['value' => '1', 'checked' => $pfBool($reg, 'prefat_conf_fiscal')]) ?> Fiscal</label>
										<?= $this->Form->button('Salvar', ['class' => 'btn btn-xs btn-secondary m-l-5']); ?>
										<?= $this->Form->end(); ?>
									</td>
									<td class="text-right">
										<?= $this->Html->link(
											'<i class="fas fa-file-alt"></i> Gerar Faturamento',
											['controller' => 'Faturamento', 'action' => 'gerarDeOS', $reg->id],
											['class' => 'btn btn-sm btn-pgm btn-pgm-salvar', 'escape' => false, 'title' => 'Criar documento de faturamento para esta OS']
										) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
(function ($) {
	function refreshBatch() {
		var n = $('.pf-os-chk:checked').length;
		$('#pf-batch-faturamento').prop('disabled', n < 1);
	}
	$(document).on('change', '.pf-os-chk', refreshBatch);

	// Batch: se uma OS → redireciona direto; se múltiplas do mesmo cliente → abre a primeira e avisa
	$('#pf-batch-faturamento').on('click', function () {
		var $c = $('.pf-os-chk:checked');
		if (!$c.length) return;
		var cid = $c.first().data('idcliente');
		var ids = [];
		var ok = true;
		$c.each(function () {
			if (String($(this).data('idcliente')) !== String(cid)) ok = false;
			ids.push($(this).data('idos'));
		});
		if (!ok) {
			alert('Selecione apenas OS do mesmo cliente para gerar faturamento em lote.');
			return;
		}
		// Abre uma aba de gerarDeOS para cada OS selecionada
		ids.forEach(function (idos) {
			var u = <?= json_encode($this->Url->build(['controller' => 'Faturamento', 'action' => 'gerarDeOS', ''], false), JSON_UNESCAPED_SLASHES) ?> + idos;
			window.open(u, '_blank', 'noopener,noreferrer');
		});
	});
})(jQuery);
</script>
