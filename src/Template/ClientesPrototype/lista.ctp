<?php
use App\Utility\PortalUi;

/**
 * Lista de clientes — pg-clientes (mock) com CRM do legado.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $cliCrm
 * @var array<int,array<string,mixed>> $cliRows
 * @var array<int,string> $cliVendedores
 */
$H = $this->ErpPrototype;
$crm = isset($cliCrm) && is_array($cliCrm) ? $cliCrm : [];
$top5 = $crm['top5'] ?? [];
$segmentos = (array)($crm['segmentos'] ?? []);
if ($segmentos === []) {
	$segmentos = [
		['slug' => 'outros', 'label' => __('Outros'), 'count' => 0, 'pct' => 0],
	];
}
$f = (array)($cliFiltros ?? ['q' => '', 'tipo' => '', 'status' => '']);
$barColors = ['var(--teal)', 'var(--blue)', '#6B5B95', 'var(--amber)', 'var(--red)'];

if (!function_exists('cliProtoInitials')) {
	function cliProtoInitials($str) {
		$parts = preg_split('/\s+/', trim((string)$str), -1, PREG_SPLIT_NO_EMPTY);
		$a = strtoupper(substr($parts[0] ?? 'C', 0, 1));
		$b = strtoupper(substr($parts[1] ?? '', 0, 1));

		return $a . $b;
	}
}

