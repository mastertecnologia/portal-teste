<?php
$this->Breadcrumbs->add('Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Nova credencial', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/css/vault-cofre.css');
$this->end();
?>
<div class="col-12 p-0 vault-form-page">
	<div class="vault-cofre vault-cofre--compact">
		<header class="vault-cofre-header">
			<h1>
				<i class="fa fa-plus-circle" aria-hidden="true"></i>
				Nova credencial
				<span class="vault-badge">Cofre</span>
			</h1>
			<div class="vault-header-actions">
				<?= $this->Html->link(
					'<i class="fa fa-arrow-left"></i> Voltar ao cofre',
					['action' => 'index'],
					['class' => 'btn btn-secondary', 'escape' => false]
				) ?>
			</div>
		</header>
		<div class="vault-form-body">
			<div class="vault-form-card">
				<?php if (!empty($vaultDedicatedKey)): ?>
					<p class="vault-form-note vault-form-note--flush">
						<strong>AES-256-CBC ativo:</strong> esta credencial será gravada com o formato <code>v2:</code> (chave <code>VAULT_ENCRYPTION_KEY</code> no servidor).
					</p>
				<?php else: ?>
					<p class="vault-form-note vault-form-note--flush">
						<strong>Modo legado:</strong> criptografia PGM padrão. Para AES-256-CBC dedicado, defina <code>VAULT_ENCRYPTION_KEY</code> no <code>.env</code> (mín. 16 caracteres).
					</p>
				<?php endif; ?>

				<?= $this->Form->create($senha, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Nome do serviço</label>
							<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Ex.: Firewall matriz']); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Usuário</label>
							<?= $this->Form->control('usuario', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Login', 'required' => true]); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">IP</label>
							<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false, 'placeholder' => '0.0.0.0']); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Protocolo</label>
							<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'HTTPS', 'list' => 'protocolos']); ?>
							<datalist id="protocolos">
								<?php foreach (C_ProtocolosArray as $reg) {
									echo '<option value="' . h($reg) . '">';
								} ?>
							</datalist>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Provedor</label>
							<?= $this->Form->control('provedor', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Fabricante / cloud']); ?>
						</div>
					</div>
					<div class="col-md-5 col-xs-12">
						<div class="form-group">
							<label class="control-label">URL</label>
							<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false, 'placeholder' => 'https://…']); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Porta</label>
							<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false, 'placeholder' => '443']); ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Senha</label>
							<?= $this->Form->control('senha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Senha secreta', 'required' => true, 'autocomplete' => 'new-password']); ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label">Confirmar senha</label>
							<?= $this->Form->control('confirmasenha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Repita a senha', 'required' => true, 'autocomplete' => 'new-password']); ?>
						</div>
					</div>
				</div>
				<div class="vault-form-actions">
					<?= $this->Form->button('Salvar no cofre', ['class' => 'btn btn-success vault-btn-copy']) ?>
				</div>
				<?= $this->Form->end(); ?>
			</div>
		</div>
	</div>
</div>
