<?php
use Cake\Routing\Router;
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
if (!empty($fromQueues)) {
	$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Filas e técnicos', ['controller' => 'Queues', 'action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Técnicos e vínculos', ['controller' => 'Queues', 'action' => 'adminTechnicians'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar usuário', [], ['class' => 'breadcrumb-item active']);
} else {
	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
}
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Editar usuário da equipe</h1>
				<p class="queues-page-sub">
					<strong><?= h($user->name ?: $user->username) ?></strong>
					<?php if (!empty($user->email)) : ?> · <?= h($user->email) ?><?php endif; ?>
					— Dados cadastrais, acesso e filas de atendimento (quando aplicável).
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if (!empty($fromQueues)) : ?>
					<?= $this->Html->link('<i class="fa fa-users"></i> Técnicos e filas', ['controller' => 'Queues', 'action' => 'adminTechnicians'], ['class' => 'queues-btn queues-btn--primary', 'escape' => false]) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fa fa-list"></i> Lista de usuários', ['action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-form-panel queues-form-panel--wide">
			<?= $this->Form->create($user, ['class' => 'form-material m-t-10', 'enctype' => 'multipart/form-data', 'type' => 'file']) ?>
			<?php if (!empty($fromQueues)) : ?>
				<input type="hidden" name="from" value="queues">
			<?php endif; ?>

			<h6 class="text-muted m-t-0 m-b-10">Dados básicos</h6>
			<div class="row">
				<div class="col-lg-3 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Usuário</label>
						<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Usuário de acesso']) ?>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">CPF</label>
						<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-8">
					<div class="form-group">
						<label class="control-label text-muted">E-mail</label>
						<?= $this->Form->control('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'nome@empresa.com']) ?>
					</div>
				</div>
				<div class="col-lg-2 col-md-4 col-sm-12">
					<div class="custom-control custom-checkbox m-t-30">
						<?= $this->Form->checkbox('inativo', ['class' => 'custom-control-input', 'id' => 'inativo']) ?>
						<label class="custom-control-label text-muted" for="inativo">Inativo</label>
						<small class="form-text text-muted m-t-5">Usuário sem acesso ao portal.</small>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<label class="control-label text-muted">Nome completo</label>
						<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome exibido em relatórios e assinaturas']) ?>
					</div>
				</div>
			</div>

			<?php if (!empty($supportLevelsList) && (int)$user->role === 0) : ?>
			<h6 class="text-muted m-t-20 m-b-10">Nível de suporte</h6>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Nível principal do técnico (N1 / N2 / N3)</label>
						<?= $this->Form->control('support_level_id', [
							'type' => 'select',
							'options' => $supportLevelsList,
							'empty' => '— Não definido —',
							'class' => 'form-control',
							'label' => false,
						]) ?>
						<small class="form-text text-muted">Usado nas regras de escalonamento e compatibilidade com a fila. Ao salvar, o vínculo em cada fila herda este nível, salvo override opcional abaixo.</small>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if (!empty($queuesList) && (int)$user->role === 0) : ?>
			<h6 class="text-muted m-t-20 m-b-10">Filas de atendimento</h6>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label class="control-label text-muted">Filas em que este técnico pode atuar (empresa atual)</label>
						<p class="small text-muted m-b-10">
							<strong>Importante:</strong> o vínculo vale para <em>este usuário</em> (quem faz login no Service Desk). Para assumir um ticket na fila N2, ele precisa estar marcado na fila N2 <strong>e</strong> ter nível de suporte compatível (N2 ou acima) acima.
							<?= $this->Html->link('Lista de todos os técnicos e filas', ['controller' => 'Queues', 'action' => 'adminTechnicians'], ['class' => 'd-block m-t-5']) ?>
						</p>
						<div class="queues-queues-box queues-checkboxes">
							<?php foreach ($queuesList as $qid => $qname) :
								$qid = (int)$qid;
								$selQ = array_map('intval', $selectedQueues ?? []);
								$qChecked = in_array($qid, $selQ, true);
								?>
								<div class="custom-control custom-checkbox mb-1">
									<input type="checkbox" class="custom-control-input js-queue-cb" name="queue_ids[]" id="queue_cb_<?= $qid ?>" value="<?= $qid ?>" <?= $qChecked ? 'checked' : '' ?>>
									<label class="custom-control-label" for="queue_cb_<?= $qid ?>"><?= h($qname) ?></label>
								</div>
							<?php endforeach; ?>
						</div>
						<p class="small m-t-5 m-b-0">
							<button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="queuesMarcarTodas" aria-label="Marcar todas as filas">Marcar todas</button>
							<span class="text-muted">·</span>
							<button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="queuesDesmarcarTodas" aria-label="Desmarcar todas as filas">Desmarcar todas</button>
						</p>
						<?php if (!empty($showQueueLevelOverrides) && !empty($selectedQueues)) : ?>
							<p class="text-muted m-t-10 m-b-5"><small><strong>Nível na fila (opcional)</strong> — por fila selecionada; vazio = usa o nível principal acima.</small></p>
							<div class="row">
								<?php foreach ($queuesList as $qid => $qname) :
									$qid = (int)$qid;
									if (!in_array($qid, array_map('intval', $selectedQueues ?? []), true)) {
										continue;
									}
									$selSl = isset($queuesUserSupportLevels[$qid]) ? (int)$queuesUserSupportLevels[$qid] : 0;
									?>
									<div class="col-md-6 m-b-10">
										<label class="control-label text-muted small"><?= h($qname) ?></label>
										<select name="queue_support_level[<?= $qid ?>]" class="form-control form-control-sm">
											<option value="">— Herda nível principal —</option>
											<?php foreach ($supportLevelsList as $sid => $sln) : ?>
												<option value="<?= (int)$sid ?>" <?= $selSl === (int)$sid ? 'selected' : '' ?>><?= h($sln) ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<small class="form-text text-muted">Marque ou desmarque cada fila; use o seletor Master/PGM no topo para a empresa correta. APIs: <code>queues/api-ensure-defaults</code>, <code>queues/api-save</code> (JSON).</small>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<h6 class="text-muted m-t-20 m-b-10">Assinaturas de e-mail</h6>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Assinatura Master</label>
						<?= $this->Form->control('assinaturamaster', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Cole aqui o link da assinatura Master']) ?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Assinatura PGM</label>
						<?= $this->Form->control('assinaturapgm', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Cole aqui o link da assinatura PGM']) ?>
					</div>
				</div>
			</div>

			<div class="row m-t-20">
				<div class="col-md-12 queues-form-actions">
					<?= $this->Form->button('Salvar usuário', ['class' => 'queues-btn queues-btn--success', 'id' => 'btnSalvarUsuario']) ?>
					<?= $this->Form->end(); ?>
					<?= $this->Html->link('Alterar senha', ['action' => 'changePassword', $user->id], ['class' => 'queues-btn queues-btn--warning']) ?>
					<?= $this->Html->link('Excluir usuário', ['action' => 'delete', $user->id], ['class' => 'queues-btn queues-btn--danger']) ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
		$('#queuesMarcarTodas').on('click', function () {
			$('.queues-checkboxes .js-queue-cb').prop('checked', true);
		});
		$('#queuesDesmarcarTodas').on('click', function () {
			$('.queues-checkboxes .js-queue-cb').prop('checked', false);
		});
	});

	$(document).on('change', '.file-input', function() {
		var filesCount = $(this)[0].files.length;
		var $textContainer = $(this).prev();
		if (filesCount === 1) {
			var fileName = $(this).val().split('\\').pop();
			$textContainer.text(fileName);
		}
	});

	var email = $('#email').val();

	$('#email').change(function() {
		if(email != $(this).val()) {
			$.ajax({
				url: "<?= Router::url(['controller'=>'Users','action'=>'verificalogincadastro']);?>/" + $('#email').val(),
				success: function(data){
					if(data == 'podecadastrar') {
						$('#btnSalvarUsuario').prop('disabled', false);
						$('#btnSalvarUsuario').removeClass('btn-disabled');
					}else{
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Já existe um usuário com este e-mail no sistema.</p>');
						$('#btnSalvarUsuario').prop('disabled', 'disabled');
						$('#btnSalvarUsuario').addClass('btn-disabled');
					}
				},
			});
		} else {
			$('#btnSalvarUsuario').prop('disabled', false);
			$('#btnSalvarUsuario').removeClass('btn-disabled');
		}
	});
</script>
