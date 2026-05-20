<?php
/**
 * Histórico CSAT/NPS com filtros.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $csatItens
 * @var array{total:int,csat_media:float,promotores:int,neutros:int,detratores:int,nps:int} $csatKpi
 * @var array{mes:string,min_csat:int,nps:bool,q:string} $csatFiltros
 */
$f = (array)$csatFiltros;
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Satisfação')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h(__('Histórico CSAT & NPS')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d respostas no escopo do filtro')), (int)$csatKpi['total']) ?></div>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link('📥 ' . __('Exportar CSV'), ['controller' => 'ServicedeskPrototype', 'action' => 'csatExportCsv', '?' => array_filter($f)], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('← ' . __('Resumo CSAT'), ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'csat'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>

	<div class="summary-grid" style="margin-bottom:14px;">
		<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Respostas')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$csatKpi['total'] ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('CSAT médio')) ?></div><div class="val" style="color:#8A4D02;"><?= number_format((float)$csatKpi['csat_media'], 2, ',', '.') ?> ⭐</div></div>
		<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('NPS')) ?></div><div class="val" style="color:#0C447C;"><?= (int)$csatKpi['nps'] ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--teal-dark);"><div class="lbl">🟢 <?= h(__('Promotores')) ?></div><div class="val"><?= (int)$csatKpi['promotores'] ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl">🟡 <?= h(__('Neutros')) ?></div><div class="val"><?= (int)$csatKpi['neutros'] ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl">🔴 <?= h(__('Detratores')) ?></div><div class="val"><?= (int)$csatKpi['detratores'] ?></div></div>
	</div>

	<div class="card" style="padding:0;overflow:hidden;">
		<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
			<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
				<div class="field" style="flex:0 0 140px;"><label><?= h(__('Mês')) ?></label><input type="month" name="mes" value="<?= h((string)$f['mes']) ?>"></div>
				<div class="field" style="flex:0 0 150px;">
					<label><?= h(__('CSAT mínimo')) ?></label>
					<select name="min_csat">
						<option value="0"><?= h(__('Todos')) ?></option>
						<?php foreach ([1, 2, 3, 4, 5] as $n) : ?>
							<option value="<?= $n ?>"<?= (int)$f['min_csat'] === $n ? ' selected' : '' ?>><?= str_repeat('★', $n) ?> e acima</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field" style="flex:0 0 150px;">
					<label><?= h(__('Apenas com NPS')) ?></label>
					<select name="nps">
						<option value="0"><?= h(__('Todos')) ?></option>
						<option value="1"<?= !empty($f['nps']) ? ' selected' : '' ?>><?= h(__('Sim')) ?></option>
					</select>
				</div>
				<div class="field" style="flex:1;min-width:180px;"><label><?= h(__('Buscar em comentário')) ?></label><input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="<?= h(__('Texto...')) ?>"></div>
				<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
				<?= $this->Html->link(__('Limpar'), ['controller' => 'ServicedeskPrototype', 'action' => 'csatHistorico'], ['class' => 'btn btn-ghost btn-sm']) ?>
			</div>
		</form>
		<div style="overflow-x:auto;">
			<table class="tbl" style="margin:0;">
				<thead>
					<tr><th><?= h(__('Quando')) ?></th><th><?= h(__('Ticket')) ?></th><th><?= h(__('Cliente')) ?></th><th><?= h(__('CSAT')) ?></th><th><?= h(__('NPS')) ?></th><th><?= h(__('Comentário')) ?></th></tr>
				</thead>
				<tbody>
					<?php if ($csatItens === []) : ?>
						<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma resposta no escopo do filtro.')) ?></td></tr>
					<?php else : foreach ($csatItens as $r) : ?>
						<tr>
							<td class="mu"><?= h($r['data'] instanceof \DateTimeInterface ? $r['data']->format('d/m/Y H:i') : '') ?></td>
							<td><?= $this->Html->link('#' . (int)$r['ticket_id'], ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)$r['ticket_id']], ['style' => 'font-weight:600;']) ?></td>
							<td><?= h((string)$r['cliente']) ?></td>
							<td><span style="color:#E9A025;font-size:14px;"><?= str_repeat('★', (int)$r['csat']) ?><span style="color:#ddd;"><?= str_repeat('★', 5 - (int)$r['csat']) ?></span></span></td>
							<td>
								<?php if ($r['nps'] !== null) :
									$n = (int)$r['nps'];
									$cls = $n >= 9 ? 'b-paga' : ($n <= 6 ? 'b-recus' : 'b-pendente');
								?>
									<span class="badge <?= $cls ?>" style="font-size:10px;"><?= $n ?></span>
								<?php else : ?>—<?php endif; ?>
							</td>
							<td style="font-size:11px;color:var(--text-muted);max-width:400px;"><?= h(\Cake\Utility\Text::truncate((string)$r['comentario'], 100, ['ellipsis' => '…'])) ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
