<?php
$this->assign('title', $title ?? 'Contrato');
$id = (int)$contract->id;
// Normalizar aliases EN (ex.: awaiting_signature) — sem isto o botão «Enviar assinatura» some na UI
$st = \App\Service\ContractLifecycleService::normalizeStatus($contract->status ?? 'rascunho');

// Status config: cor Bootstrap + rótulo (chaves em PT canónico)
$statusCfg = [
    'rascunho'              => ['default',  'Rascunho'],
    'revisao'               => ['info',     'Em Revisão'],
    'aguardando_assinatura' => ['primary',  'Aguard. Assinatura'],
    'ativo'                 => ['success',  'Ativo'],
    'a_vencer'              => ['warning',  'A Vencer'],
    'em_renovacao'          => ['warning',  'Em Renovação'],
    'suspenso'              => ['default',  'Suspenso'],    // CSS override aplica cor cinza
    'encerrado'             => ['default',  'Encerrado'],   // CSS override aplica cor cinza
    'cancelado'             => ['danger',   'Cancelado'],
    'recusado'              => ['danger',   'Recusado'],
    'assinatura_expirada'   => ['danger',   'Assin. Expirada'],
];
[$statusColor, $statusLabel] = $statusCfg[$st] ?? ['default', h((string)($contract->status ?? $st))];

// Quais ações fazem sentido por status (sempre com $st normalizado)
$podeEditar    = in_array($st, ['rascunho', 'revisao'], true);
$podeAprovar   = in_array($st, ['rascunho', 'revisao'], true);
$podeAssinar   = in_array($st, ['rascunho', 'revisao', 'aguardando_assinatura'], true);
$podeSuspender = in_array($st, ['ativo', 'a_vencer', 'em_renovacao', 'aguardando_assinatura'], true);
$podeCancelar  = !in_array($st, ['cancelado', 'encerrado'], true);
$podeRenovar   = in_array($st, ['ativo', 'a_vencer', 'em_renovacao', 'suspenso'], true);
?>

<div class="col-12 pgm-adv-page">
<?php $__cmSlaTab = !empty($contractSlaUiEnabled); ?>
<?php if ($__cmSlaTab): ?>
<ul class="nav nav-tabs adv-cm-view-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#cm-tab-ficha" aria-controls="cm-tab-ficha" role="tab" data-toggle="tab"><?= __('Ficha') ?></a></li>
	<li role="presentation"><a href="#cm-tab-sla" aria-controls="cm-tab-sla" role="tab" data-toggle="tab"><?= __('SLA & Service Desk') ?></a></li>
