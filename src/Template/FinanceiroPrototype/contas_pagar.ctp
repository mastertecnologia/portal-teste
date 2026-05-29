<?php
/**
 * Contas a Pagar — pg-contas-pagar.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$f = (array)($cpFiltros ?? []);
$pag = (array)($cpPaginacao ?? []);
$k = (array)($cpKpi ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM Soluções › <?= $this->Html->link(__('Financeiro'), ['action' => 'lista'], ['style' => 'color:var(--teal);']) ?> › <?= h(__('Contas a Pagar')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💸 <?= h(__('Contas a Pagar')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Gestão de obrigações com fornecedores · fluxo de saídas · aprovações de pagamento')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📊 Excel', ['controller' => 'Financeiro', 'action' => 'contasPagar'], ['class' => 'btn btn-ghost btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('📈 ' . __('Fluxo caixa'), ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa'], ['class' => 'btn btn-ghost btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('+ ' . __('Lançar despesa'), ['controller' => 'Financeiro', 'action' => 'addDespesa'], ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Total a pagar')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl((float)($k['total_pagar'] ?? 0))) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= sprintf(h(__('%d títulos abertos')), (int)($k['titulos_abertos'] ?? 0)) ?></div></div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Vencidos')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl((float)($k['vencidos_valor'] ?? 0))) ?></div><div style="font-size:11px;color:#7A1822;"><?= sprintf(h(__('%d títulos')), (int)($k['vencidos_qtd'] ?? 0)) ?></div></div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Vence em 7 dias')) ?></div><div class="val" style="color:#8A4D02;"><?= h($H->brl((float)($k['vence_7d_valor'] ?? 0))) ?></div><div style="font-size:11px;color:#8A4D02;"><?= sprintf(h(__('%d títulos · atenção')), (int)($k['vence_7d_qtd'] ?? 0)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Vence no mês')) ?></div><div class="val" style="color:#0C447C;"><?= h($H->brl((float)($k['vence_mes_valor'] ?? 0))) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= sprintf(h(__('%d títulos restantes')), (int)($k['vence_mes_qtd'] ?? 0)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Aguarda aprovação')) ?></div><div class="val" style="color:#3D2D63;"><?= (int)($k['aguarda_aprov_qtd'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h($H->brl((float)($k['aguarda_aprov_valor'] ?? 0))) ?> <?= h(__('valor')) ?></div></div>
	<div class="summary-card" style="background:var(--teal-light);border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Pago mês')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($k['pago_mes'] ?? 0))) ?></div></div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 14px;">
	<?= $this->Form->create(null, ['type' => 'get', 'style' => 'display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0;']) ?>
		<input type="text" name="q" value="<?= h((string)($f['busca'] ?? '')) ?>" placeholder="🔍 <?= h(__('Buscar fornecedor, NF, código...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
		<select name="status" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
			<option value=""><?= h(__('Todos os status')) ?></option>
			<?php foreach (['aberto', 'pago', 'vencido', 'cancelado'] as $s) : ?>
				<option value="<?= h($s) ?>" <?= (string)($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= h(ucfirst($s)) ?></option>
			<?php endforeach; ?>
		</select>
		<select name="fornecedor" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
			<option value="0"><?= h(__('Todos fornecedores')) ?></option>
			<?php foreach ($cpFornecedores as $fn) : ?>
				<option value="<?= (int)$fn['id'] ?>" <?= (int)($f['fornecedor'] ?? 0) === (int)$fn['id'] ? 'selected' : '' ?>><?= h((string)$fn['nome']) ?></option>
			<?php endforeach; ?>
		</select>
		<select name="centro" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
			<option value="0"><?= h(__('Centro de custo: todos')) ?></option>
			<?php foreach ($cpCentros as $cc) : ?>
				<option value="<?= (int)$cc['id'] ?>" <?= (int)($f['centro'] ?? 0) === (int)$cc['id'] ? 'selected' : '' ?>><?= h((string)$cc['label']) ?></option>
			<?php endforeach; ?>
		</select>
	<?= $this->Form->end() ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead>
				<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Código')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Fornecedor')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Documento')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Categoria')) ?></th>
					<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Valor')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Emissão')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Vencimento')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
					<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($cpItems === []) : ?>
					<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma despesa no escopo.')) ?></td></tr>
				<?php else : foreach ($cpItems as $it) :
					$st = (array)($it['status'] ?? []);
					$rowBg = (string)($st['row_bg'] ?? '');
					$valorColor = (string)($st['valor_color'] ?? '');
				?>
					<tr style="border-bottom:1px solid var(--border-light);<?= $rowBg !== '' ? 'background:' . h($rowBg) . ';' : '' ?>">
						<td style="padding:10px;"><span class="titulo-cod"><?= h((string)$it['codigo']) ?></span></td>
						<td style="padding:10px;font-weight:600;"><?= h((string)$it['fornecedor']) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h((string)$it['documento']) ?></td>
						<td style="padding:10px;font-size:11px;color:var(--text-muted);"><?= h((string)$it['categoria']) ?></td>
						<td style="padding:10px;text-align:right;font-weight:700;<?= $valorColor !== '' ? 'color:' . h($valorColor) . ';' : '' ?>"><?= h($H->brl((float)$it['valor'])) ?></td>
						<td style="padding:10px;font-size:11px;"><?= h($H->dt($it['emissao'])) ?></td>
						<td style="padding:10px;font-size:11px;<?= $valorColor !== '' ? 'color:' . h($valorColor) . ';font-weight:600;' : '' ?>"><?= h((string)$it['vencimento_label']) ?></td>
						<td style="padding:10px;"><?= $H->badge((string)($st['label'] ?? '—'), (string)($st['badge'] ?? 'pendente')) ?></td>
						<td style="padding:10px;text-align:center;">
							<?php if (($st['action'] ?? '') === 'pagar') : ?>
								<?= $this->Html->link('💰 ' . __('Pagar'), (array)$it['pagar_url'], ['class' => (string)($st['action_class'] ?? 'btn btn-red btn-xs'), 'escape' => false]) ?>
							<?php elseif (($st['action'] ?? '') === 'aprovar') : ?>
								<?= $this->Html->link('✓ ' . __('Aprovar'), (array)$it['edit_url'], ['class' => (string)($st['action_class'] ?? 'btn btn-primary btn-xs'), 'escape' => false]) ?>
							<?php else : ?>
								<?= $this->Html->link('📜', (array)$it['edit_url'], ['class' => 'btn btn-ghost btn-xs', 'escape' => false]) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
			<?php if ($cpItems !== []) : ?>
			<tfoot>
				<tr style="background:var(--bg-surface);">
					<td colspan="4" style="padding:12px;text-align:right;font-weight:700;"><?= h(__('Total visível:')) ?></td>
					<td style="padding:12px;text-align:right;font-weight:700;color:#7A1822;"><?= h($H->brl((float)($cpTotalVisivel ?? 0))) ?></td>
					<td colspan="4"></td>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
	</div>
	<div style="padding:10px 14px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;font-size:12px;border-top:1px solid var(--border-light);">
		<span style="color:var(--text-muted);"><?= sprintf(h(__('%d de %d títulos')), (int)($pag['showing'] ?? 0), (int)($pag['total'] ?? 0)) ?></span>
	</div>
</div>
