<?php
/**
 * OS · detalhe — mockup pg-os-execucao com dados reais.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $os
 * @var array<int,array<string,mixed>> $osLinhas
 * @var array<int,array<string,mixed>> $osMovs
 * @var float $osTotalItens
 */
$H = $this->ErpPrototype;
$st = strtolower((string)$os['situacao']);
$badge = 'arq';
if (strpos($st, 'concl') !== false || strpos($st, 'fech') !== false) {
	$badge = 'paga';
} elseif (strpos($st, 'execu') !== false) {
	$badge = 'aprov';
} elseif (strpos($st, 'aguard') !== false || strpos($st, 'aprov') !== false) {
	$badge = 'pendente';
}
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Operações · OS')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🛠 <?= h(sprintf('OS-%05d', (int)$os['id'])) ?> · <?= h((string)$os['cliente']) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('Aberta em %s')), h($H->dt($os['abertura'], 'd/m/Y H:i'))) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Lista de OS'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('✍ ' . __('Editar (clássico)'), ['controller' => 'Ordensservico', 'action' => 'view', (int)$os['id']], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Situação')) ?></div><div class="val"><?= $H->badge((string)$os['situacao'] !== '' ? (string)$os['situacao'] : '—', $badge) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Itens')) ?></div><div class="val" style="color:#0C447C;"><?= count($osLinhas) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-dark);"><div class="lbl"><?= h(__('Total da OS')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$os['valortotal'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Movimentações')) ?></div><div class="val" style="color:#8A4D02;"><?= count($osMovs) ?></div></div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Demanda')) ?></div>
		<div style="font-size:13px;line-height:1.6;white-space:pre-wrap;">
			<?= !empty($os['descricao']) ? h((string)$os['descricao']) : h(__('Sem descrição.')) ?>
		</div>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Linha do tempo')) ?></div>
		<?php if ($osMovs === []) : ?>
			<p style="color:var(--text-muted);margin:0;font-size:12px;"><?= h(__('Sem movimentações registradas.')) ?></p>
		<?php else : ?>
			<?php foreach (array_slice($osMovs, 0, 8) as $m) : ?>
				<div class="tl-item">
					<div class="tl-dot" style="background:var(--teal-light);color:var(--teal-dark);">●</div>
					<div class="tl-body">
						<div class="tl-title"><?= h((string)$m['sitantiga']) ?> → <strong><?= h((string)$m['sitnova']) ?></strong></div>
						<div class="tl-sub"><?= h($H->dt($m['data'], 'd/m/Y H:i')) ?> <?= !empty($m['obs']) ? '· ' . h(\Cake\Utility\Text::truncate((string)$m['obs'], 60, ['ellipsis' => '…'])) : '' ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<strong style="font-size:13px;"><?= h(__('Itens / serviços')) ?></strong>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Un')) ?></th>
					<th class="r"><?= h(__('Qtd')) ?></th>
					<th class="r"><?= h(__('Unit.')) ?></th>
					<th class="r"><?= h(__('Subtotal')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($osLinhas === []) : ?>
					<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Sem itens neste OS.')) ?></td></tr>
				<?php else : foreach ($osLinhas as $l) : ?>
					<tr>
						<td><?= h(\Cake\Utility\Text::truncate((string)$l['descricao'], 80, ['ellipsis' => '…'])) ?></td>
						<td class="mu"><?= h((string)$l['unidade']) ?></td>
						<td class="r"><?= number_format((float)$l['qtd'], 2, ',', '.') ?></td>
						<td class="r"><?= h($H->brl((float)$l['vlr'])) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$l['subtotal'])) ?></strong></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php if ($osLinhas !== []) : ?>
		<div class="tot-wrap" style="padding:14px;">
			<div class="tot-inner">
				<div class="tot-l"><span><?= h(__('Total dos itens')) ?></span><span class="g"><?= h($H->brl($osTotalItens)) ?></span></div>
			</div>
		</div>
	<?php endif; ?>
</div>
