<?php
$this->assign('title', $title ?? 'Contratos');
$params = $this->Paginator->params();
$count = isset($params['count']) ? (int)$params['count'] : (is_countable($contracts) ? count($contracts) : 0);
$kpis = $kpis ?? ['ativos' => 0, 'a_vencer' => 0, 'aguardando_assinatura' => 0, 'em_renovacao' => 0, 'valor_mensal_total' => 0];
?>
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
			foreach (\App\Model\Table\ContractsTable::allowedStatusValues() as $__st) {
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
				'class' => 'form-control input-sm',
				'style' => 'min-width:200px;margin-right:8px;margin-bottom:8px;',
				'empty' => false,
			]) ?>
			<?= $this->Form->control('idcliente', [
				'type' => 'select',
				'options' => $clF,
				'value' => $cidF > 0 ? $cidF : '',
				'empty' => __('Todos os clientes'),
				'label' => false,
				'class' => 'form-control input-sm',
				'style' => 'min-width:220px;margin-right:8px;margin-bottom:8px;',
			]) ?>
			<?= $this->Form->button(__('Filtrar'), ['class' => 'btn btn-sm btn-default', 'style' => 'margin-bottom:8px;']) ?>
			<?php if ($stF !== '' || $cidF > 0): ?>
			<?= $this->Html->link(__('Limpar'), ['action' => 'index'], ['class' => 'btn btn-sm btn-link', 'style' => 'margin-bottom:8px;']) ?>
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
						<tr>
							<td><?= h($c->code) ?></td>
							<td><?= h($c->name) ?></td>
							<td><?= !empty($c->cliente) ? h($c->cliente->razaosocial ?: $c->cliente->nome) : '—' ?></td>
							<td><span title="<?= h($c->status) ?>"><?= h($c->status_label) ?></span></td>
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
							<td><?= $this->Html->link(__('Ver'), ['action' => 'view', $c->id], ['class' => 'btn btn-xs btn-outline-primary']) ?></td>
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
