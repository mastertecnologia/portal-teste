<?php
/**
 * Detalhe tabela de preços — pg-preco-tabela-detalhe (simulação -12% sobre padrão).
 *
 * @var float $detalheDesconto
 * @var array<int,array<string,mixed>> $detalheProdutos
 * @var int $detalheTotal
 * @var float|null $detalheMargemMedia
 * @var array{inicio:string,fim:string} $detalheVigencia
 */
$H = $this->ErpPrototype;
$vig = $detalheVigencia;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Detalhe')) ?></div>
		<h1 style="font-size:22px;font-weight:600;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;">
			📋 <?= h(__('Tabela Distribuidor (simulação)')) ?>
			<span class="badge b-paga" style="font-size:11px;font-weight:500;">✓ <?= h(__('Vigente')) ?></span>
		</h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf('%d %s · %s', (int)$detalheTotal, h(__('produtos')), h(__('-12% vs tabela padrão (prévia)'))) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Tabelas'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📈 ' . __('Reajustar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-reajuste-massa'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Exportar'), ['controller' => 'ProdutosPrototype', 'action' => 'exportCsv'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Produtos')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$detalheTotal ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Clientes vinculados')) ?></div><div class="val" style="color:#0C447C;">0</div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Desconto médio vs Padrão')) ?></div><div class="val" style="color:#3D2D63;"><?= (int)$detalheDesconto ?>%</div></div>
	<div class="summary-card" style="border-left:3px solid #10B981;"><div class="lbl"><?= h(__('Margem média')) ?></div><div class="val" style="color:#065F46;"><?= $detalheMargemMedia !== null ? h((string)round($detalheMargemMedia)) . '%' : '—' ?></div></div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div class="card">
		<div class="sec-title">📦 <?= h(__('Preços nesta tabela')) ?></div>
		<table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
			<thead><tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
				<th style="padding:8px;text-align:left;"><?= h(__('Produto')) ?></th>
				<th style="padding:8px;text-align:right;"><?= h(__('Padrão')) ?></th>
				<th style="padding:8px;text-align:right;"><?= h(__('Esta tabela')) ?></th>
				<th style="padding:8px;text-align:center;">Δ</th>
				<th style="padding:8px;text-align:center;"><?= h(__('Margem')) ?></th>
				<th></th>
			</tr></thead>
			<tbody>
				<?php foreach ($detalheProdutos as $row) : ?>
					<tr style="border-bottom:1px solid var(--border-light);">
						<td style="padding:8px;"><strong><?= h(\Cake\Utility\Text::truncate((string)$row['descricao'], 40)) ?></strong></td>
						<td style="padding:8px;text-align:right;color:var(--text-muted);"><?= h($H->brl((float)$row['padrao'])) ?></td>
						<td style="padding:8px;text-align:right;font-weight:600;"><?= h($H->brl((float)$row['tabela'])) ?></td>
						<td style="padding:8px;text-align:center;color:#7A1822;"><?= (int)$row['delta_pct'] ?>%</td>
						<td style="padding:8px;text-align:center;"><?= $row['margem'] !== null ? h((string)$row['margem']) . '%' : '—' ?></td>
						<td style="padding:8px;text-align:center;"><?= $this->Html->link('✏', ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-ajuste', '?' => ['id' => (int)$row['id']]], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ($detalheTotal > count($detalheProdutos)) : ?>
					<tr><td style="padding:8px;color:var(--text-muted);">+<?= (int)$detalheTotal - count($detalheProdutos) ?> <?= h(__('produtos...')) ?></td><td colspan="5"></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">⚙ <?= h(__('Configuração')) ?></div>
			<div style="font-size:12px;line-height:1.9;color:var(--text-muted);">
				<div><strong><?= h(__('Base:')) ?></strong> <?= sprintf(h(__('Tabela Padrão -%d%%')), (int)$detalheDesconto) ?></div>
				<div><strong><?= h(__('Vigência:')) ?></strong> <?= h(date('d/m', strtotime($vig['inicio']))) ?> → <?= h(date('d/m/Y', strtotime($vig['fim']))) ?></div>
			</div>
			<?= $this->Html->link('📜 ' . __('Ver histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'width:100%;margin-top:8px;']) ?>
		</div>
	</div>
</div>
