<?php
/**
 * Histórico de preços — pg-historico-precos.
 *
 * @var array<int,array<string,mixed>> $historicoEventos
 * @var array<string,mixed> $historicoKpi
 * @var string $historicoFiltro
 */
$H = $this->ErpPrototype;
$k = $historicoKpi;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link(__('Tabela de preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['style' => 'color:var(--teal);']) ?>
			› <?= h(__('Histórico')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📜 <?= h(__('Histórico de preços')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Auditoria de alterações · quem · quando · motivo (derivado de atualizações no catálogo)')) ?></div>
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
		<input type="search" name="q" value="<?= h($historicoFiltro) ?>" placeholder="🔍 <?= h(__('Buscar produto, código...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
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
				<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Preço atual')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Motivo')) ?></th>
			</tr></thead>
			<tbody>
				<?php if ($historicoEventos === []) : ?>
					<tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma alteração registrada nos últimos 30 dias (campo modified).')) ?></td></tr>
				<?php else : foreach ($historicoEventos as $ev) :
					/** @var \Cake\I18n\FrozenTime $dt */
					$dt = $ev['data'];
				?>
					<tr style="border-bottom:1px solid var(--border-light);background:#F0FDF4;">
						<td style="padding:10px;font-family:monospace;font-size:11px;"><?= h($dt->format('d/m H:i')) ?></td>
						<td style="padding:10px;"><strong><?= h((string)$ev['codigo']) ?></strong> <?= h(\Cake\Utility\Text::truncate((string)$ev['descricao'], 40)) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h(__('Tabela Padrão')) ?></td>
						<td style="padding:10px;text-align:right;font-weight:700;color:var(--teal-dark);"><?= h($H->brl((float)$ev['preco_novo'])) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h(__('Atualização de preço no catálogo')) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
