<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Adicionar usuário', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.padding-20 {
		padding-left: 20px;
		padding-right: 20px;
	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h5 class="card-title m-b-10">Novo usuário da equipe</h5>
			<p class="text-muted m-b-20">Crie um usuário interno para a equipe PGM / Master.</p>

			<?= $this->Form->create($user, ['class' => 'form-material m-t-10']) ?>
				<div class="row padding-20">
					<div class="col-lg-4 col-md-6">
						<div class="form-group">
							<label class="control-label text-muted">Login</label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Usuário de acesso']) ?>
						</div>
					</div>
					<div class="col-lg-4 col-md-6">
						<div class="form-group">
							<label class="control-label text-muted">CPF</label>
							<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false,'placeholder' => '000.000.000-00']) ?>
						</div>
					</div>
				</div>
				<div class="row padding-20">
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
				<div class="row padding-20">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted">Nome completo</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome exibido em relatórios e assinaturas']) ?>
						</div>
					</div>
				</div>
				<?php if (!empty($supportLevelsList)) : ?>
				<div class="row padding-20">
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
				<div class="row padding-20">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted">Filas de Atendimento</label>
							<p class="small text-muted m-b-10">Marque as filas em que este técnico pode atuar (empresa atual do login). Sem marcação = sem vínculo até editar depois.</p>
							<div class="border rounded p-3 bg-light queues-checkboxes" style="max-height: 14rem; overflow-y: auto;">
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
				<div class="row padding-20">
					<div class="col-lg-12 col-md-12">
						<?= $this->Form->button('Criar usuário', ['class' => 'btn waves-effect waves-light btn-success']) ?>
						<?= $this->Html->link('Voltar para os usuários', ['action' => 'index'], ['class' => 'btn btn-secondary m-l-10']) ?>
					</div>
				</div>
				<div class="clearfix m-b-10"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
		$('#queuesMarcarTodasAdd').on('click', function () {
			$('.queues-checkboxes .js-queue-cb').prop('checked', true);
		});
		$('#queuesDesmarcarTodasAdd').on('click', function () {
			$('.queues-checkboxes .js-queue-cb').prop('checked', false);
		});
	});
</script>
