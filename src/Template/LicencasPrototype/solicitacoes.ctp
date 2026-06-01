<?php
/** @var array<int,array<string,mixed>> $licSolicitacoes @var array<string,mixed> $licFilters @var array<int,string> $licStatusOpcoes */
$items = (array)($licSolicitacoes ?? []);
$statusLabels = [
	'aberta' => __('Aberta'),
	'em_analise' => __('Em análise'),
	'aprovada' => __('Aprovada'),
	'recusada' => __('Recusada'),
	'cancelada' => __('Cancelada'),
];
?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;">📋 <?= h(__('Solicitações')) ?></h1>
<div class="card" style="margin-bottom:14px;">
	<form method="get">
		<label><?= h(__('Status')) ?></label>
		<select name="status" onchange="this.form.submit()">
			<option value=""><?= h(__('Todos')) ?></option>
			<?php foreach ((array)($licStatusOpcoes ?? []) as $st) : ?>
			<option value="<?= h($st) ?>"<?= ($licFilters['status'] ?? '') === $st ? ' selected' : '' ?>><?= h($statusLabels[$st] ?? $st) ?></option>
			<?php endforeach; ?>
		</select>
	</form>
</div>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th>#</th><th><?= h(__('Cliente')) ?></th><th><?= h(__('Tipo')) ?></th><th><?= h(__('Status')) ?></th><th><?= h(__('Criada')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="6" style="text-align:center;padding:24px;"><?= h(__('Nenhuma solicitação.')) ?></td></tr>
		<?php else : foreach ($items as $s) : ?>
		<tr>
			<td><?= (int)$s['id'] ?></td>
			<td><?= h($s['cliente']) ?></td>
			<td><?= h($s['tipo']) ?></td>
			<td><?= h($statusLabels[$s['status']] ?? $s['status']) ?></td>
			<td><?= h($s['created']) ?></td>
			<td>
				<?= $this->Form->create(null, ['url' => ['action' => 'atualizarSolicitacao'], 'style' => 'display:inline-flex;gap:4px;align-items:center;']) ?>
				<?= $this->Form->hidden('id', ['value' => (int)$s['id']]) ?>
				<select name="status" style="max-width:120px;">
					<?php foreach ((array)($licStatusOpcoes ?? []) as $st) : ?>
					<option value="<?= h($st) ?>"<?= $s['status'] === $st ? ' selected' : '' ?>><?= h($statusLabels[$st] ?? $st) ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn btn-ghost btn-xs"><?= h(__('OK')) ?></button>
				<?= $this->Form->end() ?>
			</td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