if (!function_exists('cliProtoRowDataAttrs')) {
	function cliProtoRowDataAttrs($reg) {
		$isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
		$docDigits = preg_replace('/\D/', '', (string)($isPj ? ($reg->cnpj ?? '') : ($reg->cpf ?? '')));
		$emailLower = mb_strtolower(trim((string)($reg->email ?? '')), 'UTF-8');
		$pub = mb_strtolower(trim((string)($reg->public_code ?? '')), 'UTF-8');
		$parts = $isPj ? [trim((string)($reg->razaosocial ?? '')), trim((string)($reg->nomefantasia ?? ''))] : [trim((string)($reg->nome ?? ''))];
		$textBlob = mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts)))), 'UTF-8');
		if ($emailLower !== '') {
			$textBlob = trim($textBlob . ' ' . $emailLower);
		}
		if ($pub !== '') {
			$textBlob = trim($textBlob . ' ' . $pub);
		}
		$primaryLower = mb_strtolower(trim($isPj ? (string)($reg->razaosocial ?? '') : (string)($reg->nome ?? '')), 'UTF-8');
		$primaryLower = trim(preg_replace('/\s+/', ' ', $primaryLower));

		return ' data-cli-doc="' . h($docDigits) . '" data-cli-email="' . h($emailLower) . '" data-cli-text="' . h($textBlob) . '" data-cli-primary="' . h($primaryLower) . '"';
	}
}
?>
<div id="pg-clientes-lista">
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Cadastros'),
	'title' => __('Clientes'),
	'subtitle' => __('Cadastro mestre · CRM básico · Histórico financeiro consolidado'),
	'actions' => [
		['label' => __('Exportar CSV'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'exportCsv'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Importar'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'view', 'import'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Novo cliente'), 'url' => ['controller' => 'Clientes', 'action' => 'add'], 'class' => 'btn btn-primary btn-sm'],
	],
]) ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card cli-kpi-card cli-kpi-active" data-cli-kpi="ativos" style="border-left:3px solid var(--teal);cursor:pointer;">
		<div class="lbl"><?= h(__('Clientes ativos')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($crm['ativos'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);">
			<?php if (!empty($crm['novos_mes'])) : ?>
				↑ <?= (int)$crm['novos_mes'] ?> <?= h(__('este mês')) ?>
			<?php else : ?>
				<?= h(__('na carteira')) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="summary-card" data-cli-kpi="receita" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Receita 12 meses')) ?></div>
		<div class="val" style="color:#0C447C;"><?= h((string)($crm['receita12_fmt'] ?? '—')) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);">
			<?php if (!empty($crm['receita12_pct'])) : ?>
				↑ <?= (int)$crm['receita12_pct'] ?>% <?= h(__('vs período anterior')) ?>
			<?php else : ?>
				<?= h(__('consolidado financeiro')) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="summary-card" style="border-left:3px solid #D946A0;">
		<div class="lbl"><?= h(__('Ticket médio')) ?></div>
		<div class="val" style="color:#7A1B5C;"><?= h((string)($crm['ticket_fmt'] ?? '—')) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('por cliente / ano')) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Inadimplentes')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($crm['inadimplentes'] ?? 0) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= h((string)($crm['inadimplentes_valor_fmt'] ?? '—')) ?> <?= h(__('em atraso')) ?></div>
	</div>
	<div class="summary-card" data-cli-kpi="bloqueados" style="background:#F8D8DA;border-left:3px solid var(--red);cursor:pointer;">
		<div class="lbl"><?= h(__('Bloqueados')) ?></div>
		<div class="val" style="color:#7A1822;"><?= (int)($crm['bloqueados'] ?? 0) ?></div>
		<div style="font-size:11px;color:#7A1822;"><?= h(__('restrição interna')) ?></div>
	</div>
	<div class="summary-card" data-cli-kpi="aniversariantes" style="border-left:3px solid #6B5B95;cursor:pointer;">
		<div class="lbl"><?= h(__('Aniversariantes do mês')) ?></div>
		<div class="val" style="color:#3D2D63;"><?= (int)($crm['aniversariantes'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('enviar mensagem')) ?></div>
	</div>
</div>

<div class="g2" style="margin-bottom:14px;">
	<div class="card">
		<div class="sec-title"><?= h(__('Top 5 clientes · receita 12 meses')) ?></div>
		<?php if ($top5 === []) : ?>
			<p style="font-size:12px;color:var(--text-muted);margin:0;"><?= h(__('Sem receitas lançadas no período.')) ?></p>
		<?php else : ?>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php foreach ($top5 as $i => $row) :
					$pct = max(4, min(100, (int)($row['pct'] ?? 0)));
					$barColor = $barColors[$i] ?? 'var(--teal)';
				?>
				<div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
						<?= $this->Html->link(
							h((string)$row['nome']),
							PortalUi::visao360Route((int)($row['id'] ?? 0)) ?? ['controller' => 'Clientes', 'action' => 'visao360', (int)($row['id'] ?? 0)],
							['style' => 'font-size:12px;font-weight:500;color:var(--teal-dark);', 'data-turbo' => 'false']
						) ?>
						<span style="font-size:12px;font-weight:600;"><?= h($this->Number->currency((float)($row['valor'] ?? 0), 'BRL')) ?> <span style="font-size:10px;color:var(--text-muted);">(<?= (int)($row['pct'] ?? 0) ?>%)</span></span>
					</div>
					<div style="height:8px;background:var(--bg-surface);border-radius:4px;overflow:hidden;">
						<div style="height:100%;background:<?= h($barColor) ?>;width:<?= (int)$pct ?>%;border-radius:4px;"></div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php if (!empty($crm['alerta_concentracao'])) : ?>
			<div class="alert-box alert-amber" style="margin-top:10px;font-size:11px;">
				⚠ <?= h(__('Concentração: {0} representa {1}% da carteira.', $crm['alerta_concentracao']['nome'], $crm['alerta_concentracao']['pct'])) ?>
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Distribuição por segmento')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px;margin-bottom:12px;">
			<?php foreach ($segmentos as $seg) : ?>
			<div style="text-align:center;padding:10px 8px;background:var(--bg-surface);border-radius:8px;border:1px solid var(--border-light);">
				<div style="font-size:18px;font-weight:700;"><?= (int)$seg['count'] ?></div>
				<div style="font-size:10px;color:var(--text-muted);"><?= h((string)$seg['label']) ?></div>
				<div style="font-size:10px;color:var(--teal-dark);"><?= (int)$seg['pct'] ?>%</div>
			</div>
			<?php endforeach; ?>
		</div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;"><?= h(__('Pessoa jurídica')) ?> · <?= (int)($crm['pj_bar']['count'] ?? 0) ?> (<?= (int)($crm['pj_bar']['pct'] ?? 0) ?>%)</div>
		<div style="height:6px;background:var(--bg-surface);border-radius:3px;overflow:hidden;margin-bottom:8px;">
			<div style="height:100%;background:var(--teal);width:<?= (int)($crm['pj_bar']['pct'] ?? 0) ?>%;"></div>
		</div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;"><?= h(__('Pessoa física')) ?> · <?= (int)($crm['pf_bar']['count'] ?? 0) ?> (<?= (int)($crm['pf_bar']['pct'] ?? 0) ?>%)</div>
		<div style="height:6px;background:var(--bg-surface);border-radius:3px;overflow:hidden;">
			<div style="height:100%;background:var(--blue);width:<?= (int)($crm['pf_bar']['pct'] ?? 0) ?>%;"></div>
		</div>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
			<div class="field" style="flex:1;min-width:220px;margin:0;">
				<input type="search" id="cli-search" value="<?= h((string)$f['q']) ?>" placeholder="🔍 <?= h(__('Buscar por nome, CNPJ/CPF, e-mail, telefone...')) ?>" autocomplete="off" style="width:100%;">
			</div>
			<span id="cli-search-mode" style="font-size:10px;color:var(--text-muted);min-width:48px;"></span>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
			<select id="cli-filter-status" class="field" style="min-width:140px;">
				<option value=""><?= h(__('Todos os status')) ?></option>
				<option value="ativos"<?= (string)$f['status'] === 'ativo' ? ' selected' : '' ?>><?= h(__('Ativos')) ?></option>
				<option value="inativos"<?= (string)$f['status'] === 'inativo' ? ' selected' : '' ?>><?= h(__('Inativos')) ?></option>
			</select>
			<select id="cli-filter-segmento" style="min-width:160px;">
				<option value=""><?= h(__('Todos os segmentos')) ?></option>
				<?php foreach ($segmentos as $seg) : ?>
				<option value="<?= h((string)$seg['slug']) ?>"><?= h((string)$seg['label']) ?></option>
				<?php endforeach; ?>
			</select>
			<div id="cli-filter-tipo-group" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:6px 10px;border:1px solid var(--border-light);border-radius:6px;">
				<span style="font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Tipo')) ?></span>
				<label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;"><input type="checkbox" id="cli-filter-tipo-pj" checked style="accent-color:var(--teal);"> PJ</label>
				<label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;"><input type="checkbox" id="cli-filter-tipo-pf" checked style="accent-color:var(--teal);"> PF</label>
			</div>
			<?php if (!empty($cliPapelColumns)) : ?>
			<div id="cli-filter-papel-group" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:6px 10px;border:1px solid var(--border-light);border-radius:6px;">
				<span style="font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Papel')) ?></span>
				<label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;"><input type="checkbox" id="cli-filter-papel-cliente" checked style="accent-color:var(--teal);"> <?= h(__('Cliente')) ?></label>
				<label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;"><input type="checkbox" id="cli-filter-papel-fornecedor" checked style="accent-color:var(--teal);"> <?= h(__('Fornecedor')) ?></label>
			</div>
			<?php endif; ?>
			<select id="cli-filter-vendedor" style="min-width:160px;">
				<option value=""><?= h(__('Todos os vendedores')) ?></option>
				<?php foreach ($cliVendedores as $vid => $vname) : ?>
				<option value="<?= (int)$vid ?>"><?= h((string)$vname) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div style="display:flex;gap:6px;flex-wrap:wrap;">
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="top-receita">★ <?= h(__('Top 10 receita')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="novos">🆕 <?= h(__('Novos clientes')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="atraso">⏰ <?= h(__('Em atraso')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="sem-contato">📞 <?= h(__('Sem contato 30d')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="vip">💎 <?= h(__('Clientes VIP')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm cli-proto-chip" data-chip="aniversariantes">🎂 <?= h(__('Aniversariantes')) ?></button>
		</div>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" id="cli-proto-table" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Cliente')) ?></th>
					<th><?= h(__('CNPJ/CPF')) ?></th>
					<th><?= h(__('Papel')) ?></th>
					<th><?= h(__('Segmento')) ?></th>
					<th><?= h(__('Cidade')) ?></th>
					<th class="r"><?= h(__('Receita 12M')) ?></th>
					<th class="r"><?= h(__('A receber')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th><?= h(__('Última compra')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php $idxRow = 0;
				foreach ($cliRows as $row) :
					$reg = $row['entity'];
					$seg = $row['segmento'];
					$rec12 = (float)($row['receita12'] ?? 0);
					$aRec = (float)($row['a_receber'] ?? 0);
					$url360 = $this->Url->build(PortalUi::visao360Route((int)$reg->id) ?? ['controller' => 'Clientes', 'action' => 'visao360', $reg->id]);
					$stClass = (string)($row['status_class'] ?? 'on');
					$badgeType = 'paga';
					if ($stClass === 'warn') {
						$badgeType = 'env';
					} elseif ($stClass === 'blocked') {
						$badgeType = 'arq';
					} elseif ($stClass === 'vip') {
						$badgeType = 'aprov';
					}
					$avTone = (string)($row['av_tone'] ?? 'teal');
					$avColors = [
						'teal' => 'var(--teal)',
						'blue' => 'var(--blue)',
						'navy' => '#0C447C',
						'orange' => 'var(--amber)',
						'wine' => '#7A1822',
						'rose' => '#D946A0',
						'purple' => '#6B5B95',
					];
					$avBg = $avColors[$avTone] ?? 'var(--teal)';
				?>
				<tr<?= cliProtoRowDataAttrs($reg) ?>
					data-cli-status="<?= h((string)$row['status_key']) ?>"
					data-cli-tipo="<?= h((string)$row['tipo_key']) ?>"
					data-cli-papel-cliente="<?= (int)($row['eh_cliente'] ?? 1) ?>"
					data-cli-papel-fornecedor="<?= (int)($row['eh_fornecedor'] ?? 0) ?>"
					data-cli-segmento="<?= h((string)$row['segmento_slug']) ?>"
					data-cli-vendedor="<?= (int)($row['vendedor_id'] ?? 0) ?>"
					data-cli-atraso="<?= (int)($row['atraso'] ?? 0) ?>"
					data-cli-vip="<?= (int)($row['vip'] ?? 0) ?>"
					data-cli-novo="<?= (int)($row['novo_mes'] ?? 0) ?>"
					data-cli-aniv="<?= (int)($row['aniversariante'] ?? 0) ?>"
					data-cli-top10="<?= (int)($row['top_receita'] ?? 0) ?>"
					data-cli-sem-contato="<?= (int)($row['sem_contato'] ?? 0) ?>"
					data-cli-edit-url="<?= h($url360) ?>"
					data-cli-ord="<?= (int)$idxRow ?>"
					style="cursor:pointer;"
					tabindex="0">
					<td><span style="font-size:10px;font-family:monospace;color:var(--teal);"><?= h((string)$row['codigo']) ?></span></td>
					<td>
						<div style="display:flex;align-items:center;gap:8px;">
							<span style="width:28px;height:28px;border-radius:50%;background:<?= h($avBg) ?>;color:#fff;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= h(cliProtoInitials((string)$row['nome'])) ?></span>
							<div>
								<strong style="font-size:12px;"><?= h((string)$row['nome']) ?></strong>
								<?php if (!empty($row['subline'])) : ?>
								<div style="font-size:10px;color:var(--text-muted);"><?= h((string)$row['subline']) ?></div>
								<?php endif; ?>
							</div>
						</div>
					</td>
					<td style="font-family:monospace;font-size:11px;"><?= h(function_exists('formatCnpjCpf') ? formatCnpjCpf((string)$row['doc']) : (string)$row['doc']) ?></td>
					<td style="white-space:nowrap;">
						<?php if (!empty($row['eh_cliente'])) : ?><span class="badge b-env" style="font-size:10px;"><?= h(__('Cliente')) ?></span><?php endif; ?>
						<?php if (!empty($row['eh_fornecedor'])) : ?><span class="badge b-paga" style="font-size:10px;"><?= h(__('Fornecedor')) ?></span><?php endif; ?>
					</td>
					<td><span class="badge b-env" style="font-size:10px;"><?= h((string)$seg['short']) ?></span></td>
					<td style="font-size:11px;"><?= (string)$row['cidade'] !== '' ? h((string)$row['cidade']) : '—' ?></td>
					<td class="r" style="font-size:11px;font-weight:600;"><?= h($this->Number->currency($rec12, 'BRL')) ?></td>
					<td class="r" style="font-size:11px;<?= $aRec <= 0 ? 'color:var(--text-muted);' : '' ?>"><?= h($this->Number->currency($aRec, 'BRL')) ?></td>
					<td><?= $H->badge((string)$row['status_label'], $badgeType) ?></td>
					<td class="mu" style="font-size:11px;"><?= h((string)$row['ultima']) ?></td>
					<td class="r" style="white-space:nowrap;" onclick="event.stopPropagation()">
						<?= $this->Html->link(__('360°'), PortalUi::visao360Route((int)$reg->id) ?? ['controller' => 'Clientes', 'action' => 'visao360', $reg->id], ['class' => 'btn btn-primary btn-xs', 'data-turbo' => 'false']) ?>
						<?= $this->Html->link(__('Editar'), ['controller' => 'Clientes', 'action' => 'edit', $reg->id], ['class' => 'btn btn-ghost btn-xs', 'data-turbo' => 'false']) ?>
					</td>
				</tr>
				<?php $idxRow++; endforeach; ?>
			</tbody>
		</table>
		<p id="cli-proto-empty" style="display:none;padding:24px;text-align:center;color:var(--text-muted);margin:0;"><?= h(__('Nenhum cliente corresponde aos filtros.')) ?></p>
	</div>
</div>
</div>

<style>
#pg-clientes-lista .cli-kpi-card.cli-kpi-active { box-shadow: 0 0 0 2px var(--teal-mid, var(--teal)); }
#pg-clientes-lista .cli-proto-chip.active { background: var(--teal-light, #e8faf4); border-color: var(--teal); color: var(--teal-dark); }
</style>

<?= $this->element('ClientesPrototype/crm_lista_script') ?>
