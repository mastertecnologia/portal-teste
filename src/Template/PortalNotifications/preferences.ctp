<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array{code:string,label:string,send_in_app:bool,send_email:bool}> $prefRows
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Preferências de notificações', [], ['class' => 'breadcrumb-item active']);
?>
<?= $this->element('Pgm/form_shell_dark', ['formId' => 'form-portal-notif-prefs']) ?>
<div class="col-12 clictr-edit-page">
	<div class="clictr-card clictr-card--wide">
			<h4 class="clictr-page-title">Preferências de notificações</h4>
			<p class="clictr-page-lead mb-0">Defina o que recebe no sino do portal e o que também gera e-mail automático quando o evento ocorrer.</p>

			<?= $this->Form->create(null, ['url' => ['action' => 'savePreferences'], 'class' => 'form-material clictr-form', 'id' => 'form-portal-notif-prefs']) ?>
			<div class="table-responsive m-t-20">
				<table class="table table-hover">
					<thead>
						<tr>
							<th>Evento</th>
							<th class="text-center">No sistema (sino)</th>
							<th class="text-center">E-mail</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($prefRows as $i => $r): ?>
							<tr>
								<td>
									<?= $this->Form->hidden("codes.$i", ['value' => $r['code']]) ?>
									<strong><?= h($r['label']) ?></strong>
									<div class="small text-muted"><?= h($r['code']) ?></div>
								</td>
								<td class="text-center align-middle">
									<?= $this->Form->checkbox("prefs.$i.send_in_app", [
										'value' => 1,
										'checked' => !empty($r['send_in_app']),
										'class' => 'form-check-input',
										'style' => 'position:relative;margin:0',
										'label' => false,
									]) ?>
								</td>
								<td class="text-center align-middle">
									<?= $this->Form->checkbox("prefs.$i.send_email", [
										'value' => 1,
										'checked' => !empty($r['send_email']),
										'class' => 'form-check-input',
										'style' => 'position:relative;margin:0',
										'label' => false,
									]) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?= $this->Form->button('Salvar preferências', ['class' => 'btn btn-success']) ?>
			<?= $this->Form->end() ?>
	</div>
</div>
