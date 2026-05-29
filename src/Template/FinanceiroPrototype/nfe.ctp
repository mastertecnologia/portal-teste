<?php
/**
 * NF-e / NFS-e — pg-nfe.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$k = (array)($nfeKpi ?? []);
$tabs = (array)($nfeTabCounts ?? []);
$f = (array)($nfeFiltros ?? []);
$tab = (string)($f['tab'] ?? 'todas');
$cert = (array)($nfeCertificado ?? []);
$sefaz = (array)($nfeSefaz ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM Soluções › <?= $this->Html->link(__('Financeiro'), ['action' => 'lista'], ['style' => 'color:var(--teal);']) ?> › NF-e / NFS-e
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🧾 <?= h(__('NF-e · Notas Fiscais Eletrônicas')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Emissão · gestão · DANFE · XML · contingência · cancelamentos')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('XMLs (mês)'), ['controller' => 'FiscalNotas', 'action' => 'index'], ['class' => 'btn btn-amber btn-sm']) ?>
		<?= $this->Html->link('🔍 ' . __('Consultar SEFAZ'), ['controller' => 'FiscalNotas', 'action' => 'index'], ['class' => 'btn btn-blue btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('+ ' . __('Emitir NF-e'), ['controller' => 'FiscalNotas', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
	</div>
</div>

<div class="alert-box alert-green" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
	<div>
		<strong><?= h((string)($sefaz['status'] ?? 'SEFAZ')) ?></strong>
		<div style="font-size:11px;margin-top:2px;"><?= sprintf(h(__('Última consulta: %s')), h((string)($sefaz['ultima'] ?? '—'))) ?></div>
	</div>
	<div style="display:flex;gap:12px;font-size:11px;">
		<div style="background:#fff;padding:8px 12px;border-radius:var(--radius);border:1px solid var(--border-light);">
			<div style="color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Certificado A1')) ?></div>
			<div><?= !empty($cert['valido']) ? '✓ ' . h(__('Válido')) : h(__('Indisponível')) ?><?php if (!empty($cert['vence'])) : ?> · vence <?= h((string)$cert['vence']) ?><?php endif; ?></div>
		</div>
		<div style="background:#fff;padding:8px 12px;border-radius:var(--radius);border:1px solid var(--border-light);">
			<div style="color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Próximo nº NF-e')) ?></div>
			<div><strong><?= h((string)($nfeProximoNumero ?? '—')) ?></strong></div>
		</div>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Emitidas · mês')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)($k['emitidas_mes'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h($H->brl((float)($k['valor_mes'] ?? 0))) ?> total</div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('NF-e (produtos)')) ?></div><div class="val" style="color:#0C447C;"><?= (int)($k['nfe_qtd'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(number_format((float)($nfeKpiPctNfe ?? 0), 1, ',', '.')) ?>%</div></div>
	<div class="summary-card" style="border-left:3px solid #D946A0;"><div class="lbl"><?= h(__('NFS-e (serviços)')) ?></div><div class="val" style="color:#7A1B5C;"><?= (int)($k['nfse_qtd'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(number_format((float)($nfeKpiPctNfse ?? 0), 1, ',', '.')) ?>%</div></div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Aguardando autorização')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)($k['aguardando'] ?? 0) ?></div><div style="font-size:11px;color:#8A4D02;">SEFAZ processando</div></div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Rejeitadas (24h)')) ?></div><div class="val" style="color:#7A1822;"><?= (int)($k['rejeitadas_24h'] ?? 0) ?></div><div style="font-size:11px;color:#7A1822;">revisar</div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Canceladas mês')) ?></div><div class="val" style="color:#3D2D63;"><?= (int)($k['canceladas_mes'] ?? 0) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="display:flex;border-bottom:1px solid var(--border);flex-wrap:wrap;">
		<?php
		$tabDefs = [
			'todas' => __('Todas ({0})', (int)($tabs['todas'] ?? 0)),
			'autorizadas' => '✓ ' . __('Autorizadas ({0})', (int)($tabs['autorizadas'] ?? 0)),
			'aguardando' => '⏰ ' . __('Aguardando ({0})', (int)($tabs['aguardando'] ?? 0)),
			'rejeitadas' => '⚠ ' . __('Rejeitadas ({0})', (int)($tabs['rejeitadas'] ?? 0)),
			'canceladas' => '✗ ' . __('Canceladas ({0})', (int)($tabs['canceladas'] ?? 0)),
		];
		foreach ($tabDefs as $key => $lbl) :
			$active = $tab === $key;
		?>
			<div style="padding:12px 16px;cursor:pointer;<?= $active ? 'border-bottom:3px solid var(--teal);' : '' ?>">
				<?= $this->Html->link(
					'<strong style="font-size:13px;color:' . ($active ? 'var(--teal-dark)' : 'var(--text-muted)') . ';">' . h($lbl) . '</strong>',
					['action' => 'view', 'nfe', '?' => ['tab' => $key]],
					['escape' => false, 'style' => 'text-decoration:none;']
				) ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div style="padding:12px 14px;border-bottom:1px solid var(--border-light);background:var(--bg-surface);">
		<?= $this->Form->create(null, ['type' => 'get', 'style' => 'display:flex;gap:8px;flex-wrap:wrap;margin:0;']) ?>
			<input type="hidden" name="tab" value="<?= h($tab) ?>"/>
			<input type="text" name="q" value="<?= h((string)($f['busca'] ?? '')) ?>" placeholder="🔍 <?= h(__('Buscar nº, cliente, chave de acesso...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
			<select name="modelo" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
				<option value=""><?= h(__('Todos os modelos')) ?></option>
				<option value="55" <?= (string)($f['modelo'] ?? '') === '55' ? 'selected' : '' ?>>NF-e (55)</option>
				<option value="NFSE" <?= (string)($f['modelo'] ?? '') === 'NFSE' ? 'selected' : '' ?>>NFS-e</option>
			</select>
		<?= $this->Form->end() ?>
	</div>
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead><tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Nº / Série')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Cliente')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Tipo')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Emissão')) ?></th>
				<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Valor')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Chave SEFAZ')) ?></th>
				<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
				<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
			</tr></thead>
			<tbody>
				<?php if ($nfeItems === []) : ?>
					<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma nota no escopo.')) ?></td></tr>
				<?php else : foreach ($nfeItems as $n) :
					$st = (array)($n['status'] ?? []);
					$rowBg = (string)($st['row_bg'] ?? '');
					$strike = !empty($st['strike']);
				?>
					<tr style="border-bottom:1px solid var(--border-light);<?= $rowBg !== '' ? 'background:' . h($rowBg) . ';' : '' ?>">
						<td style="padding:10px;<?= $strike ? 'text-decoration:line-through;color:var(--text-muted);' : '' ?>"><strong><?= h((string)$n['numero']) ?></strong><?php if (!empty($n['serie'])) : ?> / Sér <?= h((string)$n['serie']) ?><?php endif; ?></td>
						<td style="padding:10px;font-weight:600;<?= $strike ? 'text-decoration:line-through;color:var(--text-muted);' : '' ?>"><?= h((string)$n['cliente']) ?></td>
						<td style="padding:10px;">
							<?php if ((string)$n['tipo_badge'] === 'nfse') : ?>
								<span class="badge" style="background:#FCE0EC;color:#7A1B5C;font-size:10px;"><?= h((string)$n['tipo_label']) ?></span>
							<?php else : ?>
								<?= $H->badge((string)$n['tipo_label'], 'aprov') ?>
							<?php endif; ?>
						</td>
						<td style="padding:10px;font-size:11px;"><?= h($H->dt($n['emissao'], 'd/m H:i')) ?></td>
						<td style="padding:10px;text-align:right;font-weight:600;<?= $strike ? 'text-decoration:line-through;color:var(--text-muted);' : '' ?>"><?= h($H->brl((float)$n['valor'])) ?></td>
						<td style="padding:10px;font-family:monospace;font-size:10px;color:var(--text-muted);"><?= h((string)$n['chave_curta']) ?></td>
						<td style="padding:10px;"><?= $H->badge((string)($st['label'] ?? '—'), (string)($st['badge'] ?? 'arq')) ?></td>
						<td style="padding:10px;text-align:center;">
							<?php if (($st['tab'] ?? '') === 'rejeitadas') : ?>
								<?= $this->Html->link('✏ ' . __('Corrigir'), (array)$n['view_url'], ['class' => 'btn btn-red btn-xs', 'escape' => false]) ?>
							<?php else : ?>
								<?= $this->Html->link('📄 DANFE', (array)$n['view_url'], ['class' => 'btn btn-ghost btn-xs', 'escape' => false]) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
