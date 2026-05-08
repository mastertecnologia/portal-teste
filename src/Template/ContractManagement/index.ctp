<?php
$this->assign('title', $title ?? 'Contratos');
$params = $this->Paginator->params();
$count = isset($params['count']) ? (int)$params['count'] : (is_countable($contracts) ? count($contracts) : 0);
$kpis = $kpis ?? ['ativos' => 0, 'a_vencer' => 0, 'aguardando_assinatura' => 0, 'em_renovacao' => 0, 'valor_mensal_total' => 0];
?>
<style>
.pgm-adv-page .btn-default, .pgm-adv-page a.btn-default { background-color:#546e7a!important;border-color:#546e7a!important;color:#fff!important; }
.pgm-adv-page .btn-default:hover, .pgm-adv-page a.btn-default:hover { background-color:#607d8b!important;border-color:#607d8b!important;color:#fff!important; }
.pgm-adv-page .label-default, .pgm-adv-page span.label-default { background-color:#546e7a!important;color:#fff!important; }
.pgm-adv-page .well { background-color:rgba(255,255,255,.05)!important;border-color:rgba(255,255,255,.1)!important;color:inherit!important; }
.pgm-contract-status-badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;line-height:1.3; }
.pgm-contract-status-cancelado { background-color:#F51000;color:#fff; }
.pgm-contract-status-ativo { background-color:#4BF56C;color:#1f2937; }
.pgm-contract-status-assinado { background-color:#7E1EE5;color:#fff; }
.pgm-contract-status-aguardando-assinatura { background-color:#FF9B2A;color:#1f2937; }
</style>
<div class="col-12 pgm-adv-page">
	<div class="row mb-3">
		<div class="col-md-3 col-sm-6 mb-2"><div class="well well-sm"><small class="text-muted">Ativos</small><div class="h4 mb-0"><?= (int)$kpis['ativos'] ?></div></div></div>
		<div class="col-md-3 col-sm-6 mb-2"><div class="well well-sm"><small class="text-muted">A vencer</small><div class="h4 mb-0"><?= (int)$kpis['a_vencer'] ?></div></div></div>
		<div class="col-md-3 col-sm-6 mb-2"><div class="well well-sm"><small class="text-muted">Aguard. assinatura</small><div class="h4 mb-0"><?= (int)$kpis['aguardando_assinatura'] ?></div></div></div>
		<div class="col-md-3 col-sm-6 mb-2"><div class="well well-sm"><small class="text-muted">Receita mensal (ativos + a vencer)</small><div class="h4 mb-0">R$ <?= number_format((float)$kpis['valor_mensal_total'], 2, ',', '.') ?></div></div></div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="text-muted small mb-2">Em renovação: <strong><?= (int)$kpis['em_renovacao'] ?></strong></p>
			<?php
			$statusOpts = ['' => __('Todos os status')];
			$__lbl = \App\Model\Entity\Contract::statusLabelMap();
			foreach ([
				'rascunho',
				'revisao',
				'aguardando_assinatura',
				'ativo',
				'a_vencer',
				'em_renovacao',
				'suspenso',
				'encerrado',
				'cancelado',
				'recusado',
				'assinatura_expirada',
			] as $__st) {
				$statusOpts[$__st] = $__lbl[$__st] ?? $__st;
			}
			$stF = $statusFilter ?? '';
			$cidF = (int)($idclienteFilter ?? 0);
			$clF = $clientesList ?? [];
			?>
			<?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'index'], 'class' => 'form-inline mb-3']) ?>
			<?= $this->Form->control('status', [
				'type' => 'select',
				'options' => $statusOpts,
				'value' => $stF,
				'label' => false,
				'class' => 'form-control input-sm pgm-adv-contract-filter-status',
				'empty' => false,
			]) ?>
			<?= $this->Form->control('idcliente', [
				'type' => 'select',
				'options' => $clF,
				'value' => $cidF > 0 ? $cidF : '',
				'empty' => __('Todos os clientes'),
				'label' => false,
				'class' => 'form-control input-sm pgm-adv-contract-filter-cliente',
			]) ?>
			<?= $this->Form->button(__('Filtrar'), ['class' => 'btn btn-sm btn-default pgm-adv-contract-filter-mb']) ?>
			<?php if ($stF !== '' || $cidF > 0): ?>
			<?= $this->Html->link(__('Limpar'), ['action' => 'index'], ['class' => 'btn btn-sm btn-link pgm-adv-contract-filter-mb']) ?>
			<?php endif; ?>
			<?= $this->Form->end() ?>
			<p class="mb-3">
				<?= $this->Html->link(__('Novo contrato'), ['action' => 'add'], ['class' => 'btn btn-sm btn-success']) ?>
				<?= $this->Html->link(__('Modelos de contrato'), '/contract-templates', ['class' => 'btn btn-sm btn-default']) ?>
				<?= $this->Html->link(__('Exportar CSV'), '/modulo-contratos/exportar', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
			</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th><?= __('Código') ?></th>
							<th><?= __('Nome') ?></th>
							<th><?= __('Cliente') ?></th>
							<th><?= __('Status') ?></th>
							<th><?= __('Vigência / fim') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contracts as $c): ?>
						<?php
						$statusNorm = strtolower(trim((string)$c->status));
						$statusClass = '';
						$canDelete = in_array($statusNorm, ['rascunho', 'cancelado'], true);
						if (in_array($statusNorm, ['cancelado'], true)) {
							$statusClass = 'pgm-contract-status-cancelado';
						} elseif (in_array($statusNorm, ['ativo', 'active'], true)) {
							$statusClass = 'pgm-contract-status-ativo';
						} elseif (in_array($statusNorm, ['assinado'], true)) {
							$statusClass = 'pgm-contract-status-assinado';
						} elseif (in_array($statusNorm, ['aguardando_assinatura', 'awaiting_signature'], true)) {
							$statusClass = 'pgm-contract-status-aguardando-assinatura';
						}
						?>
						<tr>
							<td><?= h($c->code) ?></td>
							<td><?= h($c->name) ?></td>
							<td><?= !empty($c->cliente) ? h($c->cliente->razaosocial ?: $c->cliente->nome) : '—' ?></td>
							<td><span class="pgm-contract-status-badge <?= h($statusClass) ?>" title="<?= h($c->status) ?>"><?= h($c->status_label) ?></span></td>
							<td>
								<?= h($c->start_date ? $c->start_date->format('d/m/Y') : '') ?> — <?= h($c->end_date ? $c->end_date->format('d/m/Y') : '') ?>
								<?php if ($c->dias_para_vencer !== null && $c->end_date): ?>
									<br><span class="text-muted small"><?php
									$d = (int)$c->dias_para_vencer;
									echo $d >= 0
										? __n('{0} dia até o fim', '{0} dias até o fim', $d, $d)
										: __n('{0} dia após o fim', '{0} dias após o fim', abs($d), abs($d));
									?></span>
								<?php endif; ?>
							</td>
							<td>
								<?= $this->Html->link(__('Ver'), ['action' => 'view', $c->id], ['class' => 'btn btn-xs btn-outline-primary']) ?>
								<?php if ($canDelete): ?>
									<?= $this->Form->create(null, [
										'url' => ['action' => 'delete', $c->id],
										'class' => 'd-inline-block js-contract-delete-form',
										'id' => 'delete-contract-form-' . (int)$c->id,
									]) ?>
									<?= $this->Form->hidden('motivo', ['value' => '', 'class' => 'js-contract-delete-motivo']) ?>
									<button
										type="button"
										class="btn btn-xs btn-outline-danger js-contract-delete-btn"
										data-form-id="delete-contract-form-<?= (int)$c->id ?>"
										data-contract-name="<?= h($c->name) ?>"
									><?= __('Excluir') ?></button>
									<?= $this->Form->end() ?>
								<?php else: ?>
									<span
										class="text-muted small"
										title="<?= h(__('Exclusão disponível apenas para contratos em rascunho ou cancelados.')) ?>"
									>
										<?= __('Sem exclusão') ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if ($count === 0): ?>
						<tr><td colspan="6" class="text-muted"><?= __('Nenhum contrato.') ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php if (!empty($params['pageCount']) && (int)$params['pageCount'] > 1): ?>
			<nav class="mt-2"><?= $this->Paginator->numbers() ?></nav>
			<?php endif; ?>
		</div>
	</div>
</div>
<script>
document.addEventListener('click', function (event) {
	var button = event.target.closest('.js-contract-delete-btn');
	if (!button) {
		return;
	}
	var formId = button.getAttribute('data-form-id');
	if (!formId) {
		return;
	}
	var form = document.getElementById(formId);
	if (!form) {
		return;
	}
	var contractName = button.getAttribute('data-contract-name') || '';
	var motivo = window.prompt('Informe o motivo da exclusao do contrato "' + contractName + '":', '');
	if (motivo === null) {
		return;
	}
	motivo = motivo.trim();
	if (!motivo) {
		window.alert('Motivo obrigatorio para excluir.');
		return;
	}
	var input = form.querySelector('.js-contract-delete-motivo');
	if (!input) {
		return;
	}
	input.value = motivo;
	form.submit();
});
</script>
