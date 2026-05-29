<?php
/**
 * Contas a Receber — pg-titulos.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $crKpi
 * @var array<int,array<string,mixed>> $crItems
 * @var array<string,mixed> $crFiltros
 * @var array<string,mixed> $crPaginacao
 * @var array<int,array{id:int,nome:string}> $crClientes
 * @var array<int,array{id:int,label:string}> $crBancos
 * @var \Cake\I18n\Time $crAtualizado
 */
$H = $this->ErpPrototype;
$f = (array)($crFiltros ?? []);
$tab = (string)($f['tab'] ?? 'todos');
$pag = (array)($crPaginacao ?? []);
$pend = (int)($crKpi['pendentes'] ?? 0);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link('← ' . __('Financeiro'), ['action' => 'lista'], ['style' => 'color:var(--teal);']) ?>
			› <span style="color:var(--teal);"><?= h(__('Contas a Receber')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Contas a Receber · Títulos')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= sprintf(
				h(__('%d títulos pendentes · %s a receber · Atualizado %s')),
				$pend,
				$H->brl((float)($crKpi['total_receber'] ?? 0)),
				h($H->dt($crAtualizado ?? null, 'd/m/Y H:i'))
			) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📤 ' . __('Remessa CNAB'), ['controller' => 'BancosPrototype', 'action' => 'remessa'], ['class' => 'btn btn-ghost btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('📥 ' . __('Importar retorno'), ['controller' => 'BancosPrototype', 'action' => 'retorno'], ['class' => 'btn btn-blue btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('📊 Excel', ['controller' => 'Financeiro', 'action' => 'contasReceber', '?' => ['export' => 'excel']], ['class' => 'btn btn-ghost btn-sm', 'escape' => false]) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card"><div class="lbl"><?= h(__('Total a receber')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($crKpi['total_receber'] ?? 0))) ?></div></div>
	<div class="summary-card"><div class="lbl"><?= h(__('Vence em 30 dias')) ?></div><div class="val" style="color:#0C447C;"><?= h($H->brl((float)($crKpi['vence_30d'] ?? 0))) ?></div></div>
	<div class="summary-card" style="background:#FAEEDA;"><div class="lbl"><?= h(__('Vencendo (≤7d)')) ?></div><div class="val" style="color:#8A4D02;"><?= h($H->brl((float)($crKpi['vencendo_7d'] ?? 0))) ?></div></div>
	<div class="summary-card" style="background:#F8D8DA;"><div class="lbl"><?= h(__('Em atraso')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl((float)($crKpi['em_atraso'] ?? 0))) ?></div></div>
	<div class="summary-card" style="background:var(--teal-light);"><div class="lbl"><?= h(__('Pago no mês')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($crKpi['pago_mes'] ?? 0))) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);flex-wrap:wrap;gap:8px;">
		<div class="tabs">
			<?php
			$tabs = ['todos' => __('Todos'), 'vencendo' => __('Vencendo'), 'atraso' => __('Em atraso'), 'pago' => __('Pagos')];
			foreach ($tabs as $key => $lbl) :
				$url = ['action' => 'titulos', '?' => array_merge($f, ['tab' => $key, 'page' => 1])];
				unset($url['?']['page']);
				$url['?']['tab'] = $key;
			?>
				<?= $this->Html->link($lbl, $url, ['class' => 'tab' . ($tab === $key ? ' active' : ''), 'escape' => false]) ?>
			<?php endforeach; ?>
		</div>
		<?= $this->Form->create(null, ['type' => 'get', 'style' => 'display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0;']) ?>
			<input type="hidden" name="tab" value="<?= h($tab) ?>"/>
			<select name="cliente" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
				<option value="0"><?= h(__('Todos os clientes')) ?></option>
				<?php foreach ($crClientes as $c) : ?>
					<option value="<?= (int)$c['id'] ?>" <?= (int)($f['cliente'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h((string)$c['nome']) ?></option>
				<?php endforeach; ?>
			</select>
			<select name="banco" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
				<option value="0"><?= h(__('Todos os bancos')) ?></option>
				<?php foreach ($crBancos as $b) : ?>
					<option value="<?= (int)$b['id'] ?>" <?= (int)($f['banco'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= h((string)$b['label']) ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" name="q" value="<?= h((string)($f['busca'] ?? '')) ?>" placeholder="<?= h(__('Buscar título...')) ?>" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;width:160px;"/>
		<?= $this->Form->end() ?>
	</div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr>
				<th style="width:130px;"><?= h(__('Título')) ?></th>
				<th><?= h(__('Cliente')) ?></th>
				<th style="width:90px;"><?= h(__('Origem')) ?></th>
				<th style="width:90px;"><?= h(__('Parcela')) ?></th>
				<th style="width:110px;"><?= h(__('Vencimento')) ?></th>
				<th class="r" style="width:100px;"><?= h(__('Valor')) ?></th>
				<th style="width:120px;"><?= h(__('Banco')) ?></th>
				<th style="width:110px;"><?= h(__('Status')) ?></th>
				<th style="width:90px;"><?= h(__('Ações')) ?></th>
			</tr></thead>
			<tbody>
				<?php if ($crItems === []) : ?>
					<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum título no escopo.')) ?></td></tr>
				<?php else : foreach ($crItems as $it) :
					$st = (array)($it['status'] ?? []);
					$rowBg = (string)($st['row_bg'] ?? '');
				?>
					<tr style="cursor:pointer;<?= $rowBg !== '' ? 'background:' . h($rowBg) . ';' : '' ?>">
						<td><span class="titulo-cod"><?= h((string)$it['codigo']) ?></span></td>
						<td>
							<div style="font-weight:500;font-size:13px;"><?= h((string)$it['cliente_nome']) ?></div>
							<?php if (!empty($it['cliente_cnpj'])) : ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['cliente_cnpj']) ?></div>
							<?php endif; ?>
						</td>
						<td>
							<?php if (!empty($it['origem_url'])) : ?>
								<?= $this->Html->link(
									$H->badge((string)$it['origem_label'], (string)$it['origem_badge']),
									(array)$it['origem_url'],
									['escape' => false, 'style' => 'text-decoration:none;']
								) ?>
							<?php else : ?>
								<?= $H->badge((string)$it['origem_label'], (string)$it['origem_badge']) ?>
							<?php endif; ?>
						</td>
						<td><span class="titulo-num"><?= h((string)$it['parcela']) ?></span></td>
						<td><?= h($H->dt($it['vencimento'])) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['valor'])) ?></strong></td>
						<td style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['banco_label']) ?></td>
						<td><?= $H->badge((string)($st['label'] ?? '—'), (string)($st['badge'] ?? 'pendente')) ?></td>
						<td>
							<?php if (($st['state'] ?? '') === 'pago') : ?>
								<?= $this->Html->link('📄', (array)$it['fatura_url'], ['class' => 'btn btn-ghost btn-xs', 'escape' => false, 'title' => __('Comprovante')]) ?>
							<?php else : ?>
								<?= $this->Html->link('💰', (array)$it['fatura_url'], ['class' => 'btn btn-primary btn-xs', 'escape' => false, 'title' => __('Baixa')]) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-top:1px solid var(--border);background:var(--bg-surface);font-size:12px;flex-wrap:wrap;gap:8px;">
		<span style="color:var(--text-muted);">
			<?= sprintf(h(__('Mostrando %d de %d títulos')), (int)($pag['showing'] ?? 0), (int)($pag['total'] ?? 0)) ?>
		</span>
		<?php if ((int)($pag['pages'] ?? 1) > 1) : ?>
			<div style="display:flex;gap:4px;">
				<?php
				$p = (int)($pag['page'] ?? 1);
				$pages = (int)($pag['pages'] ?? 1);
				if ($p > 1) {
					echo $this->Html->link('‹ ' . __('Anterior'), ['action' => 'titulos', '?' => array_merge($f, ['page' => $p - 1])], ['class' => 'btn btn-ghost btn-xs']);
				}
				for ($i = max(1, $p - 1); $i <= min($pages, $p + 1); $i++) {
					echo $this->Html->link((string)$i, ['action' => 'titulos', '?' => array_merge($f, ['page' => $i])], [
						'class' => 'btn btn-xs ' . ($i === $p ? 'btn-primary' : 'btn-ghost'),
					]);
				}
				if ($p < $pages) {
					echo $this->Html->link(__('Próxima') . ' ›', ['action' => 'titulos', '?' => array_merge($f, ['page' => $p + 1])], ['class' => 'btn btn-ghost btn-xs']);
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
