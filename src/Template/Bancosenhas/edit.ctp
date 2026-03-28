<?php
$this->Breadcrumbs->add('Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar credencial', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/css/vault-cofre.css');
$this->end();
?>
<div class="col-12 p-0 vault-form-page">
	<div class="vault-cofre" style="min-height: auto;">
		<header class="vault-cofre-header">
			<h1>
				<i class="fa fa-edit" aria-hidden="true"></i>
				Editar credencial
				<span class="vault-badge"><?= !empty($vaultDedicatedKey) ? 'AES-256' : 'Cofre' ?></span>
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
				<p class="small text-muted" style="margin-top:0;">
					<strong><?= h($senha->nomeservico ?: 'Sem nome') ?></strong>
					<?php if ($senha->provedor): ?> · <?= h($senha->provedor) ?><?php endif; ?>
				</p>
				<p class="vault-form-note" style="margin-top:0;">
					A senha armazenada não é exibida aqui. Use <strong>Alterar senha do cofre</strong> para trocá-la.
				</p>

				<?= $this->Form->create($senha, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Nome do serviço</label>
							<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Usuário</label>
							<?= $this->Form->control('usuario', ['class' => 'form-control', 'label' => false, 'required' => true]); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">IP</label>
							<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Protocolo</label>
							<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'list' => 'protocolos']); ?>
							<datalist id="protocolos">
								<?php foreach (C_ProtocolosArray as $reg) {
									echo '<option value="' . h($reg) . '">';
								} ?>
							</datalist>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Provedor</label>
							<?= $this->Form->control('provedor', ['class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-md-5 col-xs-12">
						<div class="form-group">
							<label class="control-label">URL</label>
							<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group">
							<label class="control-label">Porta</label>
							<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
				</div>
				<div class="vault-form-actions">
					<?= $this->Form->button('Salvar alterações', ['class' => 'btn btn-success vault-btn-copy', 'style' => 'color:#fff !important;']) ?>
					<?= $this->Html->link(
						'<i class="fa fa-key"></i> Alterar senha do cofre',
						['action' => 'change_password', $senha->id],
						['class' => 'btn btn-warning', 'escape' => false]
					) ?>
				</div>
				<?= $this->Form->end(); ?>
			</div>
		</div>
	</div>
</div>
