<?php /** @var array<int,string> $licClientes */ $csrf = (string)$this->request->getAttribute('csrfToken'); ?>
<h1 style="font-size:20px;margin-bottom:14px;">+ <?= h(__('Novo dispositivo')) ?></h1>
<form method="post" action="<?= h($this->Url->build(['action' => 'salvarDispositivo'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<div class="card g2">
	<div class="field"><label><?= h(__('Cliente')) ?> *</label>
		<select name="idcliente" required>
			<option value=""><?= h(__('Selecione…')) ?></option>
			<?php foreach ((array)($licClientes ?? []) as $cid => $cn) : ?>
			<option value="<?= (int)$cid ?>"><?= h($cn) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="field"><label><?= h(__('Hostname')) ?></label><input name="hostname" maxlength="120"></div>
	<div class="field"><label><?= h(__('Serial')) ?></label><input name="serial" maxlength="80"></div>
	<div class="field"><label><?= h(__('Sistema operacional')) ?></label><input name="so" maxlength="80"></div>
</div>
<button type="submit" class="btn btn-primary"><?= h(__('Salvar')) ?></button>
</form>
