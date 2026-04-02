<?php
$this->assign('title', $title ?? 'Signatários');
$sig        = $signatory;
$sigs       = $contract->contract_signatories ?? [];
$contractId = (int)$contract->id;

$tipoOpts = [
    'cliente'       => 'Cliente',
    'empresa'       => 'Empresa / Contratante',
    'testemunha'    => 'Testemunha',
    'fiador'        => 'Fiador',
    'representante' => 'Representante Legal',
];
$authOpts = [
    'email'    => 'E-mail',
    'sms'      => 'SMS',
    'whatsapp' => 'WhatsApp',
    'pix'      => 'Pix (CPF/CNPJ)',
    'icp'      => 'Certificado ICP-Brasil',
];
$actionOpts = [
    'SIGN'    => 'Assinar',
    'APPROVE' => 'Aprovar',
    'WITNESS' => 'Testemunhar',
];
?>
<div class="col-12 pgm-adv-page">
<div class="pgm-adv-panel card mb-3">
<div class="card-body">

	<h4 class="card-title mb-1"><?= h($title) ?> — <span class="text-muted"><?= h($contract->code) ?></span></h4>

	<?= $this->element('ContractManagement/wizard_steps', [
		'step'                 => 'signatarios',
		'contractId'           => $contractId,
		'podeEditarDadosPasso' => !empty($contractMayEditCore),
	]) ?>

	<div class="mb-3" style="display:flex;flex-wrap:wrap;gap:6px;">
		<?= $this->Html->link('Ir para ficha (PDF / assinatura)', ['action' => 'view',        $contractId], ['class' => 'btn btn-sm btn-primary']) ?>
		<?= $this->Html->link('Voltar a serviços',                ['action' => 'addServicos', $contractId], ['class' => 'btn btn-sm btn-default']) ?>
	</div>

	<?php /* ── LISTA DE SIGNATÁRIOS CADASTRADOS ─── */ ?>
	<?php if (!empty($sigs)): ?>
	<?php
	usort($sigs, function ($a, $b) {
		return ((int)($a->ordem ?? 0)) <=> ((int)($b->ordem ?? 0));
	});
	$sigStatusCfg = [
		'pendente'    => 'default',
		'enviado'     => 'info',
		'visualizado' => 'warning',
		'assinado'    => 'success',
		'recusado'    => 'danger',
	];
	?>
	<div class="table-responsive mb-3">
		<table class="table table-sm table-striped table-bordered mb-0">
			<thead>
				<tr>
					<th style="width:40px;">#</th>
					<th>Nome</th>
					<th>E-mail</th>
					<th>Tipo</th>
					<th>Auth</th>
					<th>Ação</th>
					<th>Status</th>
					<th style="width:50px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($sigs as $s): ?>
				<?php $sigColor = $sigStatusCfg[$s->status ?? ''] ?? 'default'; ?>
				<tr>
					<td class="text-center"><?= (int)$s->ordem ?></td>
					<td><?= h($s->nome) ?></td>
					<td><small><?= h($s->email) ?></small></td>
					<td><span class="label label-info"><?= h($tipoOpts[$s->tipo ?? ''] ?? ($s->tipo ?? '—')) ?></span></td>
					<td><small><?= h(strtoupper($s->auth_type ?? 'email')) ?></small></td>
					<td><small><?= h($actionOpts[$s->action_type ?? ''] ?? ($s->action_type ?? '—')) ?></small></td>
					<td><span class="label label-<?= $sigColor ?>"><?= h(ucfirst($s->status ?? 'pendente')) ?></span></td>
					<td class="text-center">
						<?= $this->Form->postLink('✕', '/modulo-contratos/signatarios/delete/' . (int)$s->id . '/' . $contractId, [
							'class'   => 'btn btn-xs btn-danger',
							'title'   => 'Remover',
							'confirm' => 'Remover ' . h($s->nome) . '?',
						]) ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<hr>
	<h5>Adicionar signatário</h5>

	<?= $this->Form->create($sig, ['novalidate' => true]) ?>

	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">Nome completo</label>
				<?= $this->Form->text('nome', ['class' => 'form-control input-sm', 'placeholder' => 'Nome do signatário']) ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">E-mail</label>
				<?= $this->Form->email('email', ['class' => 'form-control input-sm', 'placeholder' => 'email@exemplo.com']) ?>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">CPF</label>
				<?= $this->Form->text('cpf', ['class' => 'form-control input-sm', 'placeholder' => '000.000.000-00']) ?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">Tipo</label>
				<?= $this->Form->select('tipo', $tipoOpts, ['class' => 'form-control input-sm', 'value' => 'cliente']) ?>
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label class="control-label">Ordem</label>
				<?= $this->Form->number('ordem', ['class' => 'form-control input-sm', 'value' => max(1, count($sigs) + 1), 'min' => 1]) ?>
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label class="control-label">Autenticação</label>
				<?= $this->Form->select('auth_type', $authOpts, ['class' => 'form-control input-sm', 'value' => 'email']) ?>
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label class="control-label">Ação</label>
				<?= $this->Form->select('action_type', $actionOpts, ['class' => 'form-control input-sm', 'value' => 'SIGN']) ?>
			</div>
		</div>
	</div>

	<?= $this->Form->button('Adicionar', ['class' => 'btn btn-primary btn-sm']) ?>
	<?= $this->Form->end() ?>

</div>
</div>
</div>
