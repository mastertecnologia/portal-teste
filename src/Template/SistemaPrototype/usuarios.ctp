<?php
/**
 * Usuários do ERP — mockup pg-usuarios.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $usrItems
 * @var array{total:int,equipe:int,portal:int,admins:int} $usrKpi
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">👥 <?= h(__('Usuários do ERP')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d usuários · %d equipe · %d portal')), (int)$usrKpi['total'], (int)$usrKpi['equipe'], (int)$usrKpi['portal']) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Módulo clássico'), ['controller' => 'Users', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo usuário'), ['controller' => 'Users', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$usrKpi['total'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Equipe (ERP)')) ?></div><div class="stat-n"><?= (int)$usrKpi['equipe'] ?></div></div>
	<div class="stat" style="--sc:var(--purple);"><div class="stat-l"><?= h(__('Portal cliente')) ?></div><div class="stat-n"><?= (int)$usrKpi['portal'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Administradores')) ?></div><div class="stat-n"><?= (int)$usrKpi['admins'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th></th>
					<th><?= h(__('Nome')) ?></th>
					<th><?= h(__('E-mail')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Admin')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th><?= h(__('Desde')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($usrItems === []) : ?>
					<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum usuário no escopo.')) ?></td></tr>
				<?php else : foreach ($usrItems as $u) : ?>
					<tr>
						<td><div class="av"><?= h($H->initials((string)$u['nome'])) ?></div></td>
						<td><strong><?= h((string)$u['nome']) ?></strong></td>
						<td style="font-size:11px;color:var(--text-muted);"><?= h((string)$u['email']) ?></td>
						<td><?= $H->badge($u['role'] === 'equipe' ? __('Equipe') : __('Portal'), $u['role'] === 'equipe' ? 'aprov' : 'env') ?></td>
						<td><?= !empty($u['admin']) ? '👑' : '—' ?></td>
						<td><?= $H->badge(!empty($u['inativo']) ? __('Inativo') : __('Ativo'), !empty($u['inativo']) ? 'arq' : 'paga') ?></td>
						<td class="mu"><?= h($H->dt($u['created'])) ?></td>
						<td class="r" style="white-space:nowrap;">
							<?= $this->Html->link(__('Editar'), ['controller' => 'Users', 'action' => 'edit', (int)$u['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
							<?= $this->Html->link(__('RBAC'), ['controller' => 'SistemaPrototype', 'action' => 'acessoUsuario', '?' => ['user_id' => (int)$u['id']]], ['class' => 'btn btn-ghost btn-xs']) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
