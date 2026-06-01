<?php
/** @var array<string,mixed>|null $licCofreItem @var array<int,string> $licClientes @var array<int,array<string,mixed>> $licLicencas */
$item = (array)($licCofreItem ?? []);
$id = (int)($item['id'] ?? 0);
$niveis = ['baixo' => __('Baixo'), 'medio' => __('Médio'), 'alto' => __('Alto')];
?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= $id > 0 ? h(__('Editar item do cofre')) : h(__('Novo item do cofre')) ?></h1>
<div class="card">
	<?= $this->Form->create(null, ['url' => ['action' => 'salvarCofre']]) ?>
	<?php if ($id > 0) : ?><?= $this->Form->hidden('id', ['value' => $id]) ?><?php endif; ?>
	<div class="g2">
		<div class="field"><label><?= h(__('Título')) ?> *</label><?= $this->Form->text('titulo', ['value' => $item['titulo'] ?? '', 'required' => true]) ?></div>
		<div class="field"><label><?= h(__('Nível')) ?></label>
			<select name="nivel">
				<?php foreach ($niveis as $k => $lbl) : ?>
				<option value="<?= h($k) ?>"<?= ($item['nivel'] ?? 'medio') === $k ? ' selected' : '' ?>><?= h($lbl) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field"><label><?= h(__('Cliente')) ?></label>
			<select name="idcliente">
				<option value=""><?= h(__('—')) ?></option>
				<?php foreach ((array)($licClientes ?? []) as $cid => $cn) : ?>
				<option value="<?= (int)$cid ?>"<?= (int)($item['idcliente'] ?? 0) === (int)$cid ? ' selected' : '' ?>><?= h($cn) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field"><label><?= h(__('Licença')) ?></label>
			<select name="idlicenca">
				<option value=""><?= h(__('—')) ?></option>
				<?php foreach ((array)($licLicencas ?? []) as $lic) : ?>
				<option value="<?= (int)$lic['id'] ?>"<?= (int)($item['idlicenca'] ?? 0) === (int)$lic['id'] ? ' selected' : '' ?>><?= h($lic['codigo'] . ' · ' . $lic['cliente']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="grid-column:1/-1;"><label><?= h(__('Segredo / credencial')) ?></label>
			<?= $this->Form->textarea('segredo', ['rows' => 3, 'placeholder' => $id > 0 ? __('Deixe em branco para manter o atual') : '']) ?>
			<p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;"><?= h(__('Com LIC_COFRE_CIPHER_KEY: AES-256-GCM; senão prefixo b64:.')) ?></p>
		</div>
	</div>
	<div style="margin-top:14px;display:flex;gap:8px;">
		<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Salvar')) ?></button>
		<?= $this->Html->link(__('Cancelar'), ['action' => 'view', 'cofre'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
	<?= $this->Form->end() ?>
</div>
