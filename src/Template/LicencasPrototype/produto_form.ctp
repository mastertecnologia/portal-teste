<?php
/** @var array<string,mixed>|null $licProduto @var array<int,array<string,mixed>> $licCategorias @var array<int,array{id:int,nome:string,cnpj:string}> $licFornecedores @var string $licWizardReturn @var int $licWizardLicId */
$p = (array)($licProduto ?? []);
$csrf = (string)$this->request->getAttribute('csrfToken');
$id = (int)($p['id'] ?? 0);
$return = trim((string)($licWizardReturn ?? ''));
$licId = (int)($licWizardLicId ?? 0);
$voltar = $return === 'nova'
	? ['action' => 'view', 'nova']
	: ($return !== '' && $licId > 0
		? ['action' => 'view', $return, '?' => ['id' => $licId]]
		: ['action' => 'view', 'catalogo']);
?>
<div style="margin-bottom:14px;">
	<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= $this->Html->link(__('Licenciamento'), ['action' => 'dashboard'], ['style' => 'color:var(--teal)']) ?> › <?= h($id > 0 ? __('Editar produto') : __('Novo produto')) ?></div>
	<h1 style="font-size:22px;font-weight:600;margin:0;"><?= $id > 0 ? h(__('Editar produto')) : h(__('Novo produto no catálogo')) ?></h1>
</div>
<form method="post" action="<?= h($this->Url->build(['action' => 'salvarCatalogoProduto'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<?php if ($id > 0) : ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
<?php if ($return !== '') : ?>
<input type="hidden" name="return" value="<?= h($return) ?>">
<input type="hidden" name="lic_id" value="<?= $licId ?>">
<?php endif; ?>
<div class="card g2">
	<div class="field"><label><?= h(__('Nome')) ?> *</label><input name="nome" required maxlength="200" value="<?= h($p['nome'] ?? '') ?>"></div>
	<div class="field"><label><?= h(__('SKU')) ?></label><input name="sku" maxlength="60" value="<?= h($p['sku'] ?? '') ?>"></div>
	<div class="field"><label><?= h(__('Categoria')) ?> *</label>
		<select name="idcategoria" required>
			<option value=""><?= h(__('Selecione…')) ?></option>
			<?php foreach ((array)($licCategorias ?? []) as $c) : ?>
			<option value="<?= (int)$c['id'] ?>"<?= (int)($p['idcategoria'] ?? 0) === (int)$c['id'] ? ' selected' : '' ?>><?= h($c['nome']) ?> (<?= h($c['codigo']) ?>)</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="field"><label><?= h(__('Fornecedor')) ?></label>
		<select name="idfornecedor_cliente">
			<option value=""><?= h(__('Selecione o fornecedor cadastrado…')) ?></option>
			<?php foreach ((array)($licFornecedores ?? []) as $f) : ?>
			<option value="<?= (int)$f['id'] ?>"<?= (int)($p['idfornecedor_cliente'] ?? 0) === (int)$f['id'] ? ' selected' : '' ?>><?= h($f['nome']) ?><?= ($f['cnpj'] ?? '') !== '' ? ' · ' . h($f['cnpj']) : '' ?></option>
			<?php endforeach; ?>
		</select>
		<div style="font-size:10px;color:var(--text-muted);margin-top:4px;"><?= h(__('Somente clientes PJ cadastrados em Fornecedores')) ?> · <?= $this->Html->link('+ ' . __('Novo fornecedor'), ['controller' => 'FornecedoresPrototype', 'action' => 'view', 'novo'], ['style' => 'color:var(--teal);']) ?></div>
	</div>
	<label><input type="checkbox" name="ativo" value="1"<?= !isset($p['ativo']) || !empty($p['ativo']) ? ' checked' : '' ?>> <?= h(__('Ativo')) ?></label>
</div>
<div style="display:flex;gap:8px;margin-top:12px;">
	<?= $this->Html->link('← ' . __('Voltar'), $voltar, ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Salvar')) ?></button>
</div>
</form>
