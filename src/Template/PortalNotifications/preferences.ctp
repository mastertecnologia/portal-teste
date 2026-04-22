<?php
/**
 * Preferências de notificações (equipe) — eventos de domínio cliente/contrato/ERP/usuários.
 *
 * @var \App\View\AppView $this
 * @var array<int, array{code:string,label:string,send_in_app:bool,send_email:bool}> $prefRows
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Preferências de notificações', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 cli-ficha-layout-unificado">
<div class="cli-form-root cli-root cli-layout-unificado">

	<div class="cli-page-head">
		<div class="cli-page-head-left">
			<div class="cli-eyebrow">Notificações · Equipe</div>
			<h1>Preferências de notificações</h1>
			<p class="mb-0">Defina o que recebe no sino do portal e o que também gera e-mail automático quando o evento ocorrer.</p>
		</div>
		<div class="d-flex align-items-center flex-wrap pgm-gap-8">
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left"></i> Lista de clientes',
				['controller' => 'Clientes', 'action' => 'index'],
				['class' => 'btn btn-sm btn-cli-secondary', 'escape' => false, 'data-turbo' => 'false']
			) ?>
		</div>
	</div>

	<div class="cli-list-card">
		<?= $this->Form->create(null, ['url' => ['action' => 'savePreferences'], 'class' => 'form-material mb-0', 'id' => 'form-portal-notif-prefs']) ?>
		<div class="cli-table-wrap">
			<div class="cli-table-card">
				<table class="table cli-table mb-0">
					<thead>
						<tr>
							<th>Evento</th>
							<th class="text-center" style="width:9rem">No sistema (sino)</th>
							<th class="text-center" style="width:9rem">E-mail</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($prefRows as $i => $r): ?>
							<tr>
								<td>
									<?= $this->Form->hidden("codes.$i", ['value' => $r['code']]) ?>
									<strong><?= h($r['label']) ?></strong>
									<div class="cli-td-doc small mt-1"><?= h($r['code']) ?></div>
								</td>
								<td class="text-center align-middle">
									<?= $this->Form->checkbox("prefs.$i.send_in_app", [
										'value' => 1,
										'checked' => !empty($r['send_in_app']),
										'class' => 'form-check-input pgm-checkbox-table-cell',
										'label' => false,
									]) ?>
								</td>
								<td class="text-center align-middle">
									<?= $this->Form->checkbox("prefs.$i.send_email", [
										'value' => 1,
										'checked' => !empty($r['send_email']),
										'class' => 'form-check-input pgm-checkbox-table-cell',
										'label' => false,
									]) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="cli-table-footer cli-dt-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
			<span class="small text-muted mb-0">As alterações aplicam-se à sua conta de equipe.</span>
			<?= $this->Form->button('<i class="fas fa-save"></i> Salvar preferências', ['class' => 'btn btn-cli-primary', 'escape' => false]) ?>
		</div>
		<?= $this->Form->end() ?>
	</div>

</div>
</div>
