<?php
/**
 * Lista de clientes — mockup pg-clientes.
 *
 * @var \App\View\AppView $this
 * @var array{total:int,pj:int,pf:int,ativos:int,inativos:int} $cliCounts
 * @var array<int,array<string,mixed>> $cliItems
 */
$H = $this->ErpPrototype;
$f = (array)($cliFiltros ?? ['q' => '', 'tipo' => '', 'status' => '']);
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Cadastros'),
	'title' => __('Clientes'),
	'subtitle' => __('Cadastro mestre · CRM básico · Histórico financeiro consolidado'),
	'actions' => [
		['label' => __('Exportar CSV'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'exportCsv'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Importar'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'view', 'import'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Novo cliente'), 'url' => ['controller' => 'Clientes', 'action' => 'add'], 'class' => 'btn btn-primary btn-sm'],
	],
]) ?>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$cliCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('PJ')) ?></div><div class="stat-n"><?= (int)$cliCounts['pj'] ?></div></div>
	<div class="stat" style="--sc:var(--purple);"><div class="stat-l"><?= h(__('PF')) ?></div><div class="stat-n"><?= (int)$cliCounts['pf'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativos')) ?></div><div class="stat-n"><?= (int)$cliCounts['ativos'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Inativos')) ?></div><div class="stat-n"><?= (int)$cliCounts['inativos'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Inadimplentes')) ?></div><div class="stat-n"><?= (int)($cliCounts['inadimplentes'] ?? 0) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:240px;">
				<label><?= h(__('Buscar')) ?></label>
				<input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="🔍 <?= h(__('Nome, CNPJ, e-mail, telefone...')) ?>">
			</div>
			<div class="field" style="flex:0 0 120px;">
				<label><?= h(__('Tipo')) ?></label>
				<select name="tipo">
					<option value=""><?= h(__('Todos')) ?></option>
					<option value="pj"<?= (string)$f['tipo'] === 'pj' ? ' selected' : '' ?>>PJ</option>
					<option value="pf"<?= (string)$f['tipo'] === 'pf' ? ' selected' : '' ?>>PF</option>
				</select>
			</div>
			<div class="field" style="flex:0 0 140px;">
				<label><?= h(__('Status')) ?></label>
				<select name="status">
					<option value=""><?= h(__('Todos')) ?></option>
					<option value="ativo"<?= (string)$f['status'] === 'ativo' ? ' selected' : '' ?>>Ativo</option>
					<option value="inativo"<?= (string)$f['status'] === 'inativo' ? ' selected' : '' ?>>Inativo</option>
				</select>
			</div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
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
				<?php else : foreach ($cliItems as $it) :
					$cliHref = $this->Url->build(['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']]);
				?>
					<tr data-cli-row="<?= (int)$it['id'] ?>" data-pgm-row-href="<?= h($cliHref) ?>" tabindex="0">
						<td><span class="badge <?= $it['tipo'] === 'PJ' ? 'b-aprov' : 'b-env' ?>"><?= h((string)$it['tipo']) ?></span></td>
						<td>
							<strong><?= h((string)$it['nome']) ?></strong>
							<?php if (!empty($it['public_code'])) : ?>
								<div style="font-size:10px;color:var(--teal);font-family:monospace;"><?= h((string)$it['public_code']) ?></div>
							<?php endif; ?>
							<?php if (!empty($it['fantasia'])) : ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['fantasia']) ?></div>
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;"><?= h((string)$it['cnpj']) ?></td>
						<td style="font-size:11px;">
							<div style="display:flex;align-items:center;gap:4px;margin-bottom:2px;">
								<span title="<?= h(__('E-mail')) ?>">📧</span>
								<input type="email" data-cli-edit="email" data-cli-id="<?= (int)$it['id'] ?>" value="<?= h((string)$it['email']) ?>" placeholder="—" style="border:1px dashed transparent;background:transparent;font-size:11px;padding:2px 4px;border-radius:3px;flex:1;min-width:120px;">
							</div>
							<div style="display:flex;align-items:center;gap:4px;">
								<span title="<?= h(__('Telefone')) ?>">📞</span>
								<input type="tel" data-cli-edit="fone" data-cli-id="<?= (int)$it['id'] ?>" value="<?= h((string)$it['fone']) ?>" placeholder="—" style="border:1px dashed transparent;background:transparent;font-size:11px;padding:2px 4px;border-radius:3px;flex:1;min-width:120px;">
							</div>
						</td>
						<td class="mu"><?= h($H->dt($it['desde'])) ?></td>
						<td><?= $H->badge($it['inativo'] ? __('Inativo') : __('Ativo'), $it['inativo'] ? 'arq' : 'paga') ?></td>
						<td class="r" style="white-space:nowrap;">
							<?= $this->Html->link(__('360°'), ['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']], ['class' => 'btn btn-primary btn-xs', 'data-turbo' => 'false']) ?>
							<?= $this->Html->link(__('Editar'), ['controller' => 'Clientes', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs', 'data-turbo' => 'false']) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	💡 <?= h(__('Você pode editar e-mail e telefone direto na tabela — basta clicar no campo, alterar e sair (Tab/clique fora).')) ?>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var csrf = <?= json_encode((string)$this->request->getAttribute('csrfToken')) ?>;
	var url = <?= json_encode($this->Url->build(['controller' => 'ClientesPrototype', 'action' => 'apiAtualizarContato'])) ?>;
	document.querySelectorAll('[data-cli-edit]').forEach(function (el) {
		var orig = el.value;
		el.addEventListener('focus', function () {
			el.style.borderColor = '#1D9E75';
			el.style.background = '#fff';
		});
		el.addEventListener('blur', function () {
			el.style.borderColor = 'transparent';
			el.style.background = 'transparent';
			var v = el.value.trim();
			if (v === orig) return;
			var fd = new FormData();
			fd.append('_csrfToken', csrf);
			fd.append('cliente_id', el.getAttribute('data-cli-id'));
			fd.append('campo', el.getAttribute('data-cli-edit'));
			fd.append('valor', v);
			fetch(url, {method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data.ok) {
						orig = v;
						el.style.background = '#E1F5EE';
						setTimeout(function () { el.style.background = 'transparent'; }, 800);
					} else {
						el.value = orig;
						alert(data.error || 'Falha ao salvar');
					}
				});
		});
	});
})();
</script>
<?php $this->end(); ?>
