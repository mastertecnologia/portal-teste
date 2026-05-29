<?php
/**
 * Extrato bancário — pg-extrato (pgm_erp_completo.html) com dados reais.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $extKpi
 * @var array<int,array<string,mixed>> $extItems
 * @var array<int,array<string,mixed>> $extContasTabs
 * @var array<string,mixed> $extFiltros
 * @var array<string,mixed> $extPaginacao
 * @var array<string,string> $extCategorias
 */
$H = $this->ErpPrototype;
$kpi = $extKpi ?? [];
$f = $extFiltros ?? [];
$pag = $extPaginacao ?? ['pagina' => 1, 'total_paginas' => 1, 'total' => 0, 'mostrando' => 0];
$tabs = $extContasTabs ?? [];
$categorias = $extCategorias ?? [];
$items = $extItems ?? [];
$abaAtiva = (string)($f['aba'] ?? 'todos');
$bancoAtivo = (int)($f['banco'] ?? 0);

$urlBase = ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'];
$urlExport = ['controller' => 'BancosPrototype', 'action' => 'exportExtratoCsv'];

$buildQuery = static function (array $overrides = []) use ($f): array {
	$q = [
		'de' => (string)($f['de'] ?? ''),
		'ate' => (string)($f['ate'] ?? ''),
		'aba' => (string)($f['aba'] ?? 'todos'),
		'banco' => (int)($f['banco'] ?? 0) ?: null,
		'categoria' => (string)($f['categoria'] ?? '') ?: null,
		'q' => (string)($f['q'] ?? '') ?: null,
		'pagina' => (int)($f['pagina'] ?? 1),
	];
	foreach ($overrides as $k => $v) {
		if ($v === null || $v === '') {
			unset($q[$k]);
		} else {
			$q[$k] = $v;
		}
	}
	if (isset($overrides['pagina']) && (int)$overrides['pagina'] <= 1) {
		unset($q['pagina']);
	}
	if (($q['aba'] ?? '') === 'todos') {
		unset($q['aba']);
	}

	return array_filter($q, static function ($v) {
		return $v !== null && $v !== '';
	});
};

$saldoInicialEm = $kpi['saldo_inicial_em'] ?? null;
$saldoInicialLabel = $saldoInicialEm instanceof \DateTimeInterface
	? $saldoInicialEm->format('d/m/Y') . ' 00:00'
	: '';
