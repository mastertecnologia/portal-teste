<?php
/**
 * Transferências / PIX (via remessas bancárias) — mockup pg-transferencias.
 *
 * @var \App\View\AppView $this
 * @var array{geradas:int,enviadas:int,processadas:int,valor_total:float} $tfKpi
 * @var array<int,array<string,mixed>> $tfItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Bancos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔄 <?= h(__('Transferências / PIX / Remessas')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d remessas geradas · total %s')), count($tfItems), $H->brl((float)$tfKpi['valor_total'])) ?></div>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova remessa (clássico)'), ['controller' => 'Remessas', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Geradas')) ?></div><div class="stat-n"><?= (int)$tfKpi['geradas'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Enviadas')) ?></div><div class="stat-n"><?= (int)$tfKpi['enviadas'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Processadas')) ?></div><div class="stat-n"><?= (int)$tfKpi['processadas'] ?></div></div>
</div>

<div class="alert-box alert-blue">
	<strong>PIX:</strong>
	<?= h(__('o portal hoje emite ordens via arquivos CNAB (financeiro_remessas). Para PIX direto via API bancária, integração é roadmap.')) ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th>#</th>
					<th><?= h(__('CNAB')) ?></th>
					<th><?= h(__('Arquivo')) ?></th>
					<th><?= h(__('Gerada em')) ?></th>
					<th class="r"><?= h(__('Títulos')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Status')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($tfItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma remessa gerada.')) ?></td></tr>
				<?php else : foreach ($tfItems as $r) :
					$st = strtolower((string)$r['status']);
					$badge = 'pendente';
					if (strpos($st, 'process') !== false || strpos($st, 'retorn') !== false) {
						$badge = 'paga';
					} elseif (strpos($st, 'envi') !== false) {
						$badge = 'aprov';
					}
				?>
					<tr>
						<td><strong><?= h(sprintf('REM-%05d', (int)$r['numero'])) ?></strong></td>
						<td><span class="badge b-aprov" style="font-size:9px;"><?= h((string)$r['cnab']) ?></span></td>
						<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h(\Cake\Utility\Text::truncate((string)$r['arquivo'], 32, ['ellipsis' => '…'])) ?></td>
						<td class="mu"><?= h($H->dt($r['data'], 'd/m/Y H:i')) ?></td>
						<td class="r"><?= (int)$r['titulos'] ?></td>
						<td class="r"><strong><?= h($H->brl((float)$r['valor'])) ?></strong></td>
						<td><?= $H->badge((string)$r['status'] !== '' ? (string)$r['status'] : '—', $badge) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
