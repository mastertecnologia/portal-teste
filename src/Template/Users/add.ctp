<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
if (!empty($fromQueues)) {
	$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Filas e técnicos', ['controller' => 'Queues', 'action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Técnicos e vínculos', ['controller' => 'Queues', 'action' => 'adminTechnicians'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Adicionar usuário', [], ['class' => 'breadcrumb-item active']);
} else {
	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Adicionar usuário', [], ['class' => 'breadcrumb-item active']);
}
$backIndexUrl = ['action' => 'index'];
if (!empty($fromQueues)) {
	$backIndexUrl['?'] = ['from' => 'queues'];
}
?>
<div class="col-md-12 col-lg-10 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Novo usuário da equipe</h1>
				<p class="queues-page-sub">Crie um usuário interno para a equipe PGM / Master. Defina login, senha e, se aplicável, nível e filas de atendimento.</p>
			</div>
			<div class="queues-page-actions">
				<?php if (!empty($fromQueues)) : ?>
					<?= $this->Html->link('<i class="fa fa-users"></i> Técnicos e filas', ['controller' => 'Queues', 'action' => 'adminTechnicians'], ['class' => 'queues-btn queues-btn--primary', 'escape' => false]) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fa fa-arrow-left"></i> Lista de usuários', $backIndexUrl, ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-form-panel queues-form-panel--wide">
			<?= $this->Form->create($user, ['class' => 'form-material m-t-10']) ?>
			<?php if (!empty($fromQueues)) : ?>
				<input type="hidden" name="from" value="queues">
			<?php endif; ?>

			<h6 class="text-muted m-t-0 m-b-10">Acesso</h6>
			<div class="row">
				<div class="col-lg-4 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Login</label>
						<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Usuário de acesso']) ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">CPF</label>
						<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Senha</label>
						<?= $this->Form->control('password', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Defina a senha de acesso']) ?>
					</div>
				</div>
				<div class="col-lg-6 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Confirmar senha</label>
						<?= $this->Form->control('confirm_password', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Repita a senha']) ?>
					</div>
				</div>
			</div>

			<h6 class="text-muted m-t-20 m-b-10">Identificação</h6>
			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<label class="control-label text-muted">Nome completo</label>
						<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome exibido em relatórios e assinaturas']) ?>
					</div>
				</div>
			</div>

			<?php if (!empty($supportLevelsList)) : ?>
				<h6 class="text-muted m-t-20 m-b-10">Nível de suporte</h6>
				<div class="row">
					<div class="col-md-8">
						<div class="form-group">
							<label class="control-label text-muted">Nível principal do técnico (N1 / N2 / N3)</label>
							<?= $this->Form->control('support_level_id', [
								'type' => 'select',
								'options' => $supportLevelsList,
								'empty' => '— Não definido —',
								'class' => 'form-control',
								'label' => false,
							]) ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if (!empty($queuesList)) : ?>
				<h6 class="text-muted m-t-20 m-b-10">Filas de atendimento</h6>
				<div class="row">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted">Filas em que este técnico pode atuar</label>
							<p class="small text-muted m-b-10">Empresa atual do login. Sem marcação = sem vínculo até editar depois.</p>
							<div class="queues-queues-box queues-checkboxes">
								<?php foreach ($queuesList as $qid => $qname) : $qid = (int)$qid; ?>
									<div class="custom-control custom-checkbox mb-1">
										<input type="checkbox" class="custom-control-input js-queue-cb" name="queue_ids[]" id="queue_cb_add_<?= $qid ?>" value="<?= $qid ?>">
										<label class="custom-control-label" for="queue_cb_add_<?= $qid ?>"><?= h($qname) ?></label>
									</div>
								<?php endforeach; ?>
							</div>
							<p class="small m-t-5 m-b-0">
								<button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="queuesMarcarTodasAdd">Marcar todas</button>
								<span class="text-muted">·</span>
								<button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="queuesDesmarcarTodasAdd">Desmarcar todas</button>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="queues-form-actions m-t-20">
				<?= $this->Form->button('Criar usuário', ['class' => 'queues-btn queues-btn--success']) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

<script>
	jQuery(function($) {
		$('#cpf').mask('999.999.999-99');
		$('#queuesMarcarTodasAdd').on('click', function() {
			$('.queues-checkboxes .js-queue-cb').prop('checked', true);
		});
		$('#queuesDesmarcarTodasAdd').on('click', function() {
			$('.queues-checkboxes .js-queue-cb').prop('checked', false);
		});
	});
</script>