</ul>
<div class="tab-content adv-cm-tab-content">
<div role="tabpanel" class="tab-pane active" id="cm-tab-ficha">
<?php endif; ?>

	<?php /* ── CABEÇALHO ─────────────────────────────────────────── */ ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">

			<?php /* wizard */ ?>
			<?= $this->element('ContractManagement/wizard_steps', [
				'step' => 'ficha',
				'contractId' => $id,
				'podeEditarDadosPasso' => !empty($contractMayEditCore),
			]) ?>

			<?php /* título + badges */ ?>
			<div class="adv-cm-title-row">
				<div>
					<h4 class="card-title mb-1 adv-cm-contract-title"><?= h($contract->name) ?></h4>
					<div class="small text-muted">
						<span class="adv-cm-code-inline"><strong>Código:</strong> <?= h($contract->code) ?></span>
						<span class="label label-<?= $statusColor ?> adv-cm-status-lbl"><?= $statusLabel ?></span>
						<?php if ($contract->dias_para_vencer !== null && $contract->end_date): ?>
							<?php $d = (int)$contract->dias_para_vencer; ?>
							&nbsp;·&nbsp;
							<span class="<?= $d <= 30 && $d >= 0 ? 'text-warning' : ($d < 0 ? 'text-danger' : 'text-muted') ?>">
								<?= $d >= 0
									? __n('{0} dia até o vencimento', '{0} dias até o vencimento', $d, $d)
									: __n('{0} dia após o vencimento', '{0} dias após o vencimento', abs($d), abs($d)) ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
				<?= $this->Html->link('← Voltar à lista', ['action' => 'index'], ['class' => 'btn btn-sm btn-default adv-cm-back-link']) ?>
			</div>

			<hr class="adv-cm-hr">

			<?php /* ── BOTÕES DE AÇÃO ─── */ ?>
			<div class="adv-cm-actions-row">

				<?php /* Editar / Serviços / Signatários — sempre visíveis */ ?>
				<?php if (!empty($contractMayEditCore)): ?>
				<?= $this->Html->link('✏ Editar', ['action' => 'edit', $id], ['class' => 'btn btn-sm btn-default']) ?>
				<?php endif; ?>
				<?= $this->Html->link('📋 Serviços', ['action' => 'addServicos', $id], ['class' => 'btn btn-sm btn-default']) ?>
				<?php if ($podeAssinar): ?>
				<?= $this->Html->link('📨 Enviar assinatura', ['action' => 'enviarAssinatura', $id], ['class' => 'btn btn-sm btn-default']) ?>
				<?php endif; ?>
				<?= $this->Html->link('✍ Signatários', ['action' => 'addSignatarios', $id], ['class' => 'btn btn-sm btn-default']) ?>

				<?php /* PDF */ ?>
				<?= $this->Html->link('📄 Gerar PDF', '/modulo-contratos/gerar-pdf/' . $id, ['class' => 'btn btn-sm btn-default', 'target' => '_blank']) ?>

				<?php if (!empty($contract->pdf_path)): ?>
				<?= $this->Html->link('⬇ Download PDF', '/modulo-contratos/pdf/' . $id, ['class' => 'btn btn-sm btn-default']) ?>
				<?php endif; ?>

				<?php if (!empty($contract->signed_pdf_path)): ?>
				<?= $this->Html->link('✅ PDF Assinado', '/modulo-contratos/pdf-assinado/' . $id, ['class' => 'btn btn-sm btn-success']) ?>
				<?php endif; ?>

				<?php /* Regenerar PDF */ ?>
				<?= $this->Form->create(null, ['url' => ['action' => 'gerarPdf', $id], 'class' => 'adv-cm-form-inline']) ?>
				<?= $this->Form->button('🔄 Regenerar PDF', ['class' => 'btn btn-sm btn-default']) ?>
				<?= $this->Form->end() ?>

				<?php /* Aprovar → só quando rascunho ou revisão */ ?>
				<?php if ($podeAprovar): ?>
				<?= $this->Form->create(null, ['url' => ['action' => 'aprovar', $id], 'class' => 'adv-cm-form-inline']) ?>
				<?= $this->Form->hidden('target_status', ['value' => 'aguardando_assinatura']) ?>
				<?= $this->Form->button('✔ Aprovar', ['class' => 'btn btn-sm btn-success', 'title' => 'Mover para Aguardando Assinatura']) ?>
				<?= $this->Form->end() ?>
				<?php endif; ?>

				<?php /* Suspender */ ?>
				<?php if ($podeSuspender): ?>
				<?= $this->Form->postLink('⏸ Suspender', ['action' => 'suspender', $id], [
					'class'   => 'btn btn-sm btn-warning',
					'confirm' => 'Suspender este contrato?',
				]) ?>
				<?php endif; ?>

				<?php /* Renovação */ ?>
				<?php if ($podeRenovar): ?>
				<?= $this->Form->postLink('🔄 Solicitar renovação', ['action' => 'solicitarRenovacao', $id], [
					'class'   => 'btn btn-sm btn-default',
					'confirm' => 'Registrar pedido de renovação?',
				]) ?>
				<?php endif; ?>

			</div>

			<?php /* ── CANCELAR — formulário separado abaixo dos demais botões */ ?>
			<?php if ($podeCancelar): ?>
			<div class="adv-cm-cancel-zone">
				<?= $this->Form->create(null, ['url' => ['action' => 'cancelar', $id], 'class' => 'adv-cm-form-cancel']) ?>
				<?= $this->Form->control('motivo', [
					'type'        => 'text',
					'label'       => false,
					'placeholder' => 'Motivo do cancelamento (obrigatório)',
					'class'       => 'form-control input-sm adv-cm-motivo-input',
				]) ?>
				<?= $this->Form->button('🚫 Cancelar contrato', ['class' => 'btn btn-sm btn-danger']) ?>
				<?= $this->Form->end() ?>
			</div>
			<?php endif; ?>

			<hr class="adv-cm-hr adv-cm-hr--loose">

			<?php /* ── DADOS PRINCIPAIS ─── */ ?>
			<div class="row small">
				<div class="col-md-6">
					<table class="table table-condensed mb-0 adv-cm-kv-table">
						<tbody>
							<tr>
								<td class="text-muted adv-cm-kv-label">Cliente</td>
								<td><strong><?= h($contract->cliente->razaosocial ?? $contract->cliente->nome ?? '—') ?></strong></td>
							</tr>
							<tr>
								<td class="text-muted adv-cm-kv-label">Tipo de contrato</td>
								<td><?= h(ucfirst($contract->type ?? '—')) ?></td>
							</tr>
							<tr>
								<td class="text-muted adv-cm-kv-label">Vigência</td>
								<td>
									<?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '—') ?>
									→
									<?= h($contract->end_date   ? $contract->end_date->format('d/m/Y')   : 'Indeterminado') ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="col-md-6">
					<table class="table table-condensed mb-0 adv-cm-kv-table">
						<tbody>
							<tr>
								<td class="text-muted adv-cm-kv-label">Mensalidade</td>
								<td><strong><?= $contract->monthly_value ? 'R$ ' . number_format((float)$contract->monthly_value, 2, ',', '.') : '—' ?></strong></td>
							</tr>
							<tr>
								<td class="text-muted adv-cm-kv-label">SLA (h)</td>
								<td><?= h($contract->sla_hours ?? '—') ?></td>
							</tr>
							<tr>
								<td class="text-muted adv-cm-kv-label">Auto-renovar</td>
								<td><?= !empty($contract->auto_renew) ? '<span class="label label-info">Sim</span>' : 'Não' ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>

	<?php /* ── SERVIÇOS ───────────────────────────────────────────── */ ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<div class="adv-cm-card-head">
				<h5 class="card-title mb-0">Serviços contratados</h5>
				<?= $this->Html->link('+ Gerenciar', ['action' => 'addServicos', $id], ['class' => 'btn btn-xs btn-default']) ?>
			</div>
			<?php if (!empty($contract->contract_services)): ?>
			<?php
			$tipoLabel = ['servico'=>'Serviço','licenca'=>'Licença','hardware'=>'Hardware','cloud'=>'Cloud','suporte'=>'Suporte'];
			$totalMensal = 0;
			foreach ($contract->contract_services as $cs) { $totalMensal += (float)($cs->valor_total ?? 0); }
			?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Serviço</th>
							<th>Tipo</th>
							<th class="text-center">Horas/Qtde</th>
							<th class="text-center">Incluso</th>
							<th class="text-right">Vl. Unit.</th>
							<th class="text-right">Vl. Total</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contract->contract_services as $cs): ?>
						<tr>
							<td>
								<?= h($cs->service_name) ?>
								<?php if (!empty($cs->service_description)): ?>
								<br><small class="text-muted"><?= h($cs->service_description) ?></small>
								<?php endif; ?>
							</td>
							<td><span class="label label-info"><?= h($tipoLabel[$cs->tipo_item ?? ''] ?? ($cs->tipo_item ?? '—')) ?></span></td>
							<td class="text-center"><?= h($cs->max_hours ?? '—') ?> <?= h($cs->unidade ?? '') ?></td>
							<td class="text-center">
								<?= !empty($cs->is_included)
									? '<span class="label label-success">Sim</span>'
									: '<span class="label label-warning">Não</span>' ?>
							</td>
							<td class="text-right"><?= $cs->valor_unitario ? 'R$ ' . number_format((float)$cs->valor_unitario, 2, ',', '.') : '—' ?></td>
							<td class="text-right"><?= $cs->valor_total ? 'R$ ' . number_format((float)$cs->valor_total, 2, ',', '.') : '—' ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
					<?php if ($totalMensal > 0): ?>
					<tfoot>
						<tr>
							<td colspan="5" class="text-right"><strong>Total mensal</strong></td>
							<td class="text-right"><strong>R$ <?= number_format($totalMensal, 2, ',', '.') ?></strong></td>
						</tr>
					</tfoot>
					<?php endif; ?>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">Nenhum serviço adicionado.
				<?= $this->Html->link('Adicionar agora →', ['action' => 'addServicos', $id]) ?>
			</p>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ── SIGNATÁRIOS ─────────────────────────────────────────── */ ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<div class="adv-cm-card-head">
				<h5 class="card-title mb-0">Signatários</h5>
				<?= $this->Html->link('+ Gerenciar', ['action' => 'addSignatarios', $id], ['class' => 'btn btn-xs btn-default']) ?>
			</div>
			<?php
			$sigsOrdered = [];
			if (!empty($contract->contract_signatories)) {
				$sigsOrdered = collection($contract->contract_signatories)->sortBy('ordem', SORT_ASC)->toList();
			}
			?>
			<?php
			$podeReenviarEmailsMassa = false;
			foreach ($sigsOrdered as $sx) {
				if (!empty($sx->link_assinatura) && ($sx->status ?? '') !== 'assinado') {
					$podeReenviarEmailsMassa = true;
					break;
				}
			}
			?>
			<?php if ($sigsOrdered !== []): ?>
			<?php if ($podeReenviarEmailsMassa): ?>
			<p class="small mb-2">
				<?= $this->Form->postLink(
					__('Reenviar e-mail com link a todos (pendentes)'),
					['action' => 'reenviarLink', $id],
					[
						'class' => 'btn btn-xs btn-info',
						'confirm' => __('Reenviar o e-mail com link de assinatura para todos os signatários que ainda não assinaram?'),
					]
				) ?>
			</p>
			<?php endif; ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th class="adv-cm-th-index">#</th>
							<th>Nome</th>
							<th>E-mail</th>
							<th>Tipo</th>
							<th>Auth</th>
							<th>Status</th>
							<th>Assinado em</th>
							<th class="adv-cm-th-convite"><?= __('Convite') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($sigsOrdered as $s): ?>
						<?php
						$sigStatusCfg = [
							'pendente'    => 'default',
							'enviado'     => 'info',
							'visualizado' => 'warning',
							'assinado'    => 'success',
							'recusado'    => 'danger',
						];
						$sigColor = $sigStatusCfg[$s->status ?? ''] ?? 'default';
						$podeReenviarEste = !empty($s->link_assinatura) && ($s->status ?? '') !== 'assinado';
						?>
						<tr>
							<td class="text-center"><?= (int)$s->ordem ?></td>
							<td><?= h($s->nome) ?></td>
							<td><small><?= h($s->email) ?></small></td>
							<td><?= h(ucfirst($s->tipo ?? '—')) ?></td>
							<td><small><?= h(strtoupper($s->auth_type ?? 'email')) ?></small></td>
							<td><span class="label label-<?= $sigColor ?>"><?= h(ucfirst($s->status ?? 'pendente')) ?></span></td>
							<td class="small"><?= !empty($s->assinado_em) ? h($s->assinado_em->format('d/m/Y H:i')) : '—' ?></td>
							<td class="small">
								<?php if ($podeReenviarEste): ?>
								<?= $this->Form->create(null, ['url' => ['action' => 'reenviarLink', $id], 'class' => 'adv-cm-form-reenvio']) ?>
								<?= $this->Form->hidden('signatory_id', ['value' => (int)$s->id]) ?>
								<?= $this->Form->button(__('Reenviar link'), [
									'type' => 'submit',
									'class' => 'btn btn-xs btn-default',
									'escape' => false,
									'onclick' => 'return confirm(' . json_encode((string)__('Reenviar e-mail com link para {0}?', $s->nome)) . ');',
								]) ?>
								<?= $this->Form->end() ?>
								<?php else: ?>
								<span class="text-muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">
				Nenhum signatário. <strong>Adicione antes de enviar para assinatura.</strong>
				<?= $this->Html->link('Adicionar →', ['action' => 'addSignatarios', $id]) ?>
			</p>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ── RENOVAÇÕES ──────────────────────────────────────────── */ ?>
	<div class="pgm-adv-panel card mb-3" id="renovacoes">
		<div class="card-body">
			<h5 class="card-title mb-2">Renovações</h5>
			<?php if (!empty($contract->contract_renewals)): ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Status</th>
							<th>Nova vigência</th>
							<th>Solicitado em</th>
							<th>Aprovado por</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contract->contract_renewals as $ren): ?>
						<tr>
							<td><span class="label label-<?= $ren->status === 'aprovada' ? 'success' : ($ren->status === 'recusada' ? 'danger' : 'warning') ?>"><?= h(ucfirst($ren->status)) ?></span></td>
							<td class="small">
								<?= h($ren->nova_vigencia_inicio ? date('d/m/Y', strtotime($ren->nova_vigencia_inicio)) : '—') ?>
								→
								<?= h($ren->nova_vigencia_fim   ? date('d/m/Y', strtotime($ren->nova_vigencia_fim))   : 'Indef.') ?>
							</td>
							<td class="small"><?= h($ren->solicitado_em ? $ren->solicitado_em->format('d/m/Y H:i') : '') ?></td>
							<td class="small"><?= !empty($ren->aprovado_em) ? h($ren->aprovado_em->format('d/m/Y')) : '—' ?></td>
							<td class="text-right">
								<?php if ($ren->status === 'pendente'): ?>
								<?= $this->Html->link('Aprovar', ['action' => 'aprovarRenovacao', $ren->id], ['class' => 'btn btn-xs btn-success']) ?>
								<?= $this->Form->postLink('Recusar', ['action' => 'recusarRenovacao', $ren->id], ['class' => 'btn btn-xs btn-danger', 'confirm' => 'Recusar renovação?']) ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else: ?>
			<p class="text-muted small mb-0">Nenhuma renovação.</p>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ── FATURAS ─────────────────────────────────────────────── */ ?>
	<?php if (!empty($contract->invoices)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title mb-2">Faturas</h5>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Código</th>
							<th>Mês ref.</th>
							<th class="text-right">Total</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contract->invoices as $inv): ?>
						<?php
						$refM    = $inv->reference_month;
						$refMStr = $refM instanceof \DateTimeInterface ? $refM->format('m/Y') : (string)$refM;
						?>
						<tr>
							<td><?= h($inv->code) ?></td>
							<td><?= h($refMStr) ?></td>
							<td class="text-right"><?= $inv->total ? 'R$ ' . number_format((float)$inv->total, 2, ',', '.') : '—' ?></td>
							<td><span class="label label-<?= $inv->status === 'pago' ? 'success' : ($inv->status === 'cancelado' ? 'danger' : 'warning') ?>"><?= h(ucfirst($inv->status ?? '—')) ?></span></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>

<?php if ($__cmSlaTab): ?>
</div>
<div role="tabpanel" class="tab-pane" id="cm-tab-sla">
	<?= $this->element('ContractManagement/sla_service_desk_tab', [
		'contractId' => $id,
		'apiUrl' => $contractSlaApiUrl ?? '',
		'idcliente' => (int)($contract->idcliente ?? 0),
		'contractName' => (string)($contract->name ?? ''),
		'contractCode' => (string)($contract->code ?? ''),
	]) ?>
</div>
</div>
<?php endif; ?>

</div>
