<?php
$this->Breadcrumbs->add('Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Bancosenhas', 'action' => 'edit', $senha->id], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Alterar senha do cofre', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/css/vault-cofre.css');
$this->end();
?>
<div class="col-12 p-0 vault-form-page">
	<div class="vault-cofre vault-cofre--compact">
		<header class="vault-cofre-header">
			<h1>
				<i class="fa fa-key" aria-hidden="true"></i>
				Alterar senha do cofre
				<span class="vault-badge">Segredo</span>
			</h1>
			<div class="vault-header-actions">
				<?= $this->Html->link(
					'<i class="fa fa-arrow-left"></i> Editar credencial',
					['action' => 'edit', $senha->id],
					['class' => 'btn btn-secondary', 'escape' => false]
				) ?>
			</div>
		</header>
		<div class="vault-form-body">
			<div class="vault-form-card">
				<p class="vault-form-note vault-form-note--flush">
					Informe a <strong>senha atual em texto claro</strong> (a mesma usada no login do serviço). Ela será regravada com
					<?= !empty($vaultDedicatedKey) ? 'AES-256-CBC (<code>v2:</code>)' : 'o padrão legado do sistema' ?>.
				</p>
				<?= $this->Form->create($senha, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Senha atual (clara)</label>
							<?= $this->Form->control('old_password', ['class' => 'form-control', 'label' => false, 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'placeholder' => 'Senha atual do serviço']); ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Nova senha</label>
							<?= $this->Form->control('password1', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'autocomplete' => 'new-password']); ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Confirmar nova senha</label>
							<?= $this->Form->control('password2', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'autocomplete' => 'new-password']); ?>
						</div>
					</div>
				</div>
				<div class="vault-form-actions">
					<?= $this->Form->button('Atualizar segredo', ['class' => 'btn btn-success vault-btn-copy']) ?>
				</div>
				<?= $this->Form->end(); ?>
			</div>
		</div>
	</div>
</div>
