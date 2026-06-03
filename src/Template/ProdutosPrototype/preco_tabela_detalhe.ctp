<?php
/**
 * Detalhe tabela de preços — pg-preco-tabela-detalhe.
 *
 * @var array<string,mixed> $detalheTabela
 * @var int $detalheTabelaId
 * @var int $detalheDesconto
 * @var int $detalheDescontoSinal
 * @var array<int,array<string,mixed>> $detalheProdutos
 * @var int $detalheTotal
 * @var float|null $detalheMargemMedia
 * @var array{inicio:string,fim:string} $detalheVigencia
 * @var string $detalheBusca
 */
$H = $this->ErpPrototype;
$tb = $detalheTabela;
$vig = $detalheVigencia;
$busca = (string)$detalheBusca;
$urlBase = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-detalhe', '?' => ['tabela' => (int)$detalheTabelaId]];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › {0}', (string)$tb['nome'])) ?></div>
		<h1 style="font-size:22px;font-weight:600;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;">
			📋 <?= h((string)$tb['nome']) ?>
			<?php if (!empty($tb['vigente'])) : ?>
				<span class="badge b-paga" style="font-size:11px;font-weight:500;">✓ <?= h(__('Vigente')) ?></span>
			<?php endif; ?>
		</h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf('%d %s · %s', (int)$detalheTotal, h(__('produtos')), h((string)($tb['vigencia_label'] ?? ''))) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Tabelas'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabelas'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📈 ' . __('Reajustar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-reajuste-massa', '?' => ['tabela' => (int)$detalheTabelaId]], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Exportar'), ['controller' => 'ProdutosPrototype', 'action' => 'exportCsv'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Produtos')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$detalheTotal ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Clientes vinculados')) ?></div><div class="val" style="color:#0C447C;">0</div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Desconto médio vs Padrão')) ?></div><div class="val" style="color:#3D2D63;"><?= (int)abs($detalheDescontoSinal) ?>%</div></div>
	<div class="summary-card" style="border-left:3px solid #10B981;"><div class="lbl"><?= h(__('Margem média')) ?></div><div class="val" style="color:#065F46;"><?= $detalheMargemMedia !== null ? h((string)round($detalheMargemMedia)) . '%' : '—' ?></div></div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div class="card">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
			<div class="sec-title" style="margin:0;">📦 <?= h(__('Preços nesta tabela')) ?></div>
			<form method="get" action="<?= h($this->Url->build($urlBase)) ?>" style="margin:0;">
				<input type="hidden" name="tabela" value="<?= (int)$detalheTabelaId ?>">
				<input type="text" name="q" value="<?= h($busca) ?>" placeholder="<?= h(__('🔍 Buscar produto...')) ?>" style="padding:5px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;"/>
			</form>
		</div>
		<div style="overflow-x:auto;max-height:70vh;overflow-y:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead><tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1;">
					<th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);font-weight:600;"><?= h(__('Produto')) ?></th>
					<th style="padding:8px;text-align:right;font-size:11px;color:var(--text-muted);font-weight:600;"><?= h(__('Padrão')) ?></th>
					<th style="padding:8px;text-align:right;font-size:11px;color:var(--text-muted);font-weight:600;"><?= h(__('Esta tabela')) ?></th>
					<th style="padding:8px;text-align:center;font-size:11px;color:var(--text-muted);font-weight:600;">Δ</th>
					<th style="padding:8px;text-align:center;font-size:11px;color:var(--text-muted);font-weight:600;"><?= h(__('Margem')) ?></th>
					<th style="padding:8px;text-align:center;"></th>
				</tr></thead>
				<tbody>
					<?php foreach ($detalheProdutos as $row) :
						$delta = (int)$row['delta_pct'];
						$deltaColor = $delta < 0 ? '#7A1822' : ($delta > 0 ? 'var(--teal-dark)' : 'var(--text-muted)');
						$ajusteQ = ['id' => (int)$row['id'], 'tabela' => (int)$detalheTabelaId];
					?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:8px;"><strong><?= h((string)$row['codigo']) ?></strong> · <?= h(\Cake\Utility\Text::truncate((string)$row['descricao'], 48)) ?></td>
							<td style="padding:8px;text-align:right;color:var(--text-muted);"><?= h($H->brl((float)$row['padrao'])) ?></td>
							<td style="padding:8px;text-align:right;font-weight:600;"><?= h($H->brl((float)$row['tabela'])) ?></td>
							<td style="padding:8px;text-align:center;color:<?= h($deltaColor) ?>;"><?= $delta > 0 ? '+' : '' ?><?= $delta ?>%</td>
							<td style="padding:8px;text-align:center;"><?= $row['margem'] !== null ? h((string)$row['margem']) . '%' : '—' ?></td>
							<td style="padding:8px;text-align:center;">
								<?php if ((int)$row['id'] > 0) : ?>
									<?= $this->Html->link('✏', ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-ajuste', '?' => $ajusteQ], ['class' => 'btn btn-ghost btn-xs']) ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ($detalheProdutos === []) : ?>
						<tr><td colspan="6" style="padding:16px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum item nesta tabela.')) ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<div>
		<div class="card">
			<div class="sec-title">⚙ <?= h(__('Configuração')) ?></div>
			<div style="font-size:12px;line-height:1.9;color:var(--text-muted);">
				<div><strong><?= h(__('Código:')) ?></strong> <?= h((string)$tb['codigo']) ?></div>
				<div><strong><?= h(__('Vigência:')) ?></strong> <?= h(date('d/m', strtotime($vig['inicio']))) ?> → <?= h(date('d/m/Y', strtotime($vig['fim']))) ?></div>
				<div><strong><?= h(__('Itens:')) ?></strong> <?= (int)$detalheTotal ?></div>
			</div>
			<?= $this->Html->link('📜 ' . __('Ver histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'width:100%;margin-top:8px;']) ?>
		</div>
	</div>
</div>
