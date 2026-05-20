<?php
/**
 * Simulador "View as user" — escolhe usuário e mostra papéis + permissões efetivas.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $viewAsCandidatos
 * @var array<string,mixed>|null $viewAsSelecionado
 * @var array<int,array<string,mixed>> $viewAsPapeis
 * @var array<int,array<string,mixed>> $viewAsPermissoes
 */
$H = $this->ErpPrototype;
$selUid = (int)($viewAsSelecionado['id'] ?? 0);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · RBAC')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🎭 <?= h(__('Simular acesso (View as user)')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Visualize quais papéis e permissões um usuário efetivamente tem hoje no RBAC')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Central'), ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="card">
	<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
		<div class="field" style="flex:1;min-width:280px;">
			<label><?= h(__('Selecione um usuário')) ?></label>
			<select name="user_id" onchange="this.form.submit()" style="width:100%;">
				<option value="0"><?= h(__('— Escolha um usuário —')) ?></option>
				<?php foreach ($viewAsCandidatos as $c) :
					$sel = (int)$c['id'] === $selUid ? ' selected' : '';
				?>
					<option value="<?= (int)$c['id'] ?>"<?= $sel ?>>
						<?= h((string)$c['nome']) ?> · <?= h((string)$c['email']) ?><?= !empty($c['admin']) ? ' · 👑' : '' ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Simular')) ?></button>
	</form>
</div>

<?php if ($viewAsSelecionado === null) : ?>
	<div class="alert-box alert-blue">
		<?= h(__('Escolha um usuário acima para ver papéis RBAC atribuídos e permissões agregadas.')) ?>
	</div>
	<?php return; ?>
<?php endif; ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Usuário')) ?></div>
		<div class="val" style="font-size:14px;color:var(--text);"><?= h((string)$viewAsSelecionado['nome']) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$viewAsSelecionado['email']) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid <?= !empty($viewAsSelecionado['admin']) ? 'var(--red)' : 'var(--blue)' ?>;">
		<div class="lbl"><?= h(__('Tipo')) ?></div>
		<div class="val" style="font-size:14px;">
			<?php if (!empty($viewAsSelecionado['admin'])) : ?>👑 <?= h(__('Administrador (bypass RBAC)')) ?><?php else : ?><?= (int)$viewAsSelecionado['role'] === 0 ? __('Equipe ERP') : __('Portal cliente') ?><?php endif; ?>
		</div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--purple);">
		<div class="lbl"><?= h(__('Papéis ativos')) ?></div>
		<div class="val" style="color:#3C3489;"><?= count($viewAsPapeis) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--teal-dark);">
		<div class="lbl"><?= h(__('Permissões efetivas')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= count($viewAsPermissoes) ?></div>
	</div>
</div>

<?php if (!empty($viewAsSelecionado['admin'])) : ?>
	<div class="alert-box alert-red">
		<strong><?= h(__('Atenção:')) ?></strong>
		<?= h(__('este usuário é administrador (users.admin = true). O RBAC é ignorado e ele tem acesso total a todas as funcionalidades, exceto onde houver checagem explícita extra.')) ?>
	</div>
<?php endif; ?>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Papéis atribuídos')) ?></div>
		<?php if ($viewAsPapeis === []) : ?>
			<p style="color:var(--text-muted);margin:0;"><?= h(__('Nenhum papel RBAC atribuído. O usuário tem apenas o acesso legado (role/admin).')) ?></p>
		<?php else : ?>
			<?php foreach ($viewAsPapeis as $r) : ?>
				<div style="padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
					<strong><?= h((string)$r['name']) ?></strong>
					<div style="font-family:monospace;font-size:10px;color:var(--text-muted);"><?= h((string)$r['slug']) ?></div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Permissões efetivas (agregadas dos papéis)')) ?></div>
		<?php if ($viewAsPermissoes === []) : ?>
			<p style="color:var(--text-muted);margin:0;"><?= h(__('Sem permissões RBAC. Acesso continua governado pelo legado users.role / users.admin.')) ?></p>
		<?php else : ?>
			<div style="max-height:320px;overflow-y:auto;">
				<?php foreach ($viewAsPermissoes as $p) : ?>
					<div style="padding:6px 0;border-bottom:1px solid var(--border-light);font-size:11px;display:flex;justify-content:space-between;gap:10px;">
						<span style="font-family:monospace;color:var(--teal-dark);font-weight:600;flex:0 0 auto;"><?= h((string)$p['code']) ?></span>
						<span style="color:var(--text-muted);flex:1;text-align:right;"><?= h(\Cake\Utility\Text::truncate((string)$p['description'], 60, ['ellipsis' => '…'])) ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('🛡 ' . __('Gerenciar papéis'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?php if ($selUid > 0) : ?>
		<?= $this->Html->link('🔑 ' . __('Ficha completa do usuário'), ['controller' => 'SistemaPrototype', 'action' => 'acessoUsuario', '?' => ['user_id' => $selUid]], ['class' => 'btn btn-primary btn-sm']) ?>
	<?php endif; ?>
</div>
