<?php
/** @var array<string,mixed>|null $licProduto @var array<int,array<string,mixed>> $licCategorias @var array<int,string> $licClientes */
$p = (array)($licProduto ?? []);
$csrf = (string)$this->request->getAttribute('csrfToken');
$id = (int)($p['id'] ?? 0);
?>
<h1 style="font-size:20px;margin-bottom:14px;"><?= $id > 0 ? h(__('Editar produto')) : h(__('Novo produto')) ?></h1>
<form method="post" action="<?= h($this->Url->build(['action' => 'salvarCatalogoProduto'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<?php if ($id > 0) : ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
<div class="card g2">
	<div class="field"><label><?= h(__('Nome')) ?> *</label><input name="nome" required maxlength="200" value="<?= h($p['nome'] ?? '') ?>"></div>
	<div class="field"><label><?= h(__('SKU')) ?></label><input name="sku" maxlength="60" value="<?= h($p['sku'] ?? '') ?>"></div>
	<div class="field"><label><?= h(__('Categoria')) ?></label>
		<select name="idcategoria">
			<option value=""><?= h(__('—')) ?></option>
			<?php foreach ((array)($licCategorias ?? []) as $c) : ?>
			<option value="<?= (int)$c['id'] ?>"<?= (int)($p['idcategoria'] ?? 0) === (int)$c['id'] ? ' selected' : '' ?>><?= h($c['nome']) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="field"><label><?= h(__('Fornecedor (cliente PJ)')) ?></label>
		<select name="idfornecedor_cliente">
			<option value=""><?= h(__('—')) ?></option>
			<?php foreach ((array)($licClientes ?? []) as $cid => $cn) : ?>
			<option value="<?= (int)$cid ?>"<?= (int)($p['idfornecedor_cliente'] ?? 0) === (int)$cid ? ' selected' : '' ?>><?= h($cn) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<label><input type="checkbox" name="ativo" value="1"<?= !isset($p['ativo']) || !empty($p['ativo']) ? ' checked' : '' ?>> <?= h(__('Ativo')) ?></label>
</div>
<button type="submit" class="btn btn-primary"><?= h(__('Salvar')) ?></button>
</form>
