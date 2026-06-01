<?php
/** @var array<string,mixed>|null $licCategoria @var int $licCategoriaId */
$cat = (array)($licCategoria ?? []);
$csrf = (string)$this->request->getAttribute('csrfToken');
$id = (int)($licCategoriaId ?? 0);
?>
<h1 style="font-size:20px;margin-bottom:14px;"><?= $id > 0 ? h(__('Editar categoria')) : h(__('Nova categoria')) ?></h1>
<form method="post" action="<?= h($this->Url->build(['action' => 'salvarCategoria'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<?php if ($id > 0) : ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
<div class="card">
	<div class="field"><label><?= h(__('Código')) ?> *</label><input name="codigo" required maxlength="30" value="<?= h($cat['codigo'] ?? '') ?>"></div>
	<div class="field"><label><?= h(__('Nome')) ?> *</label><input name="nome" required maxlength="120" value="<?= h($cat['nome'] ?? '') ?>"></div>
	<label><input type="checkbox" name="ativo" value="1"<?= !isset($cat['ativo']) || !empty($cat['ativo']) ? ' checked' : '' ?>> <?= h(__('Ativa')) ?></label>
</div>
<button type="submit" class="btn btn-primary"><?= h(__('Salvar')) ?></button>
</form>
