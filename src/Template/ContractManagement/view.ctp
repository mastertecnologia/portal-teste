<?php
$this->assign('title', $title ?? 'Contrato');
$id = (int)$contract->id;
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($contract->name) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', ['step' => 'ficha', 'contractId' => $id]) ?>
			<p class="small text-muted mb-2"><?= __('Código') ?> <?= h($contract->code) ?> ·
				<span class="label label-default" title="<?= h($contract->status) ?>"><?= h($contract->status_label) ?></span>
				<?php if ($contract->dias_para_vencer !== null && $contract->end_date): ?>
					<?php $d = (int)$contract->dias_para_vencer; ?>
					· <span class="text-muted"><?= $d >= 0
						? __n('{0} dia até o fim da vigência', '{0} dias até o fim da vigência', $d, $d)
						: __n('{0} dia após o fim da vigência', '{0} dias após o fim da vigência', abs($d), abs($d)); ?></span>
				<?php endif; ?>
			</p>
			<div class="btn-group-vertical btn-group-sm d-block mb-3">
				<?= $this->Html->link(__('Editar'), ['action' => 'edit', $id], ['class' => 'btn btn-default']) ?>
				<?= $this->Html->link(__('Serviços'), ['action' => 'addServicos', $id], ['class' => 'btn btn-default']) ?>
				<?= $this->Html->link(__('Signatários'), ['action' => 'addSignatarios', $id], ['class' => 'btn btn-default']) ?>
				<?= $this->Html->link(__('Gerar PDF (abrir)'), '/modulo-contratos/gerar-pdf/' . $id, ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
				<?php if (!empty($contract->pdf_path)): ?>
				<?= $this->Html->link(__('Download PDF'), '/modulo-contratos/pdf/' . $id, ['class' => 'btn btn-default']) ?>
				<?php endif; ?>
				<?php if (!empty($contract->signed_pdf_path)): ?>
				<?= $this->Html->link(__('PDF assinado'), '/modulo-contratos/pdf-assinado/' . $id, ['class' => 'btn btn-default']) ?>
				<?php endif; ?>
				<?= $this->Html->link(__('Enviar assinatura'), ['action' => 'enviarAssinatura', $id], ['class' => 'btn btn-info']) ?>
			</div>
			<?= $this->Form->create(null, ['url' => ['action' => 'gerarPdf', $id], 'style' => 'display:inline']) ?>
			<?= $this->Form->button(__('Regenerar PDF (gravar)'), ['class' => 'btn btn-sm btn-default']) ?>
			<?= $this->Form->end() ?>

			<?= $this->Form->create(null, ['url' => ['action' => 'aprovar', $id], 'style' => 'display:inline;margin-left:8px']) ?>
			<?= $this->Form->hidden('target_status', ['value' => 'aguardando_assinatura']) ?>
			<?= $this->Form->button(__('Aprovar (→ aguardando assinatura)'), ['class' => 'btn btn-sm btn-success']) ?>
			<?= $this->Form->end() ?>

			<?= $this->Form->postLink(__('Suspender'), ['action' => 'suspender', $id], ['class' => 'btn btn-sm btn-warning', 'confirm' => __('Suspender este contrato?')]) ?>

			<?= $this->Form->create(null, ['url' => ['action' => 'cancelar', $id], 'style' => 'display:inline']) ?>
			<?= $this->Form->control('motivo', ['type' => 'text', 'label' => false, 'placeholder' => __('Motivo cancelamento'), 'class' => 'form-control input-sm', 'style' => 'display:inline;width:200px;']) ?>
			<?= $this->Form->button(__('Cancelar contrato'), ['class' => 'btn btn-sm btn-danger']) ?>
			<?= $this->Form->end() ?>

			<?= $this->Form->postLink(__('Solicitar renovação'), ['action' => 'solicitarRenovacao', $id], ['class' => 'btn btn-sm btn-default', 'confirm' => __('Registrar pedido de renovação?')]) ?>

			<hr>
			<dl class="row small mb-0">
				<dt class="col-sm-3"><?= __('Vigência') ?></dt>
				<dd class="col-sm-9"><?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?></dd>
				<dt class="col-sm-3"><?= __('Mensalidade') ?></dt>
				<dd class="col-sm-9"><?= h($contract->monthly_value) ?></dd>
				<dt class="col-sm-3"><?= __('SLA (h)') ?></dt>
				<dd class="col-sm-9"><?= h($contract->sla_hours) ?></dd>
			</dl>
			<?= $this->Html->link(__('Voltar à lista'), ['action' => 'index'], ['class' => 'btn btn-sm btn-default mt-2']) ?>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center mb-2">
				<h5 class="card-title mb-0"><?= __('Signatários') ?></h5>
				<?= $this->Html->link(__('Gerir'), ['action' => 'addSignatarios', $id], ['class' => 'btn btn-xs btn-default']) ?>
			</div>
			<?php
			$sigsOrdered = [];
			if (!empty($contract->contract_signatories)) {
				$sigsOrdered = collection($contract->contract_signatories)->sortBy('ordem', SORT_ASC)->toList();
			}
			?>
			<?php if ($sigsOrdered !== []): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th><?= __('Ordem') ?></th>
							<th><?= __('Nome') ?></th>
							<th><?= __('E-mail') ?></th>
							<th><?= __('Tipo') ?></th>
							<th><?= __('Status') ?></th>
							<th><?= __('Assinado') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($sigsOrdered as $s): ?>
						<tr>
							<td><?= (int)$s->ordem ?></td>
							<td><?= h($s->nome) ?></td>
							<td><?= h($s->email) ?></td>
							<td><?= h($s->tipo) ?></td>
							<td><?= h($s->status ?? '—') ?></td>
							<td class="small"><?= !empty($s->assinado_em) ? h($s->assinado_em->format('d/m/Y H:i')) : '—' ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0"><?= __('Nenhum signatário. Adicione antes de enviar para assinatura.') ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php if (!empty($contract->contract_services)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title"><?= __('Serviços') ?></h5>
			<ul class="mb-0">
				<?php foreach ($contract->contract_services as $s): ?>
				<li><?= h($s->service_name) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
	<div class="pgm-adv-panel card mb-3" id="renovacoes">
		<div class="card-body">
			<h5 class="card-title"><?= __('Renovações') ?></h5>
			<?php if (!empty($contract->contract_renewals)): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th><?= __('Status') ?></th><th><?= __('Solicitado') ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ($contract->contract_renewals as $ren): ?>
						<tr>
							<td><?= h($ren->status) ?></td>
							<td class="small"><?= h($ren->solicitado_em ? $ren->solicitado_em->format('d/m/Y H:i') : '') ?></td>
							<td>
								<?php if ($ren->status === 'pendente'): ?>
								<?= $this->Html->link(__('Aprovar'), ['action' => 'aprovarRenovacao', $ren->id], ['class' => 'btn btn-xs btn-success']) ?>
								<?= $this->Form->postLink(__('Recusar'), ['action' => 'recusarRenovacao', $ren->id], ['class' => 'btn btn-xs btn-danger', 'confirm' => __('Recusar renovação?')]) ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0"><?= __('Nenhuma renovação.') ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php if (!empty($contract->invoices)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title"><?= __('Faturas (módulo avançado)') ?></h5>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th><?= __('Código') ?></th><th><?= __('Mês') ?></th><th><?= __('Total') ?></th><th><?= __('Status') ?></th></tr></thead>
					<tbody>
						<?php foreach ($contract->invoices as $inv): ?>
						<?php
							$refM = $inv->reference_month;
							$refMStr = $refM instanceof \DateTimeInterface ? $refM->format('Y-m') : (string)$refM;
						?>
						<tr>
							<td><?= h($inv->code) ?></td>
							<td><?= h($refMStr) ?></td>
							<td><?= h($inv->total) ?></td>
							<td><?= h($inv->status) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
