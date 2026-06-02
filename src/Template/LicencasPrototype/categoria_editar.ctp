<?php
/** @var array<string,mixed>|null $licCategoria @var int $licCategoriaId @var string $licWizardReturn */
$cat = (array)($licCategoria ?? []);
$csrf = (string)$this->request->getAttribute('csrfToken');
$id = (int)($licCategoriaId ?? 0);
$return = trim((string)($licWizardReturn ?? ''));
$voltar = $return === 'nova'
	? ['action' => 'view', 'categorias', '?' => ['return' => 'nova']]
	: ['action' => 'view', 'categorias'];
?>
<div style="margin-bottom:14px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;"><?= $id > 0 ? h(__('Editar categoria')) : h(__('Nova categoria')) ?></h1>
</div>
<form method="post" action="<?= h($this->Url->build(['action' => 'salvarCategoria'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<?php if ($id > 0) : ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
<?php if ($return !== '') : ?><input type="hidden" name="return" value="<?= h($return) ?>"><?php endif; ?>
<div class="card">
	<div class="field"><label><?= h(__('Código')) ?> *</label><input name="codigo" required maxlength="30" value="<?= h($cat['codigo'] ?? '') ?>" placeholder="OFFICE"></div>
	<div class="field"><label><?= h(__('Nome')) ?> *</label><input name="nome" required maxlength="120" value="<?= h($cat['nome'] ?? '') ?>"></div>
	<label><input type="checkbox" name="ativo" value="1"<?= !isset($cat['ativo']) || !empty($cat['ativo']) ? ' checked' : '' ?>> <?= h(__('Ativa')) ?></label>
</div>
<div style="display:flex;gap:8px;margin-top:12px;">
	<?= $this->Html->link('← ' . __('Voltar'), $voltar, ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Salvar')) ?></button>
</div>
</form>
