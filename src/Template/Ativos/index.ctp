<?php
/**
 * Ativos / CMDB — listagem premium.
 *
 * @var \App\View\AppView $this
 * @var array $ativos
 * @var array $kpis
 * @var array $clientesOpts
 * @var array $tiposOpts
 * @var array $statusOpts
 * @var array $filtros
 */
$this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Ativos', ['controller' => 'Ativos', 'action' => 'index'], ['class' => 'breadcrumb-item active']);
$this->append('css', $this->element('pgm_premium_css', ['name' => 'ativos-premium']));

if (!function_exists('atvBadgeStatus')) {
	function atvBadgeStatus(?string $status): string {
		$labels = [
			'em_uso' => 'Em uso', 'estoque' => 'Em estoque', 'manutencao' => 'Em manutenção',
			'reservado' => 'Reservado', 'descartado' => 'Descartado', 'perdido' => 'Perdido',
		];
		$key = (string)($status ?? '');
		$label = $labels[$key] ?? '—';
		$cls = 'atv-badge-' . ($key !== '' ? str_replace('_', '-', $key) : 'estoque');

		return '<span class="atv-badge ' . h($cls) . '"><span class="atv-badge-dot"></span>' . h($label) . '</span>';
	}
}
if (!function_exists('atvFmtDate')) {
	function atvFmtDate($d): string {
		if (empty($d)) {
			return '—';
		}
		if ($d instanceof \DateTimeInterface) {
			return $d->format('d/m/Y');
		}
		$t = strtotime((string)$d);

		return $t ? date('d/m/Y', $t) : '—';
	}
}
?>
<div class="col-md-12 p-0">
<div class="atv-root">
	<div class="atv-topbar">
		<div>
			<div class="atv-eyebrow">Cadastros &rsaquo; CMDB</div>
			<h1 class="atv-h1">Ativos de TI / CMDB</h1>
		</div>
		<div class="atv-topbar-actions">
			<?= $this->Html->link('<i class="fas fa-plus"></i> Novo Ativo', ['action' => 'add'], ['class' => 'btn-atv-new', 'escape' => false]) ?>
		</div>
	</div>

	<div class="atv-kpis">
		<div class="atv-kpi"><div class="atv-kpi-label">Total de Ativos</div><div class="atv-kpi-value"><?= (int)$kpis['total'] ?></div></div>
		<div class="atv-kpi"><div class="atv-kpi-label">Em uso</div><div class="atv-kpi-value green"><?= (int)$kpis['em_uso'] ?></div></div>
		<div class="atv-kpi"><div class="atv-kpi-label">Em estoque</div><div class="atv-kpi-value gray"><?= (int)$kpis['estoque'] ?></div></div>
		<div class="atv-kpi"><div class="atv-kpi-label">Em manutenção</div><div class="atv-kpi-value yellow"><?= (int)$kpis['manutencao'] ?></div></div>
		<div class="atv-kpi"><div class="atv-kpi-label">Descartados</div><div class="atv-kpi-value red"><?= (int)$kpis['descartado'] ?></div></div>
	</div>

	<?= $this->Form->create(null, ['type' => 'get', 'class' => 'atv-filters', 'valueSources' => ['query']]) ?>
		<div>
			<label class="atv-filter-label">Cliente</label>
			<?= $this->Form->select('idcliente', ['' => '— Todos —'] + $clientesOpts, ['default' => $filtros['idcliente'] ?? '']) ?>
		</div>
		<div>
			<label class="atv-filter-label">Tipo</label>
			<?= $this->Form->select('tipo', $tiposOpts, ['default' => $filtros['tipo'] ?? '']) ?>
		</div>
		<div>
			<label class="atv-filter-label">Status</label>
			<?= $this->Form->select('status', ['' => '— Todos —'] + $statusOpts, ['default' => $filtros['status'] ?? '']) ?>
		</div>
		<div>
			<label class="atv-filter-label">Buscar</label>
			<input type="search" name="q" value="<?= h($filtros['q'] ?? '') ?>" placeholder="Descrição, série, hostname, QR…" />
		</div>
		<div>
			<button type="submit" class="btn-atv-outline" style="width:100%;justify-content:center">
				<i class="fas fa-filter"></i> Filtrar
			</button>
		</div>
	<?= $this->Form->end() ?>

	<div class="atv-card">
		<div class="atv-card-head">
			<h3 class="atv-card-title"><?= count($ativos) ?> ativo(s) listado(s)</h3>
			<div class="atv-topbar-actions">
				<button class="btn-atv-outline" disabled title="Disponível na fase 2"><i class="fas fa-file-import"></i> Importar CSV</button>
				<button class="btn-atv-outline" onclick="window.print();return false;"><i class="fas fa-print"></i> Imprimir</button>
			</div>
		</div>
		<div class="atv-card-body">
			<?php if (empty($ativos)) : ?>
				<div class="atv-empty"><i class="fas fa-server fa-2x" style="opacity:.4"></i><br><br>Nenhum ativo encontrado para os filtros aplicados.</div>
			<?php else : ?>
			<div class="table-responsive">
				<table class="atv-table">
					<thead>
						<tr>
							<th>Identificador</th>
							<th>Descrição</th>
							<th>Tipo</th>
							<th>Cliente</th>
							<th>Marca/Modelo</th>
							<th>Nº Série</th>
							<th>Hostname</th>
							<th>Status</th>
							<th>Garantia</th>
							<th class="atv-actions">Ações</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($ativos as $a) :
						$cli = $a->cliente ?? null;
						$cliNome = $cli ? (
							$cli->razaosocial
							?: ($cli->nomefantasia ?: ($cli->nome ?: ('Cliente #' . $cli->id)))
						) : '—';
						$tipoLabel = $tiposOpts[$a->tipo ?? ''] ?? ($a->tipo ?: '—');
						$idTag = $a->identificador ?: ('ATV-' . str_pad((string)$a->id, 6, '0', STR_PAD_LEFT));
					?>
						<tr>
							<td class="atv-mono"><?= h($idTag) ?></td>
							<td><?= h($a->descricao ?: '—') ?></td>
							<td><?= h($tipoLabel) ?></td>
							<td><?= h($cliNome) ?></td>
							<td><?= h(trim((string)($a->marca ?? '') . ' ' . (string)($a->modelo ?? ''))) ?: '—' ?></td>
							<td class="atv-mono"><?= h($a->numero_serie ?: '—') ?></td>
							<td class="atv-mono"><?= h($a->hostname ?: '—') ?></td>
							<td><?= atvBadgeStatus($a->status_operacional) ?></td>
							<td><?= atvFmtDate($a->dt_garantia_fim) ?></td>
							<td class="atv-actions">
								<?= $this->Html->link('<i class="fas fa-eye"></i>', ['action' => 'view', $a->id], ['class' => 'btn-atv-icon', 'escape' => false, 'title' => 'Ver']) ?>
								<?= $this->Html->link('<i class="fas fa-edit"></i>', ['action' => 'edit', $a->id], ['class' => 'btn-atv-icon', 'escape' => false, 'title' => 'Editar']) ?>
								<?= $this->Html->link('<i class="fas fa-qrcode"></i>', ['action' => 'qr', $a->id], ['class' => 'btn-atv-icon', 'escape' => false, 'title' => 'Etiqueta/QR', 'target' => '_blank']) ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
</div>
