<?php
/**
 * Fornecedores — clientes PJ (mockup pg-fornecedores).
 *
 * @var \App\View\AppView $this
 * @var array{total:int,ativos:int,inativos:int} $fornCounts
 * @var array<int,array<string,mixed>> $fornItems
 */
$H = $this->ErpPrototype;
$f = (array)($fornFiltros ?? ['q' => '', 'status' => '']);
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Cadastros'),
	'title' => __('Fornecedores'),
	'subtitle' => __('Cadastro via clientes PJ · mesmo escopo usado em contas a pagar e NF-e de entrada'),
	'actions' => [
		['label' => __('Clientes (todos)'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'lista'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('NF-e entrada'), 'url' => ['controller' => 'FiscalNotas', 'action' => 'index'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Novo fornecedor (PJ)'), 'url' => ['controller' => 'Clientes', 'action' => 'add'], 'class' => 'btn btn-primary'],
	],
]) ?>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total PJ')) ?></div><div class="stat-n"><?= (int)$fornCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativos')) ?></div><div class="stat-n"><?= (int)$fornCounts['ativos'] ?></div></div>
	<div class="stat" style="--sc:var(--gray-400);"><div class="stat-l"><?= h(__('Inativos')) ?></div><div class="stat-n"><?= (int)$fornCounts['inativos'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:240px;">
				<label><?= h(__('Buscar')) ?></label>
				<input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="🔍 <?= h(__('Razão social, CNPJ, e-mail...')) ?>">
			</div>
			<div class="field" style="flex:0 0 140px;">
				<label><?= h(__('Status')) ?></label>
				<select name="status">
					<option value=""><?= h(__('Todos')) ?></option>
					<option value="ativo"<?= (string)$f['status'] === 'ativo' ? ' selected' : '' ?>><?= h(__('Ativo')) ?></option>
					<option value="inativo"<?= (string)$f['status'] === 'inativo' ? ' selected' : '' ?>><?= h(__('Inativo')) ?></option>
				</select>
			</div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'FornecedoresPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Razão social')) ?></th>
					<th><?= h(__('CNPJ')) ?></th>
					<th><?= h(__('Contato')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($fornItems === []) : ?>
					<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum fornecedor (cliente PJ) no escopo.')) ?></td></tr>
				<?php else : foreach ($fornItems as $it) :
					$rowHref = $this->Url->build(['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']]);
				?>
					<tr data-pgm-row-href="<?= h($rowHref) ?>" tabindex="0">
						<td>
							<strong><?= h((string)$it['nome']) ?></strong>
							<?php if (!empty($it['fantasia'])) : ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['fantasia']) ?></div>
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;"><?= h((string)$it['cnpj']) ?></td>
						<td style="font-size:11px;">
							<?php if (!empty($it['email'])) : ?><?= h((string)$it['email']) ?><br><?php endif; ?>
							<?php if (!empty($it['fone'])) : ?><?= h((string)$it['fone']) ?><?php endif; ?>
						</td>
						<td><?= $H->badge($it['inativo'] ? __('Inativo') : __('Ativo'), $it['inativo'] ? 'arq' : 'paga') ?></td>
						<td class="r" style="white-space:nowrap;">
							<?= $this->Html->link(__('360°'), ['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
							<?= $this->Html->link(__('Editar'), ['controller' => 'Clientes', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Fornecedores compartilham o cadastro de Clientes (tipo PJ). Use Contas a pagar ou Fiscal para vínculos financeiros.')) ?>
</div>
