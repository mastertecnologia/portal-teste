<?php
/**
 * Bancos — mockup pg-bancos (dashboard consolidado).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $bcItems
 * @var array<string,mixed> $bcKpi
 * @var array{labels:array,entradas:array,saidas:array,max:float} $chart7d
 * @var array<int,array<string,mixed>> $distribuicao
 * @var array<int,array<string,mixed>> $ultimosMov
 * @var array<int,array<string,string>> $bancosCatalogo
 * @var bool $abrirModalConta
 */
$H = $this->ErpPrototype;
$urlExtrato = ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'];
$urlConc = ['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao'];
$urlTf = ['controller' => 'BancosPrototype', 'action' => 'view', 'transferencias'];
$maxChart = max(1.0, (float)($chart7d['max'] ?? 1));
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Bancos · Visão consolidada')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= sprintf(
				h(__('%d contas bancárias · Última sincronização %s')),
				(int)$bcKpi['total'],
				h((string)($bcKpi['ultima_sync_label'] ?? __('—')))
			) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🔄 ' . __('Sincronizar tudo'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🔗 ' . __('Conciliar'), $urlConc, ['class' => 'btn btn-blue btn-sm']) ?>
		<button type="button" class="btn btn-primary btn-sm" data-pgm-open-conta-modal>+ <?= h(__('Nova conta')) ?></button>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="background:linear-gradient(135deg,var(--teal-light),#fff);border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Saldo total consolidado')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl($bcKpi['saldo_total'] ?? 0)) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);">
			<?php if ((float)($bcKpi['variacao_hoje'] ?? 0) >= 0) : ?>↑<?php else : ?>↓<?php endif; ?>
			<?= h($H->brl(abs((float)($bcKpi['variacao_hoje'] ?? 0)))) ?> <?= h(__('hoje')) ?> · <?= (int)($bcKpi['ativas'] ?? 0) ?> <?= h(__('contas')) ?>
		</div>
	</div>
	<div class="summary-card" style="border-left:3px solid #1D9E75;">
		<div class="lbl"><?= h(__('Entradas hoje')) ?></div>
		<div class="val" style="color:var(--teal);"><?= h($H->brl($bcKpi['entradas_hoje'] ?? 0)) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($bcKpi['mov_entradas_hoje'] ?? 0) ?> <?= h(__('movimentações')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('Saídas hoje')) ?></div>
		<div class="val" style="color:#7A1822;"><?= h($H->brl($bcKpi['saidas_hoje'] ?? 0)) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($bcKpi['mov_saidas_hoje'] ?? 0) ?> <?= h(__('movimentações')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--amber);background:#FAEEDA;">
		<div class="lbl"><?= h(__('Pendentes de conciliação')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= h($H->brl($bcKpi['pendentes_valor'] ?? 0)) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= (int)($bcKpi['pendentes_count'] ?? 0) ?> <?= h(__('lançamentos')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #0C447C;">
		<div class="lbl"><?= h(__('A pagar próx. 7 dias')) ?></div>
		<div class="val" style="color:#0C447C;"><?= h($H->brl($bcKpi['a_pagar_7d'] ?? 0)) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($bcKpi['a_pagar_count'] ?? 0) ?> <?= h(__('contas')) ?></div>
	</div>
	<div class="summary-card">
		<div class="lbl"><?= h(__('Contas ativas')) ?></div>
		<div class="val" style="color:var(--text);"><?= (int)($bcKpi['ativas'] ?? 0) ?> / <?= (int)($bcKpi['total'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);"><?= h(__('Cadastro em dia')) ?></div>
	</div>
</div>

<?php if ($bcItems === []) : ?>
	<div class="card" style="text-align:center;padding:32px 22px;color:var(--text-muted);">
		<?= h(__('Nenhuma conta bancária cadastrada.')) ?>
		<div style="margin-top:14px;">
			<button type="button" class="btn btn-primary btn-sm" data-pgm-open-conta-modal>+ <?= h(__('Nova conta bancária')) ?></button>
		</div>
	</div>
<?php else : ?>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:14px;">
		<?php foreach ($bcItems as $b) :
			$br = $b['brand'];
			$extratoUrl = $urlExtrato + ['?' => ['conta' => (string)($b['conta_extrato'] ?: $b['nome'])]];
			?>
			<div class="bank-card">
				<div class="bank-card-header" style="background:<?= h($br['header']) ?>;">
					<div style="display:flex;align-items:center;gap:10px;">
						<div class="bank-card-logo" style="background:<?= h($br['logo_bg']) ?>;color:<?= h($br['logo_fg']) ?>;"><?= h($br['sigla']) ?></div>
						<div>
							<div style="font-size:13px;font-weight:600;"><?= h((string)$b['nome']) ?></div>
							<div style="font-size:11px;opacity:.85;"><?= h(__('Ag.')) ?> <?= h((string)$b['agencia']) ?> · <?= h(__('CC')) ?> <?= h((string)$b['conta']) ?></div>
						</div>
					</div>
					<?php if (!empty($b['sync_stale'])) : ?>
						<span class="badge b-vencendo" style="background:rgba(255,255,255,.25);color:#fff;border-color:rgba(255,255,255,.3);">⏰ <?= h((string)$b['sync_label']) ?></span>
					<?php else : ?>
						<span class="badge b-paga" style="background:rgba(255,255,255,.25);color:#fff;border-color:rgba(255,255,255,.3);">✓ Sync</span>
					<?php endif; ?>
				</div>
				<div class="bank-card-body">
					<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Saldo disponível')) ?></div>
					<div class="bank-saldo"><?= h($H->brl($b['saldo'])) ?></div>
					<div class="bank-info-row">
						<span><?= h((string)$b['tipo_label']) ?></span>
						<span style="color:<?= (float)$b['variacao_hoje'] >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;">
							<?= (float)$b['variacao_hoje'] >= 0 ? '↑' : '↓' ?> <?= h($H->brl(abs((float)$b['variacao_hoje']))) ?>
						</span>
					</div>
					<div style="display:flex;gap:6px;margin-top:10px;">
						<?= $this->Html->link(__('Extrato'), $extratoUrl, ['class' => 'btn btn-ghost btn-xs', 'style' => 'flex:1;']) ?>
						<?= $this->Html->link(__('Transferir'), $urlTf, ['class' => 'btn btn-ghost btn-xs', 'style' => 'flex:1;']) ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="g2">
		<div class="card">
			<div class="sec-title"><?= h(__('Movimentação · últimos 7 dias')) ?></div>
			<div style="position:relative;height:200px;border-bottom:1px solid var(--border);border-left:1px solid var(--border);padding:0 8px;margin-left:30px;">
				<div style="position:absolute;bottom:0;left:8px;right:8px;display:flex;align-items:flex-end;gap:8px;height:100%;">
					<?php foreach ($chart7d['labels'] as $i => $lbl) :
						$eh = round(100 * (float)$chart7d['entradas'][$i] / $maxChart);
						$sh = round(100 * (float)$chart7d['saidas'][$i] / $maxChart);
						?>
						<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
							<div style="width:100%;display:flex;flex-direction:column-reverse;align-items:stretch;height:100%;">
								<div style="background:var(--teal);height:<?= (int)$eh ?>%;min-height:2px;border-radius:3px 3px 0 0;"></div>
								<div style="background:var(--red);height:<?= (int)$sh ?>%;min-height:1px;margin-bottom:1px;"></div>
							</div>
							<div style="font-size:10px;color:var(--text-muted);<?= $lbl === __('Hoje') ? 'font-weight:600;color:var(--teal-dark);' : '' ?>"><?= h($lbl) ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div style="display:flex;gap:10px;margin-top:10px;font-size:11px;justify-content:center;">
				<div><span style="display:inline-block;width:10px;height:10px;background:var(--teal);border-radius:2px;vertical-align:middle;"></span> <?= h(__('Entradas')) ?></div>
				<div><span style="display:inline-block;width:10px;height:10px;background:var(--red);border-radius:2px;vertical-align:middle;"></span> <?= h(__('Saídas')) ?></div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Distribuição do saldo por instituição')) ?></div>
			<div style="display:flex;flex-direction:column;gap:10px;">
				<?php foreach ($distribuicao as $d) : ?>
					<div>
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
							<span style="font-size:12px;font-weight:500;"><?= h((string)$d['nome']) ?></span>
							<span style="font-size:12px;font-weight:600;"><?= h($H->brl($d['saldo'])) ?> <span style="font-size:10px;color:var(--text-muted);">(<?= h((string)$d['pct']) ?>%)</span></span>
						</div>
						<div style="height:8px;background:var(--bg-surface);border-radius:4px;overflow:hidden;">
							<div style="height:100%;background:<?= h((string)$d['bar']) ?>;width:<?= min(100, (float)$d['pct']) ?>%;border-radius:4px;"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div style="background:var(--bg-surface);padding:10px 12px;border-radius:var(--radius);margin-top:14px;display:flex;justify-content:space-between;align-items:center;">
				<span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Total consolidado')) ?></span>
				<span style="font-size:16px;font-weight:700;color:var(--teal-dark);"><?= h($H->brl($bcKpi['saldo_total'] ?? 0)) ?></span>
			</div>
		</div>
	</div>

	<?php if ($ultimosMov !== []) : ?>
		<div class="card" style="margin-top:14px;padding:0;overflow:hidden;">
			<div style="padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;">
				<div style="font-size:14px;font-weight:600;"><?= h(__('Últimas movimentações · todas as contas')) ?></div>
				<?= $this->Html->link(__('Ver extrato completo →'), $urlExtrato, ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
			<div>
				<?php foreach ($ultimosMov as $m) : ?>
					<div class="mov-row">
						<span style="font-size:11px;color:var(--text-muted);"><?= h((string)$m['data_label']) ?></span>
						<div class="mov-icon <?= !empty($m['entrada']) ? 'in' : 'out' ?>"><?= !empty($m['entrada']) ? '↓' : '↑' ?></div>
						<div>
							<div style="font-size:13px;font-weight:500;"><?= h((string)$m['descricao']) ?></div>
							<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$m['conta']) ?></div>
						</div>
						<?= $H->badge(!empty($m['conciliado']) ? '✓ ' . __('Conciliado') : '⏰ ' . __('Pendente'), !empty($m['conciliado']) ? 'paga' : 'pendente') ?>
						<span class="mov-valor <?= !empty($m['entrada']) ? 'in' : 'out' ?>"><?= !empty($m['entrada']) ? '+' : '-' ?> <?= h($H->brl($m['valor'])) ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>

<?= $this->element('BancosPrototype/modal_conta', ['bancosCatalogo' => $bancosCatalogo]) ?>
<?= $this->element('BancosPrototype/modal_scripts', ['abrirModalConta' => !empty($abrirModalConta)]) ?>
