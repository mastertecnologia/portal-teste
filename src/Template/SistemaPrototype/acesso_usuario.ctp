<?php
/**
 * Ficha de acesso · usuário — mockup pg-acesso-usuario.
 *
 * @var \App\View\AppView $this
 * @var int $acessoUserId
 * @var mixed $acessoUser
 * @var array<int,array<string,mixed>> $acessoPapeis
 * @var array{pending:int,approved_30d:int,rejected_30d:int} $acessoPedidos
 */
$H = $this->ErpPrototype;
$nome = $acessoUser ? trim((string)($acessoUser->get('name') ?? $acessoUser->get('username'))) : '—';
$initials = $H->initials($nome !== '' ? $nome : 'U');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div class="av" style="width:54px;height:54px;font-size:18px;background:linear-gradient(135deg,var(--purple),var(--purple-dark));color:#fff;"><?= h($initials) ?></div>
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · RBAC')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($nome) ?></h1>
			<?php if ($acessoUser) : ?>
				<div style="font-size:12px;color:var(--text-muted);">
					<?= h((string)($acessoUser->get('email') ?? $acessoUser->get('username') ?? '')) ?>
					· <?= (int)$acessoUser->get('role') === 0 ? __('Equipe') : __('Portal') ?>
					<?php if ((int)$acessoUser->get('admin') === 1) : ?>· 👑 <?= h(__('Admin')) ?><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Central'), ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?php if ($acessoUserId > 0) : ?>
			<?= $this->Html->link(__('Papéis (legado)'), ['controller' => 'Permissoes', 'action' => 'adminUserRoles', $acessoUserId], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link(__('Editar usuário'), ['controller' => 'Users', 'action' => 'edit', $acessoUserId], ['class' => 'btn btn-primary btn-sm']) ?>
		<?php endif; ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Papéis ativos')) ?></div><div class="val" style="color:var(--teal-dark);"><?= count($acessoPapeis) ?></div></div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Pedidos pendentes')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)$acessoPedidos['pending'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-mid);"><div class="lbl"><?= h(__('Aprovados (30d)')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$acessoPedidos['approved_30d'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Rejeitados (30d)')) ?></div><div class="val" style="color:#7A1822;"><?= (int)$acessoPedidos['rejected_30d'] ?></div></div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Papéis RBAC atribuídos')) ?></div>
	<?php if ($acessoPapeis === []) : ?>
		<p style="color:var(--text-muted);margin:0;"><?= h(__('Este usuário ainda não possui papéis RBAC. Atribua um para liberar funcionalidades além do legado.')) ?></p>
	<?php else : ?>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
			<?php foreach ($acessoPapeis as $r) : ?>
				<div style="border:1px solid var(--border);border-radius:var(--radius);padding:10px 12px;">
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
						<strong><?= h((string)$r['name']) ?></strong>
						<?php if (!empty($r['is_system'])) : ?><span class="badge b-aprov">⚙</span><?php endif; ?>
					</div>
					<div style="font-family:monospace;font-size:10px;color:var(--text-muted);"><?= h((string)$r['slug']) ?></div>
					<div style="font-size:10px;color:var(--text-muted);margin-top:4px;"><?= h(__('Hierarquia')) ?>: <?= (int)$r['hierarchy'] ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<div class="footer-bar">
	<?= $this->Html->link('🛡 ' . __('Gerenciar papéis'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link('📨 ' . __('Pedidos de acesso'), ['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcessoManager'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
