<?php
/**
 * Fornecedores — pg-fornecedores (paridade mockup + dados reais).
 *
 * @var array<string,mixed> $fornData
 * @var array<string,mixed> $fornFiltros
 */
$H = $this->ErpPrototype;
$d = (array)($fornData ?? []);
$counts = (array)($d['counts'] ?? []);
$items = (array)($d['items'] ?? []);
$categorias = (array)($d['categorias'] ?? []);
$f = (array)($fornFiltros ?? ['q' => '', 'status' => '', 'categoria' => '', 'pj' => true, 'pf' => false]);
$listaUrl = ['controller' => 'FornecedoresPrototype', 'action' => 'lista'];
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Cadastros'),
	'title' => __('🏭 Fornecedores'),
	'subtitle' => __('Cadastro mestre · homologação · histórico de compras · performance'),
	'actions' => [
		['label' => __('📊 Excel'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'exportCsv'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Novo fornecedor'), 'url' => ['controller' => 'FornecedoresPrototype', 'action' => 'view', 'novo'], 'class' => 'btn btn-primary btn-sm'],
	],
]) ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Total cadastrados')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($counts['total'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= (int)($counts['ativos'] ?? 0) ?> <?= h(__('ativos')) ?> · <?= (int)($counts['inativos'] ?? 0) ?> <?= h(__('inativos')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Homologados')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)($counts['homologados'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);"><?= (int)($counts['ativos'] ?? 0) > 0 ? round(100 * (int)($counts['homologados'] ?? 0) / max(1, (int)($counts['ativos'] ?? 1))) : 0 ?>% <?= h(__('dos ativos')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--teal-mid);">
		<div class="lbl"><?= h(__('Compras · 12m')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h((string)($counts['compras12_fmt'] ?? '—')) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('média')) ?> <?= h((string)($counts['compras12_media_fmt'] ?? '—')) ?>/<?= h(__('forn')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #D946A0;">
		<div class="lbl"><?= h(__('Pontualidade média')) ?></div>
		<div class="val" style="color:#7A1B5C;"><?= (int)($counts['pontualidade_media'] ?? 0) ?>%</div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('indicador operacional')) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Avaliação pendente')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($counts['analise'] ?? 0) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= h(__('em homologação')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;">
		<div class="lbl"><?= h(__('Top categorias')) ?></div>
		<div class="val" style="font-size:14px;color:#3D2D63;line-height:1.3;">
			<?php
			$topCat = array_slice($categorias, 0, 3);
			echo $topCat === [] ? '—' : h(implode(' · ', $topCat));
			?>
		</div>
	</div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 14px;">
	<form method="get" action="<?= h($this->Url->build($listaUrl)) ?>">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:240px;margin:0;">
				<input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="🔍 <?= h(__('Buscar nome, CNPJ/CPF, categoria...')) ?>">
			</div>
			<select name="status" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;">
				<option value=""><?= h(__('Todos os status')) ?></option>
				<option value="homologado"<?= (string)$f['status'] === 'homologado' ? ' selected' : '' ?>><?= h(__('★ Homologado')) ?></option>
				<option value="analise"<?= (string)$f['status'] === 'analise' ? ' selected' : '' ?>><?= h(__('⏰ Em análise')) ?></option>
				<option value="ativo"<?= (string)$f['status'] === 'ativo' ? ' selected' : '' ?>><?= h(__('Ativo')) ?></option>
				<option value="inativo"<?= (string)$f['status'] === 'inativo' ? ' selected' : '' ?>><?= h(__('⚪ Inativo')) ?></option>
			</select>
			<select name="categoria" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;">
				<option value=""><?= h(__('Todas categorias')) ?></option>
				<?php foreach ($categorias as $cat) : ?>
				<option value="<?= h($cat) ?>"<?= (string)$f['categoria'] === (string)$cat ? ' selected' : '' ?>><?= h($cat) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:10px;align-items:center;">
			<span style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Tipo fornecedor')) ?></span>
			<label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
				<input type="hidden" name="pj" value="0">
				<input type="checkbox" name="pj" value="1"<?= !empty($f['pj']) ? ' checked' : '' ?> style="width:15px;height:15px;accent-color:var(--teal);">
				<?= h(__('Pessoa jurídica')) ?>
			</label>
			<label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
				<input type="checkbox" name="pf" value="1"<?= !empty($f['pf']) ? ' checked' : '' ?> style="width:15px;height:15px;accent-color:var(--teal);">
				<?= h(__('Pessoa física')) ?>
			</label>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), $listaUrl, ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead>
				<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Código')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Fornecedor')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Categoria')) ?></th>
					<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Localização')) ?></th>
					<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Lead time')) ?></th>
					<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Pontualidade')) ?></th>
					<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Compras 12m')) ?></th>
					<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
					<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Papel')) ?></th>
					<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($items === []) : ?>
				<tr><td colspan="10" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum fornecedor cadastrado. Marque "Fornecedor" no cadastro de clientes ou crie um novo.')) ?></td></tr>
				<?php else : foreach ($items as $it) :
					$rowHref = $this->Url->build(['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']]);
					$docFmt = function_exists('formatCnpjCpf') ? formatCnpjCpf((string)$it['doc']) : (string)$it['doc'];
				?>
				<tr style="border-bottom:1px solid var(--border-light);cursor:pointer;" data-pgm-row-href="<?= h($rowHref) ?>" tabindex="0">
					<td style="padding:10px;"><span class="titulo-cod"><?= h((string)$it['codigo']) ?></span></td>
					<td style="padding:10px;">
						<strong><?= h((string)$it['nome']) ?></strong>
						<div style="font-size:11px;color:var(--text-muted);"><?= !empty($it['is_pj']) ? 'CNPJ' : 'CPF' ?> <?= h($docFmt) ?></div>
					</td>
					<td style="padding:10px;font-size:11px;"><?= h((string)$it['categoria']) ?></td>
					<td style="padding:10px;font-size:11px;"><?= h((string)$it['localizacao']) ?></td>
					<td style="padding:10px;text-align:right;"><?= h((string)$it['lead_time']) ?></td>
					<td style="padding:10px;text-align:right;">
						<strong style="color:var(--teal-dark);"><?= (int)($it['pontualidade'] ?? 0) ?>%</strong>
					</td>
					<td style="padding:10px;text-align:right;font-weight:600;"><?= h((string)$it['compras12_fmt']) ?></td>
					<td style="padding:10px;text-align:center;"><?= $H->badge((string)$it['status_label'], (string)$it['status_badge']) ?></td>
					<td style="padding:10px;text-align:center;font-size:10px;">
						<?php if (!empty($it['eh_cliente'])) : ?><span class="badge b-env"><?= h(__('Cliente')) ?></span><?php endif; ?>
						<span class="badge b-paga"><?= h(__('Fornecedor')) ?></span>
					</td>
					<td style="padding:10px;text-align:center;" onclick="event.stopPropagation()">
						<?= $this->Html->link(__('360°'), ['controller' => 'Clientes', 'action' => 'visao360', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs', 'data-turbo' => 'false']) ?>
						<?= $this->Html->link(__('Editar'), ['controller' => 'Clientes', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs', 'data-turbo' => 'false']) ?>
					</td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;font-size:12px;">
	<?= h(__('Fornecedores usam o cadastro mestre de Clientes. Marque o papel "Fornecedor" (e opcionalmente "Cliente") nos checkboxes do formulário de cadastro.')) ?>
</div>
