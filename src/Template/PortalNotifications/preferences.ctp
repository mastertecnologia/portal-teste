<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array{code:string,label:string,send_in_app:bool,send_email:bool}> $prefRows
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Preferências de notificações', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Preferências de notificações</h4>
			<p class="text-muted small mb-4">Defina o que recebe no sino do portal e o que também gera e-mail automático quando o evento ocorrer.</p>

			<?= $this->Form->create(null, ['url' => ['action' => 'savePreferences'], 'class' => 'form-material']) ?>
			<div class="table-responsive">
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
									<input type="hidden" name="prefs[<?= (int)$i ?>][send_in_app]" value="0">
									<input type="checkbox" name="prefs[<?= (int)$i ?>][send_in_app]" value="1" class="form-check-input" style="position:relative;margin:0" <?= !empty($r['send_in_app']) ? 'checked' : '' ?>>
								</td>
								<td class="text-center align-middle">
									<input type="hidden" name="prefs[<?= (int)$i ?>][send_email]" value="0">
									<input type="checkbox" name="prefs[<?= (int)$i ?>][send_email]" value="1" class="form-check-input" style="position:relative;margin:0" <?= !empty($r['send_email']) ? 'checked' : '' ?>>
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
</div>
