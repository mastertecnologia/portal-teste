<?php
/**
 * NF-e / NFS-e — mockup pg-nfe.
 *
 * @var \App\View\AppView $this
 * @var array{emitidas:int,autorizadas:int,rejeitadas:int,canceladas:int,valor_total:float} $nfeKpi
 * @var array<int,array<string,mixed>> $nfeItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Financeiro · Fiscal')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🧾 <?= h(__('NF-e / NFS-e')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('Últimas %d notas · total emitido %s')), count($nfeItems), $H->brl((float)$nfeKpi['valor_total'])) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'FinanceiroPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Emitir nota (clássico)'), ['controller' => 'FiscalNotas', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Emitidas')) ?></div><div class="stat-n"><?= (int)$nfeKpi['emitidas'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Autorizadas')) ?></div><div class="stat-n"><?= (int)$nfeKpi['autorizadas'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Rejeitadas')) ?></div><div class="stat-n"><?= (int)$nfeKpi['rejeitadas'] ?></div></div>
	<div class="stat" style="--sc:var(--gray-400);"><div class="stat-l"><?= h(__('Canceladas')) ?></div><div class="stat-n"><?= (int)$nfeKpi['canceladas'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<input type="text" placeholder="🔍 <?= h(__('Buscar por número, chave, destinatário...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Nº / Série')) ?></th>
					<th><?= h(__('Modelo')) ?></th>
					<th><?= h(__('Emissão')) ?></th>
					<th><?= h(__('Chave')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($nfeItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma nota emitida no escopo.')) ?></td></tr>
				<?php else : foreach ($nfeItems as $n) :
					$st = strtolower((string)$n['status']);
					$badge = 'arq';
					$lbl = (string)$n['status'] ?: '—';
					if (strpos($st, 'autoriz') !== false) {
						$badge = 'paga';
					} elseif (strpos($st, 'rejeit') !== false) {
						$badge = 'recus';
					} elseif (strpos($st, 'cancel') !== false) {
						$badge = 'arq';
					} elseif (strpos($st, 'pend') !== false || strpos($st, 'process') !== false) {
						$badge = 'pendente';
					}
					$chaveTruncada = (string)$n['chave'] !== '' ? substr((string)$n['chave'], -10) : '—';
				?>
					<tr>
						<td><strong><?= h((string)$n['numero']) ?></strong><?php if (!empty($n['serie'])) : ?> <span style="font-size:11px;color:var(--text-muted);">/<?= h((string)$n['serie']) ?></span><?php endif; ?></td>
						<td class="mu"><?= h((string)$n['modelo']) ?></td>
						<td class="mu"><?= h($H->dt($n['emissao'])) ?></td>
						<td style="font-family:monospace;font-size:11px;color:var(--text-muted);" title="<?= h((string)$n['chave']) ?>">…<?= h($chaveTruncada) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$n['valor'])) ?></strong></td>
						<td>
							<?= $H->badge($lbl, $badge) ?>
							<?php if (!empty($n['motivo_rejeicao'])) : ?>
								<div style="font-size:10px;color:#7A1822;margin-top:2px;"><?= h(\Cake\Utility\Text::truncate((string)$n['motivo_rejeicao'], 60, ['ellipsis' => '…'])) ?></div>
							<?php endif; ?>
						</td>
						<td class="r"><?= $this->Html->link(__('Ver'), ['controller' => 'FiscalNotas', 'action' => 'view', (int)$n['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