$delta = (float)($kpi['delta_periodo'] ?? 0);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Bancos'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['style' => 'color:var(--teal);text-decoration:none;']) ?>
			› <span style="color:var(--teal);"><?= h(__('Extrato')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Extrato bancário')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Visualize entradas e saídas com filtros avançados')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🔄 ' . __('Atualizar'), $urlBase + ['?' => $buildQuery()], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📁 ' . __('Exportar OFX'), $urlExport + ['?' => $buildQuery()], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 PDF/Excel', $urlExport + ['?' => $buildQuery()], ['class' => 'btn btn-blue btn-sm']) ?>
	</div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 14px;">
	<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
		<span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-right:4px;"><?= h(__('Conta:')) ?></span>
		<?php foreach ($tabs as $tab) :
			$tabId = (int)($tab['id'] ?? 0);
			$isActive = $tabId === $bancoAtivo;
			$tabStyle = $tabId > 0 && !empty($tab['bar']) ? 'border-left:3px solid ' . h($tab['bar']) . ';' : '';
		?>
			<?= $this->Html->link(
				h((string)($tab['label'] ?? '')),
				$urlBase + ['?' => $buildQuery(['banco' => $tabId ?: null, 'pagina' => 1])],
				[
					'class' => 'bank-tab' . ($isActive ? ' active' : ''),
					'style' => $tabStyle,
					'escape' => false,
				]
			) ?>
		<?php endforeach; ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="background:var(--teal-light);">
		<div class="lbl"><?= h(__('Saldo inicial')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($kpi['saldo_inicial'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h($saldoInicialLabel) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Total entradas')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($kpi['entradas'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($kpi['mov_entradas'] ?? 0) ?> <?= h(__('movimentações')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('Total saídas')) ?></div>
		<div class="val" style="color:#7A1822;"><?= h($H->brl((float)($kpi['saidas'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($kpi['mov_saidas'] ?? 0) ?> <?= h(__('movimentações')) ?></div>
	</div>
	<div class="summary-card" style="background:linear-gradient(135deg,var(--teal-light),#fff);border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Saldo atual')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($kpi['saldo_atual'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);">
			<?= $delta >= 0 ? '↑' : '↓' ?> <?= h($H->brl(abs($delta))) ?> <?= h(__('no período')) ?>
		</div>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" action="<?= h($this->Url->build($urlBase)) ?>">
		<div style="padding:12px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
			<div class="tabs" style="margin-bottom:0;">
				<?php
				$abas = [
					'todos' => __('Todos'),
					'in' => __('Entradas'),
					'out' => __('Saídas'),
					'pendente' => __('Pendentes'),
				];
				foreach ($abas as $key => $label) :
					$isTab = $abaAtiva === $key || ($abaAtiva === '' && $key === 'todos');
				?>
					<a href="<?= h($this->Url->build($urlBase + ['?' => $buildQuery(['aba' => $key === 'todos' ? null : $key, 'pagina' => 1])])) ?>" class="tab<?= $isTab ? ' active' : '' ?>"><?= h($label) ?></a>
				<?php endforeach; ?>
			</div>
			<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
				<?php if ($bancoAtivo > 0) : ?>
					<input type="hidden" name="banco" value="<?= (int)$bancoAtivo ?>">
				<?php endif; ?>
				<?php if ($abaAtiva !== '' && $abaAtiva !== 'todos') : ?>
					<input type="hidden" name="aba" value="<?= h($abaAtiva) ?>">
				<?php endif; ?>
				<input type="date" name="de" value="<?= h((string)($f['de'] ?? '')) ?>" style="padding:6px 8px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;">
				<span style="font-size:11px;color:var(--text-muted);"><?= h(__('até')) ?></span>
				<input type="date" name="ate" value="<?= h((string)($f['ate'] ?? '')) ?>" style="padding:6px 8px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;">
				<select name="categoria" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;">
					<?php foreach ($categorias as $val => $lbl) : ?>
						<option value="<?= h($val) ?>"<?= (string)($f['categoria'] ?? '') === (string)$val ? ' selected' : '' ?>><?= h($lbl) ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="q" value="<?= h((string)($f['q'] ?? '')) ?>" placeholder="<?= h(__('Buscar...')) ?>" style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;width:140px;">
				<button type="submit" class="btn btn-primary btn-xs"><?= h(__('Filtrar')) ?></button>
			</div>
		</div>
	</form>

	<div>
		<div class="mov-row mov-row-head">
			<span><?= h(__('Data/Hora')) ?></span>
			<span></span>
			<span><?= h(__('Descrição / Histórico')) ?></span>
			<span style="text-align:center;"><?= h(__('Status')) ?></span>
			<span style="text-align:right;"><?= h(__('Valor')) ?></span>
		</div>

		<?php if ($items === []) : ?>
			<div class="mov-row" style="cursor:default;">
				<span colspan="5" style="grid-column:1/-1;text-align:center;padding:24px;color:var(--text-muted);">
					<?= h(__('Nenhum movimento no período/filtros selecionados.')) ?>
				</span>
			</div>
		<?php else : foreach ($items as $it) :
			$icon = (string)($it['icon'] ?? 'in');
			$valorClass = !empty($it['is_transferencia']) ? '' : ($icon === 'in' || !empty($it['is_entrada']) ? 'in' : 'out');
		?>
			<div class="mov-row">
				<span style="font-size:11px;color:var(--text-muted);">
					<strong style="color:var(--text);"><?= h((string)($it['data_dia'] ?? '')) ?></strong>
					<?php if (!empty($it['data_hora'])) : ?><br><?= h((string)$it['data_hora']) ?><?php endif; ?>
				</span>
				<div class="mov-icon <?= h($icon) ?>"><?= h((string)($it['icon_char'] ?? '')) ?></div>
				<div>
					<div style="font-size:13px;font-weight:500;"><?= h((string)($it['titulo'] ?? '')) ?></div>
					<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($it['historico'] ?? '')) ?></div>
				</div>
				<span class="badge b-<?= h((string)($it['status_badge'] ?? 'pendente-conc')) ?>"><?= h((string)($it['status_label'] ?? '')) ?></span>
				<span class="mov-valor <?= h($valorClass) ?>"<?= $valorClass === '' ? ' style="color:var(--text-muted);"' : '' ?>><?= h((string)($it['valor_label'] ?? '')) ?></span>
			</div>
		<?php endforeach; endif; ?>
	</div>

	<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-top:1px solid var(--border);background:var(--bg-surface);font-size:12px;">
		<span style="color:var(--text-muted);">
			<?= sprintf(
				h(__('Mostrando %d de %d movimentações no período')),
				(int)($pag['mostrando'] ?? 0),
				(int)($pag['total'] ?? 0)
			) ?>
		</span>
		<?php if ((int)($pag['total_paginas'] ?? 1) > 1) :
			$pagAtual = (int)($pag['pagina'] ?? 1);
			$totalPag = (int)($pag['total_paginas'] ?? 1);
			$iniPag = max(1, min($pagAtual - 1, max(1, $totalPag - 3)));
			$fimPag = min($totalPag, $iniPag + 3);
		?>
			<div style="display:flex;gap:4px;">
				<?php if ($pagAtual > 1) : ?>
					<?= $this->Html->link('‹', $urlBase + ['?' => $buildQuery(['pagina' => $pagAtual - 1])], ['class' => 'btn btn-ghost btn-xs', 'escape' => false]) ?>
				<?php else : ?>
					<button type="button" class="btn btn-ghost btn-xs" disabled>‹</button>
				<?php endif; ?>
				<?php for ($p = $iniPag; $p <= $fimPag; $p++) : ?>
					<?= $this->Html->link(
						(string)$p,
						$urlBase + ['?' => $buildQuery(['pagina' => $p])],
						['class' => 'btn btn-' . ($p === $pagAtual ? 'primary' : 'ghost') . ' btn-xs']
					) ?>
				<?php endfor; ?>
				<?php if ($pagAtual < $totalPag) : ?>
					<?= $this->Html->link('›', $urlBase + ['?' => $buildQuery(['pagina' => $pagAtual + 1])], ['class' => 'btn btn-ghost btn-xs', 'escape' => false]) ?>
				<?php else : ?>
					<button type="button" class="btn btn-ghost btn-xs" disabled>›</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
