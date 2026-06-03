<?php
/**
 * Histórico de preços — pg-historico-precos.
 *
 * @var array<int,array<string,mixed>> $historicoEventos
 * @var array<string,mixed> $historicoKpi
 * @var string $historicoFiltro
 * @var array<int,array<string,mixed>> $historicoTabelas
 * @var string $historicoFiltroTipo
 * @var int $historicoFiltroTabela
 * @var string $historicoDesde
 * @var string $historicoAte
 */
$H = $this->ErpPrototype;
$k = $historicoKpi;
$urlDetalhe = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-preco-detalhe'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link(__('Tabela de preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['style' => 'color:var(--teal);']) ?>
			› <?= h(__('Histórico')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📜 <?= h(__('Histórico de preços')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Auditoria completa de alterações · quem · quando · de quanto para quanto · motivo')) ?></div>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('← ' . __('Tabela atual'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Exportar'), ['controller' => 'ProdutosPrototype', 'action' => 'exportCsv'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Alterações 30d')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$k['alteracoes_30d'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('↑ Aumentos')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$k['aumentos'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('↓ Reduções')) ?></div><div class="val" style="color:#7A1822;"><?= (int)$k['reducoes'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('↔ Promoções (temp.)')) ?></div><div class="val" style="color:#0C447C;"><?= (int)$k['promocoes'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-mid);"><div class="lbl"><?= h(__('Reajuste médio')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h((string)$k['reajuste_medio']) ?></div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Próximo reajuste')) ?></div><div class="val" style="font-size:16px;color:#3D2D63;"><?= h((string)$k['proximo_reajuste']) ?></div></div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 14px;">
	<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;">
		<input type="search" name="q" value="<?= h($historicoFiltro) ?>" placeholder="🔍 <?= h(__('Buscar produto, código, motivo...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
		<select name="tipo" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;">
			<option value="todos"<?= $historicoFiltroTipo === 'todos' ? ' selected' : '' ?>><?= h(__('Tipo: todos')) ?></option>
			<option value="aumento"<?= $historicoFiltroTipo === 'aumento' ? ' selected' : '' ?>><?= h(__('↑ Aumento')) ?></option>
			<option value="reducao"<?= $historicoFiltroTipo === 'reducao' ? ' selected' : '' ?>><?= h(__('↓ Redução')) ?></option>
			<option value="promocao"<?= $historicoFiltroTipo === 'promocao' ? ' selected' : '' ?>><?= h(__('↔ Promoção')) ?></option>
			<option value="massa"<?= $historicoFiltroTipo === 'massa' ? ' selected' : '' ?>><?= h(__('★ Reajuste em massa')) ?></option>
		</select>
		<select name="tabela" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;">
			<option value="0"><?= h(__('Tabela: todas')) ?></option>
			<?php foreach ($historicoTabelas as $tb) :
				$sel = (int)$tb['id'] === (int)$historicoFiltroTabela ? ' selected' : '';
			?>
				<option value="<?= (int)$tb['id'] ?>"<?= $sel ?>><?= h((string)$tb['nome']) ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="desde" value="<?= h($historicoDesde) ?>" style="padding:6px 8px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;"/>
		<input type="date" name="ate" value="<?= h($historicoAte) ?>" style="padding:6px 8px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;"/>
		<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Filtrar')) ?></button>
	</form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead><tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Data/hora')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Produto')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Tabela')) ?></th>
				<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Preço antigo')) ?></th>
				<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">→</th>
				<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Preço novo')) ?></th>
				<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Δ %</th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Motivo')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Autor')) ?></th>
			</tr></thead>
			<tbody>
				<?php if ($historicoEventos === []) : ?>
					<tr><td colspan="9" style="padding:20px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma alteração registrada. Ajustes e reajustes passam a aparecer aqui automaticamente.')) ?></td></tr>
				<?php else : foreach ($historicoEventos as $ev) :
					/** @var \Cake\I18n\FrozenTime $dt */
					$dt = $ev['data'];
					$link = $urlDetalhe + ['?' => ['id' => (int)($ev['id'] ?? 0)]];
					if ((int)($ev['id'] ?? 0) <= 0) {
						$link = null;
					}
					$pct = $ev['variacao_pct'];
					$pctFmt = $pct !== null ? (($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%') : '—';
					$pctColor = $pct !== null && $pct < 0 ? '#7A1822' : 'var(--teal-dark)';
					$setaColor = $ev['seta'] === '↓' ? '#7A1822' : ($ev['seta'] === '★' ? 'var(--teal)' : 'var(--teal-dark)');
				?>
					<tr style="border-bottom:1px solid var(--border-light);background:<?= h((string)$ev['row_bg']) ?>;<?= $link ? 'cursor:pointer;' : '' ?>"
						<?php if ($link) : ?>onclick="window.location='<?= h($this->Url->build($link)) ?>'"<?php endif; ?>>
						<td style="padding:10px;font-family:monospace;font-size:11px;"><?= h($dt->format('d/m H:i')) ?></td>
						<td style="padding:10px;"><strong><?= h((string)$ev['codigo']) ?></strong> <?= h(\Cake\Utility\Text::truncate((string)$ev['descricao'], 36)) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h((string)($ev['tabela'] ?: '—')) ?></td>
						<td style="padding:10px;text-align:right;text-decoration:<?= $ev['preco_anterior'] !== null ? 'line-through' : 'none' ?>;color:var(--text-muted);"><?= $ev['preco_anterior'] !== null ? h($H->brl((float)$ev['preco_anterior'])) : '—' ?></td>
						<td style="padding:10px;text-align:center;font-weight:700;color:<?= h($setaColor) ?>;"><?= h((string)$ev['seta']) ?></td>
						<td style="padding:10px;text-align:right;font-weight:700;color:<?= h($pctColor) ?>;"><?= h($H->brl((float)$ev['preco_novo'])) ?></td>
						<td style="padding:10px;text-align:right;font-weight:600;color:<?= h($pctColor) ?>;"><?= h($pctFmt) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h((string)$ev['motivo']) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h((string)$ev['autor']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
