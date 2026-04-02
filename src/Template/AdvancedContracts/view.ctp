<?php
$this->assign('title', $title ?? 'Contrato');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($contract->name) ?></h4>
			<p class="small text-muted mb-2">Código <?= h($contract->code) ?> · <?= h($contract->status) ?></p>
			<dl class="row small mb-0">
				<dt class="col-sm-3">Vigência</dt>
				<dd class="col-sm-9"><?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?></dd>
				<dt class="col-sm-3">Mensalidade</dt>
				<dd class="col-sm-9"><?= h($contract->monthly_value) ?></dd>
				<dt class="col-sm-3">SLA (h)</dt>
				<dd class="col-sm-9"><?= h($contract->sla_hours) ?></dd>
				<?php if ($contract->get('valor_total') !== null && $contract->get('valor_total') !== ''): ?>
				<dt class="col-sm-3">Valor total</dt>
				<dd class="col-sm-9"><?= h($contract->valor_total) ?></dd>
				<?php endif; ?>
				<?php if (!empty($contract->nivel_sla)): ?>
				<dt class="col-sm-3">Nível SLA</dt>
				<dd class="col-sm-9"><?= h($contract->nivel_sla) ?></dd>
				<?php endif; ?>
				<dt class="col-sm-3">Versão</dt>
				<dd class="col-sm-9"><?= h((int)($contract->versao ?? 1)) ?></dd>
				<?php if (!empty($contract->parent_contract)): ?>
				<dt class="col-sm-3">Contrato pai</dt>
				<dd class="col-sm-9"><?= $this->Html->link(h($contract->parent_contract->code), ['action' => 'view', $contract->parent_contract->id]) ?></dd>
				<?php endif; ?>
			</dl>
			<?php if (!empty($contract->observacoes_cli)): ?>
			<p class="small mb-0 mt-2"><strong>Obs. cliente:</strong> <?= nl2br(h($contract->observacoes_cli)) ?></p>
			<?php endif; ?>
			<?php
			$cobr = [];
			if (!empty($contract->cobre_remoto)) {
				$cobr[] = 'remoto';
			}
			if (!empty($contract->cobre_presencial)) {
				$cobr[] = 'presencial';
			}
			if (!empty($contract->cobre_manutencao)) {
				$cobr[] = 'manutenção';
			}
			if (!empty($contract->cobre_backup)) {
				$cobr[] = 'backup';
			}
			if (!empty($contract->cobre_monitoramento)) {
				$cobr[] = 'monitoramento';
			}
			?>
			<?php if (!empty($cobr)): ?>
			<p class="small mb-0 mt-2 text-muted">Cobertura: <?= h(implode(', ', $cobr)) ?></p>
			<?php endif; ?>
			<?php if ($contract->get('limite_chamados') !== null && $contract->get('limite_chamados') !== ''): ?>
			<p class="small mb-0 mt-1 text-muted">Limite chamados: <?= h($contract->limite_chamados) ?></p>
			<?php endif; ?>
			<?php if (!empty($contract->autentique_doc_id) || !empty($contract->autentique_status)): ?>
			<p class="small mb-0 mt-2">
				<strong>Autentique:</strong>
				<?= h($contract->autentique_status ?: '—') ?>
				<?php if (!empty($contract->autentique_doc_id)): ?>
					· doc <?= h($contract->autentique_doc_id) ?>
				<?php endif; ?>
			</p>
			<?php endif; ?>
			<?= $this->Html->link('Gerar PDF', '/modulo-avancado/contratos/export-pdf/' . (int)$contract->id, ['class' => 'btn btn-sm btn-primary mt-2 mr-1', 'target' => '_blank']) ?>
			<?php if (!empty($contract->pdf_path)): ?>
			<span class="small text-muted d-block mt-1">Último ficheiro: <?= h(basename((string)$contract->pdf_path)) ?></span>
			<?php endif; ?>
			<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary mt-2']) ?>
		</div>
	</div>
	<?php if (!empty($contract->contract_services)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Serviços</h5>
			<ul class="mb-0">
				<?php foreach ($contract->contract_services as $s): ?>
				<li><?= h($s->service_name) ?><?= !empty($s->is_included) ? ' <span class="badge badge-info">incluso</span>' : '' ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_documents)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Documentos</h5>
			<ul class="mb-0">
				<?php foreach ($contract->contract_documents as $d): ?>
				<li><?= h($d->title) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_template)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Modelo</h5>
			<p class="small mb-0"><?= h($contract->contract_template->nome) ?> <span class="text-muted">(v<?= (int)$contract->contract_template->versao ?>)</span></p>
		</div>
	</div>
	<?php endif; ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Signatários</h5>
			<?php if (!empty($contract->contract_signatories)): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th>Nome</th><th>E-mail</th><th>Status</th><th>Ordem</th></tr></thead>
					<tbody>
						<?php foreach ($contract->contract_signatories as $sig): ?>
						<tr>
							<td><?= h($sig->nome) ?></td>
							<td><?= h($sig->email) ?></td>
							<td><?= h($sig->status) ?></td>
							<td><?= (int)$sig->ordem ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">Nenhum signatário cadastrado.</p>
			<?php endif; ?>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Renovações</h5>
			<?php if (!empty($contract->contract_renewals)): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th>Status</th><th>Solicitado</th><th>Nova vigência</th><th>Valor mensal</th></tr></thead>
					<tbody>
						<?php foreach ($contract->contract_renewals as $ren): ?>
						<tr>
							<td><?= h($ren->status) ?></td>
							<td class="small"><?= h($ren->solicitado_em ? $ren->solicitado_em->format('d/m/Y H:i') : '') ?></td>
							<td class="small">
								<?= h($ren->nova_vigencia_inicio ? $ren->nova_vigencia_inicio->format('d/m/Y') : '—') ?>
								—
								<?= h($ren->nova_vigencia_fim ? $ren->nova_vigencia_fim->format('d/m/Y') : '—') ?>
							</td>
							<td><?= h($ren->novo_valor_mensal) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">Nenhuma renovação registada.</p>
			<?php endif; ?>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Notificações</h5>
			<?php if (!empty($contract->contract_notifications)): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th>Tipo</th><th>Canal</th><th>Enviado</th><th>Quando</th></tr></thead>
					<tbody>
						<?php foreach ($contract->contract_notifications as $n): ?>
						<tr>
							<td><?= h($n->tipo) ?></td>
							<td><?= h($n->canal) ?></td>
							<td><?= !empty($n->enviado) ? 'sim' : 'não' ?></td>
							<td class="small"><?= h($n->enviado_em ? $n->enviado_em->format('d/m/Y H:i') : '—') ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">Nenhuma notificação registada.</p>
			<?php endif; ?>
		</div>
	</div>
</div>
