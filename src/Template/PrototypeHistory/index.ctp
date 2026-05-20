<?php
/**
 * Histórico filtrável de transições (prototype_status_history).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $histRows
 * @var array<int,string> $histUsers
 * @var array<string,string> $histFiltros
 * @var array<string,string> $histTypeOptions
 * @var bool $histTableMissing
 * @var array<int,array{endpoint:string,count:int}> $histApiStats
 */
$H = $this->ErpPrototype;
$f = (array)($histFiltros ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📜 <?= h(__('Histórico de transições')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Orçamentos, OS, tickets e demais mudanças de status registradas nos protótipos.')) ?></div>
	</div>
</div>

<?php if (!empty($histTableMissing)) : ?>
	<div class="alert-box alert-amber"><?= h(__('Tabela prototype_status_history indisponível. Execute a migration ou bin/apply_migration_prototype_status_history.php.')) ?></div>
<?php endif; ?>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:0 0 160px;">
				<label><?= h(__('Tipo')) ?></label>
				<select name="type">
					<?php foreach ($histTypeOptions as $k => $lbl) : ?>
						<option value="<?= h($k) ?>"<?= (string)$f['type'] === (string)$k ? ' selected' : '' ?>><?= h($lbl) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 180px;">
				<label><?= h(__('Usuário')) ?></label>
				<select name="user">
					<option value=""><?= h(__('Todos')) ?></option>
					<?php foreach ($histUsers as $uid => $uname) : ?>
						<option value="<?= (int)$uid ?>"<?= (string)$f['user'] === (string)$uid ? ' selected' : '' ?>><?= h($uname) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 130px;"><label><?= h(__('De')) ?></label><input type="date" name="de" value="<?= h((string)$f['de']) ?>"></div>
			<div class="field" style="flex:0 0 130px;"><label><?= h(__('Até')) ?></label><input type="date" name="ate" value="<?= h((string)$f['ate']) ?>"></div>
			<div class="field" style="flex:1;min-width:200px;">
				<label><?= h(__('Busca')) ?></label>
				<input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="<?= h(__('ID, nota, status, nome…')) ?>">
			</div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'PrototypeHistory', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;" id="pgm-hist-table">
			<thead>
				<tr>
					<th><?= h(__('Quando')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Registro')) ?></th>
					<th><?= h(__('Transição')) ?></th>
					<th><?= h(__('Por')) ?></th>
					<th><?= h(__('Nota')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($histRows === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum registro com os filtros atuais.')) ?></td></tr>
				<?php else : foreach ($histRows as $row) :
					$url = $row['url'] ?? null;
					$href = $url ? $this->Url->build($url) : '';
				?>
					<tr<?= $href !== '' ? ' data-pgm-row-href="' . h($href) . '" tabindex="0"' : '' ?>>
						<td class="mu"><?= h($H->dt($row['created'])) ?></td>
						<td><span class="badge b-arq"><?= h((string)$row['type_label']) ?></span></td>
						<td style="font-family:monospace;font-weight:600;">#<?= (int)$row['source_id'] ?></td>
						<td><?= h((string)$row['from']) ?> → <strong><?= h((string)$row['to']) ?></strong></td>
						<td><?= h((string)$row['user_name']) ?></td>
						<td class="mu" style="max-width:200px;"><?= h(\Cake\Utility\Text::truncate((string)$row['note'], 60, ['ellipsis' => '…'])) ?></td>
						<td class="r"><?php if ($href !== '') : ?><?= $this->Html->link(__('Abrir'), $url, ['class' => 'btn btn-ghost btn-xs']) ?><?php endif; ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php if (!empty($histApiStats)) : ?>
<div class="card" style="margin-top:16px;padding:14px 16px;">
	<h2 style="font-size:14px;font-weight:600;margin:0 0 10px;">📊 <?= h(__('Uso de endpoints AJAX (cache)')) ?></h2>
	<table class="tbl" style="margin:0;">
		<thead><tr><th><?= h(__('Endpoint')) ?></th><th class="r"><?= h(__('Chamadas')) ?></th></tr></thead>
		<tbody>
			<?php foreach ($histApiStats as $s) : ?>
				<tr>
					<td style="font-family:monospace;font-size:11px;"><?= h((string)$s['endpoint']) ?></td>
					<td class="r"><strong><?= (int)$s['count'] ?></strong></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>

<div class="alert-box alert-teal" style="margin-top:14px;">
	⌨️ <?= h(__('Nas listas com linhas clicáveis:')) ?> <code>j</code> / <code>k</code> <?= h(__('navega;')) ?> <code>Enter</code> <?= h(__('abre o registro.')) ?>
</div>
