<?php
/**
 * Ativos / CMDB — listagem (tema claro alinhado a Clientes).
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
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

if (!function_exists('atvBadgeStatusCli')) {
	function atvBadgeStatusCli(?string $status): string {
		$labels = [
			'em_uso' => 'Em uso', 'estoque' => 'Em estoque', 'manutencao' => 'Em manutenção',
			'reservado' => 'Reservado', 'descartado' => 'Descartado', 'perdido' => 'Perdido',
		];
		$badgeClass = [
			'em_uso' => 'success', 'estoque' => 'secondary', 'manutencao' => 'warning',
			'reservado' => 'info', 'descartado' => 'danger', 'perdido' => 'danger',
		];
		$key = (string)($status ?? '');
		$label = $labels[$key] ?? '—';
		$bc = $badgeClass[$key] ?? 'secondary';

		return '<span class="badge badge-' . h($bc) . '">' . h($label) . '</span>';
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
<?= $this->element('pgm_premium_css', ['name' => 'clientes-premium']) ?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="col-md-12 p-0">
<div class="cli-root cli-layout-unificado">

	<div class="atv-cli-list-head">
		<div>
			<div class="atv-cli-eyebrow">Cadastros &rsaquo; CMDB</div>
			<h1 class="atv-cli-h1">Ativos de TI / CMDB</h1>
		</div>
		<?= $this->Html->link('<i class="fas fa-plus"></i> Novo ativo', ['action' => 'add'], ['class' => 'btn-cli-primary', 'escape' => false]) ?>
	</div>

	<div class="atv-cli-kpi-strip">
		<div class="cli-kpi atv-cli-kpi--static">
			<div class="cli-kpi-label">Total de ativos</div>
			<div class="cli-kpi-val"><?= (int)$kpis['total'] ?></div>
		</div>
		<div class="cli-kpi atv-cli-kpi--static">
			<div class="cli-kpi-label">Em uso</div>
			<div class="cli-kpi-val teal"><?= (int)$kpis['em_uso'] ?></div>
		</div>
		<div class="cli-kpi atv-cli-kpi--static">
			<div class="cli-kpi-label">Em estoque</div>
			<div class="cli-kpi-val muted"><?= (int)$kpis['estoque'] ?></div>
		</div>
		<div class="cli-kpi atv-cli-kpi--static">
			<div class="cli-kpi-label">Em manutenção</div>
			<div class="cli-kpi-val atv-cli-kpi-val--amber"><?= (int)$kpis['manutencao'] ?></div>
		</div>
		<div class="cli-kpi atv-cli-kpi--static">
			<div class="cli-kpi-label">Descartados</div>
			<div class="cli-kpi-val atv-cli-kpi-val--red"><?= (int)$kpis['descartado'] ?></div>
		</div>
	</div>

	<div class="cli-list-card">
		<?= $this->Form->create(null, ['type' => 'get', 'class' => 'atv-cli-filter-form', 'valueSources' => ['query']]) ?>
		<div class="atv-cli-filter-grid">
			<div>
				<label class="atv-cli-filter-label">Cliente</label>
				<?= $this->Form->select('idcliente', ['' => '— Todos —'] + $clientesOpts, ['default' => $filtros['idcliente'] ?? '', 'class' => 'form-control']) ?>
			</div>
			<div>
				<label class="atv-cli-filter-label">Tipo</label>
				<?= $this->Form->select('tipo', $tiposOpts, ['default' => $filtros['tipo'] ?? '', 'class' => 'form-control']) ?>
			</div>
			<div>
				<label class="atv-cli-filter-label">Status</label>
				<?= $this->Form->select('status', ['' => '— Todos —'] + $statusOpts, ['default' => $filtros['status'] ?? '', 'class' => 'form-control']) ?>
			</div>
			<div>
				<label class="atv-cli-filter-label">Buscar</label>
				<input type="search" name="q" class="form-control" value="<?= h($filtros['q'] ?? '') ?>" placeholder="Descrição, série, hostname, QR…" />
			</div>
			<div class="atv-cli-filter-submit">
				<label class="atv-cli-filter-label">&nbsp;</label>
				<button type="submit" class="btn-cli-outline" style="width:100%;justify-content:center;display:inline-flex;align-items:center;gap:6px;">
					<i class="fas fa-filter"></i> Filtrar
				</button>
			</div>
		</div>
		<?= $this->Form->end() ?>

		<div class="atv-cli-table-toolbar">
			<h2 class="atv-cli-table-title"><?= count($ativos) ?> ativo(s) listado(s)</h2>
			<div class="d-flex flex-wrap" style="gap:8px;">
				<button type="button" class="btn-cli-outline" disabled title="Disponível na fase 2"><i class="fas fa-file-import"></i> Importar CSV</button>
				<button type="button" class="btn-cli-outline" onclick="window.print();return false;"><i class="fas fa-print"></i> Imprimir</button>
			</div>
		</div>

		<div class="cli-table-wrap">
			<div class="cli-table-card">
				<?php if (empty($ativos)) : ?>
					<div class="text-center text-muted py-5">
						<i class="fas fa-server fa-2x mb-3 d-block" style="opacity:.35"></i>
						Nenhum ativo encontrado para os filtros aplicados.
					</div>
				<?php else : ?>
				<div class="table-responsive">
					<table class="table cli-table mb-0">
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
								<th class="text-right">Ações</th>
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
								<td><code><?= h($idTag) ?></code></td>
								<td><?= h($a->descricao ?: '—') ?></td>
								<td><?= h($tipoLabel) ?></td>
								<td><?= h($cliNome) ?></td>
								<td><?= h(trim((string)($a->marca ?? '') . ' ' . (string)($a->modelo ?? ''))) ?: '—' ?></td>
								<td><code><?= h($a->numero_serie ?: '—') ?></code></td>
								<td><code><?= h($a->hostname ?: '—') ?></code></td>
								<td><?= atvBadgeStatusCli($a->status_operacional) ?></td>
								<td><?= h(atvFmtDate($a->dt_garantia_fim)) ?></td>
								<td class="text-right text-nowrap">
									<?= $this->Html->link('<i class="fas fa-eye"></i>', ['action' => 'view', $a->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => 'Ver']) ?>
									<?= $this->Html->link('<i class="fas fa-edit"></i>', ['action' => 'edit', $a->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => 'Editar']) ?>
									<?= $this->Html->link('<i class="fas fa-qrcode"></i>', ['action' => 'qr', $a->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => 'Etiqueta/QR', 'target' => '_blank']) ?>
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
</div>
