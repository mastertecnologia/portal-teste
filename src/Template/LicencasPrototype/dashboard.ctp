<?php
/**
 * Painel PGM Licenças (pg-lic-dashboard).
 *
 * @var \App\View\AppView $this
 * @var array<string,int|float> $licKpi
 * @var array<string,mixed> $licDash
 * @var \App\Service\Lic\LicPrototypeDataService $licSvc
 * @var bool $licMigrationHint
 */
$k = (array)($licKpi ?? []);
$d = (array)($licDash ?? []);
$svc = $licSvc ?? null;
$proximos = (array)($d['proximos_vencimentos'] ?? []);
$topEmpresas = (array)($d['top_empresas'] ?? []);
$porCat = (array)($d['por_categoria'] ?? []);
$atividade = (array)($d['atividade_recente'] ?? []);
$solAbertas = (int)($k['solicitacoes_abertas'] ?? 0);

$gradients = [
	'linear-gradient(135deg,var(--teal),var(--teal-dark))',
	'linear-gradient(135deg,#0C447C,#1E40AF)',
	'linear-gradient(135deg,#F59E0B,#B45309)',
	'linear-gradient(135deg,#6B21A8,#3D1F75)',
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= h(__('Licenciamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔑 <?= h(__('PGM Licenças — Painel')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Controle consolidado de licenças, dispositivos e credenciais dos clientes')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(
			'📋 ' . __('Solicitações') . ($solAbertas > 0 ? ' <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:9px;margin-left:4px;">' . (int)$solAbertas . '</span>' : ''),
			['action' => 'view', 'solicitacoes'],
			['class' => 'btn btn-ghost btn-sm', 'escape' => false]
		) ?>
		<?= $this->Html->link('📅 ' . __('Calendário'), ['action' => 'view', 'calendario'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Relatórios'), ['action' => 'view', 'relatorios'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('⚙ ' . __('Configurações'), ['action' => 'view', 'config'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova licença'), ['action' => 'view', 'nova'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn" style="margin-bottom:14px;">
	<?= h(__('Execute: bin/cake migrations migrate')) ?>
</div>
<?php endif; ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'empresas'])) ?>" style="border-left:3px solid var(--teal);text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Empresas-cliente')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($k['empresas_cliente'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('{0} novas em 30d', (int)($k['empresas_novas_30d'] ?? 0))) ?></div>
	</a>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'licencas'])) ?>" style="border-left:3px solid var(--blue);text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Licenças ativas')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)($k['licencas_ativas'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('{0} assentos', number_format((int)($k['assentos'] ?? 0), 0, '', '.'))) ?></div>
	</a>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'renovacoes'])) ?>" style="background:#FFFBEB;border-left:3px solid var(--amber);text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Vencem em 30 dias')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($k['venc_30'] ?? 0) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= $svc ? h($svc->formatReceitaCompacta($k['renovacao_valor_30'] ?? 0) . ' ' . __('em renovações')) : '' ?></div>
	</a>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'renovacoes'])) ?>" style="background:#FEF2F2;border-left:3px solid var(--red);text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Vencidas')) ?></div>
		<div class="val" style="color:#7A1822;"><?= (int)($k['vencidas'] ?? 0) ?></div>
		<div style="font-size:11px;color:#7A1822;"><?= h(__('ação urgente')) ?></div>
	</a>
	<div class="summary-card" style="border-left:3px solid #10B981;">
		<div class="lbl"><?= h(__('Receita anual (MRR×12)')) ?></div>
		<div class="val" style="color:#065F46;font-size:16px;"><?= $svc ? h($svc->formatReceitaCompacta($k['receita_anual'] ?? 0)) : '—' ?></div>
	</div>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'cofre'])) ?>" style="border-left:3px solid #6B5B95;text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Credenciais no cofre')) ?></div>
		<div class="val" style="color:#3D2D63;"><?= (int)($k['cofre_itens'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('{0} visualizações 7d', (int)($k['cofre_views_7d'] ?? 0))) ?></div>
	</a>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'dispositivos'])) ?>" style="border-left:3px solid #D946A0;text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Dispositivos')) ?></div>
		<div class="val" style="color:#7A1B5C;"><?= number_format((int)($k['dispositivos'] ?? 0), 0, '', '.') ?></div>
	</a>
	<a class="summary-card" href="<?= h($this->Url->build(['action' => 'view', 'inteligencia'])) ?>" style="border-left:3px solid #F59E0B;text-decoration:none;color:inherit;">
		<div class="lbl"><?= h(__('Subutilizadas')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($k['subutilizadas'] ?? 0) ?></div>
		<?php
		$economia = ((float)($k['receita_anual'] ?? 0) / max(1, (int)($k['licencas_ativas'] ?? 1))) / 12 * (int)($k['subutilizadas'] ?? 0);
		?>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('economia potencial {0}/mês', $svc ? $svc->formatReceitaCompacta($economia) : '—')) ?></div>
	</a>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:6px;">
				<div class="sec-title" style="margin:0;">⏰ <?= h(__('Próximos vencimentos')) ?></div>
				<?= $this->Html->link(__('Ver calendário →'), ['action' => 'view', 'calendario'], ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
							<th style="padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Vence em')) ?></th>
							<th style="padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Cliente')) ?></th>
							<th style="padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Licença')) ?></th>
							<th style="padding:8px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Renovação')) ?></th>
							<th style="padding:8px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($proximos === []) : ?>
						<tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum vencimento nos próximos 90 dias.')) ?></td></tr>
						<?php else :
							foreach ($proximos as $row) :
								$dias = (int)($row['dias'] ?? 0);
								$bg = (string)($row['row_bg'] ?? '');
								$kind = (string)($row['status_kind'] ?? '');
								$badgeStyle = 'font-size:9px;';
								if ($kind === 'urgente') {
									$badgeStyle .= 'background:#FEE2E2;color:#991B1B;';
								} elseif ($kind === 'aviso') {
									$badgeStyle .= 'background:#FAEEDA;color:#8A4D02;';
								} elseif ($kind === 'renovacao') {
									$badgeStyle .= 'background:#E1F5EE;color:#085041;';
								} else {
									$badgeStyle .= 'background:#DBEAFE;color:#1E40AF;';
								}
								?>
						<tr style="border-bottom:1px solid var(--border-light);cursor:pointer;<?= $bg !== '' ? 'background:' . h($bg) . ';' : '' ?>" onclick="window.location.href='<?= h($this->Url->build(['action' => 'licencaDetalhe', (int)$row['id']])) ?>'">
							<td style="padding:8px;">
								<?php if ($dias < 0) : ?>
								<strong style="color:#7A1822;">⚠ <?= h(__('Vencida')) ?></strong>
								<div style="font-size:10px;color:#7A1822;"><?= h(__('{0} dias', $dias)) ?></div>
								<?php else : ?>
								<strong><?= h(__('{0} dias', $dias)) ?></strong>
								<div style="font-size:10px;color:var(--text-muted);"><?= h($row['fim_fmt'] ?? '') ?></div>
								<?php endif; ?>
							</td>
							<td style="padding:8px;"><?= h($row['cliente'] ?? '') ?></td>
							<td style="padding:8px;"><?= h($row['licenca'] ?? '') ?></td>
							<td style="padding:8px;text-align:right;font-weight:600;"><?= $this->ErpPrototype->brl($row['renovacao'] ?? 0) ?></td>
							<td style="padding:8px;text-align:center;"><span class="badge" style="<?= h($badgeStyle) ?>"><?= h($row['status_label'] ?? '') ?></span></td>
						</tr>
						<?php endforeach;
						endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:6px;">
				<div class="sec-title" style="margin:0;">🏢 <?= h(__('Top empresas por valor anual')) ?></div>
				<?= $this->Html->link(__('Ver todas →'), ['action' => 'view', 'empresas'], ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php if ($topEmpresas === []) : ?>
				<p style="font-size:12px;color:var(--text-muted);margin:0;"><?= h(__('Cadastre licenças para ver o ranking por cliente.')) ?></p>
				<?php else :
					foreach ($topEmpresas as $i => $e) :
						$ini = $this->ErpPrototype->initials((string)($e['nome'] ?? ''));
						$valAnual = (float)($e['valor_anual'] ?? 0);
						$valMes = $valAnual / 12;
						$grad = $gradients[$i % count($gradients)];
						?>
				<a href="<?= h($this->Url->build(['action' => 'view', 'empresa-detalhe', '?' => ['id' => (int)$e['id']]])) ?>" style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg-surface);border-radius:6px;text-decoration:none;color:inherit;">
					<div style="width:36px;height:36px;border-radius:8px;background:<?= h($grad) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;"><?= h($ini) ?></div>
					<div style="flex:1;">
						<strong style="font-size:13px;"><?= h($e['nome'] ?? '') ?></strong>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('{0} licenças · {1} dispositivos', (int)($e['licencas'] ?? 0), (int)($e['dispositivos'] ?? 0))) ?></div>
					</div>
					<div style="text-align:right;">
						<strong style="font-size:14px;color:#065F46;"><?= $this->ErpPrototype->brl($valAnual) ?>/<?= h(__('ano')) ?></strong>
						<div style="font-size:10px;color:var(--text-muted);"><?= $this->ErpPrototype->brl($valMes) ?>/<?= h(__('mês')) ?></div>
					</div>
				</a>
				<?php endforeach;
				endif; ?>
			</div>
		</div>
	</div>

	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📊 <?= h(__('Por categoria')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;font-size:12px;">
				<?php if ($porCat === []) : ?>
				<p style="color:var(--text-muted);margin:0;"><?= h(__('Sem licenças ativas por categoria.')) ?></p>
				<?php else :
					foreach ($porCat as $cat) : ?>
				<div>
					<div style="display:flex;justify-content:space-between;margin-bottom:3px;"><span><?= h(($cat['icon'] ?? '') . ' ' . ($cat['nome'] ?? '')) ?></span><strong><?= (int)($cat['total'] ?? 0) ?></strong></div>
					<div style="height:6px;background:var(--bg-surface);border-radius:3px;overflow:hidden;"><div style="height:100%;background:<?= h($cat['color'] ?? 'var(--teal)') ?>;width:<?= (int)($cat['pct'] ?? 0) ?>%;"></div></div>
				</div>
				<?php endforeach;
				endif; ?>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">⚡ <?= h(__('Ações rápidas')) ?></div>
			<div style="display:flex;flex-direction:column;gap:6px;">
				<?= $this->Html->link('+ ' . __('Cadastrar nova licença'), ['action' => 'view', 'nova'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
				<?= $this->Html->link('🏢 ' . __('Adicionar empresa-cliente'), ['action' => 'view', 'empresa-nova'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
				<?= $this->Html->link('📚 ' . __('Adicionar produto ao catálogo'), ['action' => 'view', 'produto-novo'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
				<?= $this->Html->link('🔐 ' . __('Cofre de credenciais'), ['action' => 'view', 'cofre'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
				<?= $this->Html->link('📜 ' . __('Auditoria'), ['action' => 'view', 'auditoria'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
				<?= $this->Html->link('🤖 ' . __('Inteligência & Insights'), ['action' => 'view', 'inteligencia'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'justify-content:flex-start;text-align:left;']) ?>
			</div>
		</div>

		<div class="card">
			<div class="sec-title">📋 <?= h(__('Atividade recente')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;font-size:11px;">
				<?php if ($atividade === []) : ?>
				<p style="color:var(--text-muted);margin:0;"><?= h(__('Nenhum evento de auditoria registrado.')) ?></p>
				<?php else :
					foreach ($atividade as $ev) : ?>
				<div style="padding:6px;border-left:2px solid <?= h($ev['cor'] ?? 'var(--teal)') ?>;padding-left:10px;">
					<strong><?= h($ev['autor'] ?? '') ?></strong> <?= h($ev['titulo'] ?? '') ?>
					<div style="color:var(--text-muted);"><?= h($ev['quando'] ?? '') ?> · <?= h($ev['detalhe'] ?? '') ?></div>
				</div>
				<?php endforeach;
				endif; ?>
			</div>
		</div>
	</div>
</div>
