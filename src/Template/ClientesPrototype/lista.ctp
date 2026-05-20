<?php
/**
 * Lista de clientes — mockup pg-clientes.
 *
 * @var \App\View\AppView $this
 * @var array{total:int,pj:int,pf:int,ativos:int,inativos:int} $cliCounts
 * @var array<int,array<string,mixed>> $cliItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Cadastros')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">👥 <?= h(__('Clientes')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d clientes · %d PJ · %d PF no escopo')), (int)$cliCounts['total'], (int)$cliCounts['pj'], (int)$cliCounts['pf']) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📤 ' . __('Exportar'), ['controller' => 'ClientesPrototype', 'action' => 'view', 'export'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📥 ' . __('Importar'), ['controller' => 'ClientesPrototype', 'action' => 'view', 'import'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo cliente'), ['controller' => 'ClientesPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$cliCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('PJ')) ?></div><div class="stat-n"><?= (int)$cliCounts['pj'] ?></div></div>
	<div class="stat" style="--sc:var(--purple);"><div class="stat-l"><?= h(__('PF')) ?></div><div class="stat-n"><?= (int)$cliCounts['pf'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativos')) ?></div><div class="stat-n"><?= (int)$cliCounts['ativos'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Inativos')) ?></div><div class="stat-n"><?= (int)$cliCounts['inativos'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<input type="text" placeholder="🔍 <?= h(__('Buscar nome, CNPJ, e-mail, telefone...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Nome / Razão social')) ?></th>
					<th><?= h(__('CNPJ / CPF')) ?></th>
					<th><?= h(__('Contato')) ?></th>
					<th><?= h(__('Desde')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($cliItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum cliente no escopo.')) ?></td></tr>
				<?php else : foreach ($cliItems as $it) : ?>
					<tr>
						<td><span class="badge <?= $it['tipo'] === 'PJ' ? 'b-aprov' : 'b-env' ?>"><?= h((string)$it['tipo']) ?></span></td>
						<td>
							<strong><?= h((string)$it['nome']) ?></strong>
							<?php if (!empty($it['fantasia'])) : ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['fantasia']) ?></div>
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;"><?= h((string)$it['cnpj']) ?></td>
						<td style="font-size:11px;">
							<?php if (!empty($it['email'])) : ?>📧 <?= h((string)$it['email']) ?><br><?php endif; ?>
							<?php if (!empty($it['fone'])) : ?>📞 <?= h((string)$it['fone']) ?><?php endif; ?>
						</td>
						<td class="mu"><?= h($H->dt($it['desde'])) ?></td>
						<td><?= $H->badge($it['inativo'] ? __('Inativo') : __('Ativo'), $it['inativo'] ? 'arq' : 'paga') ?></td>
						<td class="r">
							<?= $this->Html->link(__('Abrir'), ['controller' => 'Clientes', 'action' => 'view', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
