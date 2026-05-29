<?php
/**
 * Conciliação Bancária — pg-conciliacao (pgm_erp_completo.html) com dados reais.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $concKpi
 * @var array<int,array<string,mixed>> $concItems
 * @var array<string,string> $concMeta
 */
$H = $this->ErpPrototype;
$kpi = $concKpi ?? [];
$items = $concItems ?? [];
$meta = $concMeta ?? [];
$csrf = (string)$this->request->getAttribute('csrfToken');
$urlConc = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'conciliar']);
$urlRej = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'rejeitarMatch']);
$urlIgn = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'ignorarExtrato']);
$urlAuto = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'conciliarAutomatico']);
$urlImport = $this->Url->build(['controller' => 'Financeiro', 'action' => 'conciliacao']);
$urlLista = ['controller' => 'BancosPrototype', 'action' => 'lista'];
$contaLabel = (string)($meta['conta_label'] ?? __('Todas as contas'));
$periodoLabel = (string)($meta['periodo_label'] ?? '');
$colExtrato = (string)($meta['coluna_extrato'] ?? __('Extrato bancário'));
$pct = (int)($kpi['pct_conciliados'] ?? 0);
$saldoOk = ((int)($kpi['divergentes'] ?? 0) === 0);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Bancos'), $urlLista, ['style' => 'color:var(--teal);text-decoration:none;']) ?>
			› <span style="color:var(--teal);"><?= h(__('Conciliação')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Conciliação Bancária')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= h(__('Match entre extrato bancário e lançamentos do sistema')) ?> · <?= h($contaLabel) ?><?= $periodoLabel !== '' ? ' · ' . h($periodoLabel) : '' ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📁 ' . __('Importar OFX'), $urlImport, ['class' => 'btn btn-ghost btn-sm']) ?>
		<form method="post" action="<?= h($urlAuto) ?>" style="margin:0;" onsubmit="return confirm('<?= h(__('Conciliar automaticamente todos os matches com score ≥ 90%?')) ?>')">
			<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
			<button type="submit" class="btn btn-blue btn-sm">⚡ <?= h(__('Match automático')) ?></button>
		</form>
		<?= $this->Html->link('✓ ' . __('Finalizar conciliação'), $urlLista, ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="alert-box alert-blue">
	<strong>🔗 <?= h(__('Como funciona:')) ?></strong>
	<?= h(__('O sistema compara cada movimentação do extrato bancário (esquerda) com lançamentos do ERP (direita). Itens com match exato são marcados em verde; divergências em vermelho. Você pode aprovar manualmente, criar novo lançamento ou ajustar valores.')) ?>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="background:var(--teal-light);border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Conciliados')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($kpi['conciliados'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);"><?= $pct ?>% · <?= h($H->brl((float)($kpi['valor_conciliados'] ?? 0))) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Pendentes')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($kpi['pendentes'] ?? 0) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= h($H->brl((float)($kpi['valor_pendentes'] ?? 0))) ?></div>
	</div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('Divergências')) ?></div>
		<div class="val" style="color:#7A1822;"><?= (int)($kpi['divergentes'] ?? 0) ?></div>
		<div style="font-size:11px;color:#7A1822;"><?= h($H->brl((float)($kpi['valor_divergentes'] ?? 0))) ?></div>
	</div>
	<div class="summary-card">
		<div class="lbl"><?= h(__('Saldo extrato')) ?></div>
		<div class="val" style="color:var(--text);"><?= h($H->brl((float)($kpi['saldo_extrato'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);"><?= $saldoOk ? h(__('= sistema (sem divergência)')) : h(__('com divergências pendentes')) ?></div>
	</div>
</div>

<div class="conc-col-header">
	<div>📥 <?= h($colExtrato) ?></div>
	<div style="text-align:center;"><?= h(__('Match')) ?></div>
	<div>💼 <?= h(__('Lançamento no sistema')) ?></div>
</div>

<div class="conc-list-wrap">
	<?php if ($items === []) : ?>
		<div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px;">
			<?= h(__('Sem movimentos no extrato. Importe um arquivo OFX para iniciar a conciliação.')) ?>
		</div>
	<?php else : foreach ($items as $it) :
		$st = (string)($it['status'] ?? '');
		$rowClass = trim('match-row ' . (string)($it['row_class'] ?? ''));
		$rowBg = (string)($it['row_bg'] ?? '');
		$ext = (array)($it['extrato'] ?? []);
		$lan = (array)($it['lancamento'] ?? []);
		$panel = (array)($it['panel'] ?? []);
		$match = (array)($it['match'] ?? []);
		$arrowColor = (string)($it['arrow_color'] ?? 'var(--text-muted)');
	?>
		<div class="<?= h($rowClass) ?>"<?= $rowBg !== '' ? ' style="background:' . h($rowBg) . ';"' : '' ?>>
			<div class="match-side" style="background:#fff;border-left:3px solid <?= h((string)($ext['border'] ?? 'var(--teal)')) ?>;">
				<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($ext['meta'] ?? '')) ?></div>
				<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= h((string)($ext['titulo'] ?? '')) ?></div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($ext['subtitulo'] ?? '')) ?></div>
				<div style="font-size:14px;font-weight:700;color:<?= !empty($ext['is_entrada']) ? 'var(--teal-dark)' : '#7A1822' ?>;margin-top:4px;"><?= h((string)($ext['valor_label'] ?? '')) ?></div>
			</div>
			<div class="match-arrow" style="color:<?= h($arrowColor) ?>;"><?= h((string)($it['arrow'] ?? '→')) ?></div>
			<?php if ($st === 'matched' && $lan !== []) : ?>
				<div class="match-side" style="background:#fff;border-left:3px solid <?= h((string)($lan['border'] ?? 'var(--teal)')) ?>;">
					<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($lan['meta'] ?? '')) ?></div>
					<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= h((string)($lan['titulo'] ?? '')) ?></div>
					<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($lan['subtitulo'] ?? '')) ?></div>
					<div style="font-size:14px;font-weight:700;color:<?= !empty($lan['is_entrada']) ? 'var(--teal-dark)' : '#7A1822' ?>;margin-top:4px;"><?= h((string)($lan['valor_label'] ?? '')) ?></div>
				</div>
			<?php elseif ($panel !== []) : ?>
				<div class="match-side" style="background:<?= h((string)($panel['bg'] ?? '#FAEEDA')) ?>;border-left:3px solid <?= h((string)($panel['border'] ?? 'var(--amber)')) ?>;<?= ($panel['type'] ?? '') === 'aguardando' ? 'text-align:center;padding:14px;' : '' ?>">
					<div style="font-size:13px;font-weight:600;color:<?= ($panel['type'] ?? '') === 'divergente' ? '#7A1822' : '#8A4D02' ?>;"><?= h((string)($panel['titulo'] ?? '')) ?></div>
					<div style="font-size:11px;color:<?= ($panel['type'] ?? '') === 'divergente' ? '#7A1822' : '#8A4D02' ?>;margin-top:4px;"><?= h((string)($panel['texto'] ?? '')) ?></div>
					<?php if (($panel['type'] ?? '') === 'aguardando' && !empty($panel['lancamento_id'])) : ?>
						<form method="post" action="<?= h($urlConc) ?>" style="margin:8px 0 0;" onsubmit="return confirm('<?= h(__('Confirmar match sugerido?')) ?>')">
							<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
							<input type="hidden" name="extrato_id" value="<?= (int)($it['id'] ?? 0) ?>">
							<input type="hidden" name="lancamento_id" value="<?= (int)$panel['lancamento_id'] ?>">
							<button type="submit" class="btn btn-primary btn-xs">✓ <?= h(__('Confirmar match')) ?></button>
						</form>
					<?php elseif (($panel['type'] ?? '') === 'divergente') : ?>
						<div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
							<?= $this->Html->link('+ ' . __('Criar lançamento'), ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-primary btn-xs']) ?>
							<form method="post" action="<?= h($urlIgn) ?>" style="margin:0;display:inline;">
								<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
								<input type="hidden" name="extrato_id" value="<?= (int)($it['id'] ?? 0) ?>">
								<button type="submit" class="btn btn-ghost btn-xs"><?= h(__('Ignorar')) ?></button>
							</form>
						</div>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="match-side" style="background:var(--bg-surface);text-align:center;color:var(--text-muted);font-size:12px;">—</div>
			<?php endif; ?>
		</div>
	<?php endforeach; endif; ?>
</div>
