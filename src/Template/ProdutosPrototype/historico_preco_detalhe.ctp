<?php
/**
 * Detalhe de alteração — pg-historico-preco-detalhe.
 *
 * @var array<string,mixed> $historicoDetalhe
 * @var array<int,array<string,mixed>> $historicoTimeline
 */
$H = $this->ErpPrototype;
$ev = $historicoDetalhe;
$dt = $ev['data'];
$pct = $ev['variacao_pct'];
$pctFmt = $pct !== null ? (($pct >= 0 ? '↑ +' : '↓ ') . number_format(abs($pct), 1, ',', '.') . '%') : '—';
$pctColor = $pct !== null && $pct < 0 ? '#7A1822' : 'var(--teal-dark)';
$tipoLabel = $ev['seta'] === '↑' ? __('↑ Aumento') : ($ev['seta'] === '↓' ? __('↓ Redução') : ($ev['seta'] === '★' ? __('★ Reajuste') : __('↔ Ajuste')));
$custo = $ev['custo_na_epoca'];
$margem = $ev['margem_apos'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link(__('Tabela de Preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['style' => 'color:var(--teal);']) ?>
			› <?= $this->Html->link(__('Histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['style' => 'color:var(--teal);']) ?>
			› <?= h(__('Alteração')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📜 <?= h(__('Detalhe da Alteração de Preço')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h((string)$ev['codigo']) ?> · <?= h(\Cake\Utility\Text::truncate((string)$ev['descricao'], 50)) ?> · <?= h($dt->format('d/m/Y H:i')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?php if ($ev['preco_anterior'] !== null && (int)$ev['id'] > 0) : ?>
			<?= $this->Form->postLink('↺ ' . __('Reverter'), ['controller' => 'ProdutosPrototype', 'action' => 'historicoRevert'], [
				'class' => 'btn btn-ghost btn-sm',
				'data' => ['historico_id' => (int)$ev['id']],
				'confirm' => __('Reverter o preço para {0}? Isso gera uma nova entrada no histórico.', $H->brl((float)$ev['preco_anterior'])),
			]) ?>
		<?php endif; ?>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">💰 <?= h(__('A alteração')) ?></div>
			<div style="display:flex;align-items:center;justify-content:center;gap:20px;padding:18px;background:var(--bg-surface);border-radius:8px;flex-wrap:wrap;">
				<div style="text-align:center;">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Preço antigo')) ?></div>
					<div style="font-size:22px;font-weight:600;text-decoration:line-through;color:var(--text-muted);"><?= $ev['preco_anterior'] !== null ? h($H->brl((float)$ev['preco_anterior'])) : '—' ?></div>
				</div>
				<div style="font-size:24px;color:var(--teal);">→</div>
				<div style="text-align:center;">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Preço novo')) ?></div>
					<div style="font-size:22px;font-weight:600;color:var(--teal-dark);"><?= h($H->brl((float)$ev['preco_novo'])) ?></div>
				</div>
				<div style="text-align:center;padding-left:20px;border-left:1px solid var(--border);">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Variação')) ?></div>
					<div style="font-size:22px;font-weight:600;color:<?= h($pctColor) ?>;"><?= h($pctFmt) ?></div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title">📋 <?= h(__('Contexto completo')) ?></div>
			<div style="font-size:12px;line-height:2;">
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Produto')) ?></span><strong><?= h((string)$ev['codigo']) ?> · <?= h((string)$ev['descricao']) ?></strong></div>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Tabela')) ?></span><strong><?= h((string)($ev['tabela'] ?: '—')) ?></strong></div>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Data/hora')) ?></span><strong><?= h($dt->format('d/m/Y H:i:s')) ?></strong></div>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Autor')) ?></span><strong><?= h((string)$ev['autor']) ?></strong></div>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Tipo')) ?></span><strong><?= h($tipoLabel) ?></strong></div>
				<?php if ($custo !== null && $custo > 0) : ?>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Custo na época')) ?></span><strong><?= h($H->brl($custo)) ?><?= $margem !== null ? ' (' . h(__('margem')) . ' ' . number_format($margem, 1, ',', '.') . '%)' : '' ?></strong></div>
				<?php endif; ?>
				<?php if ($margem !== null) : ?>
				<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Margem após')) ?></span><strong><?= number_format($margem, 1, ',', '.') ?>%</strong></div>
				<?php endif; ?>
				<?php if (!empty($ev['ip_origem'])) : ?>
				<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('IP de origem')) ?></span><strong style="font-family:monospace;"><?= h((string)$ev['ip_origem']) ?></strong></div>
				<?php endif; ?>
			</div>
			<?php if ((string)$ev['motivo'] !== '') : ?>
			<div style="margin-top:10px;padding:10px;background:var(--teal-light);border-radius:6px;font-size:12px;">
				<strong><?= h(__('Motivo declarado:')) ?></strong> <?= h((string)$ev['motivo']) ?>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<div>
		<div class="card">
			<div class="sec-title">🕐 <?= h(__('Linha do tempo do produto')) ?></div>
			<div style="display:flex;flex-direction:column;gap:6px;font-size:11px;">
				<?php if ($historicoTimeline === []) : ?>
					<div style="color:var(--text-muted);padding:8px;"><?= h(__('Sem outras alterações registradas.')) ?></div>
				<?php else : foreach ($historicoTimeline as $i => $t) :
					$destaque = (int)$t['id'] === (int)$ev['id'];
					$bg = $destaque ? 'var(--teal-light)' : 'var(--bg-surface)';
					$border = $destaque ? '2px solid var(--teal)' : 'none';
					$p = $t['variacao_pct'];
					$pLbl = $p !== null ? (($p >= 0 ? '↑ +' : '↓ -') . number_format(abs($p), 1, ',', '.') . '%') : h($t['tipo']);
				?>
					<div style="padding:8px;background:<?= h($bg) ?>;border-radius:6px;border-left:<?= h($border) ?>;">
						<div style="display:flex;justify-content:space-between;">
							<strong><?= h($t['data']->format('d/m')) ?><?= $destaque ? ' (' . h(__('esta')) . ')' : '' ?></strong>
							<span style="color:var(--text-muted);"><?= h($pLbl) ?></span>
						</div>
						<div style="color:var(--text-muted);">
							<?= $t['preco_anterior'] !== null ? h($H->brl((float)$t['preco_anterior'])) . ' → ' : '' ?><?= h($H->brl((float)$t['preco_novo'])) ?>
						</div>
					</div>
				<?php endforeach; endif; ?>
			</div>
		</div>
	</div>
</div>
