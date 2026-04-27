<?php
/**
 * Ficha somente-leitura do Ativo.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asset $asset
 * @var array $tickets
 */
$this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Ativos', ['controller' => 'Ativos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($asset->descricao ?: ('#' . $asset->id), '#', ['class' => 'breadcrumb-item active']);
$this->append('css', $this->element('pgm_premium_css', ['name' => 'ativos-premium']));

$idTag = $asset->identificador ?: ('ATV-' . str_pad((string)$asset->id, 6, '0', STR_PAD_LEFT));
$qrPayload = $asset->codigo_qr ?: $idTag;
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($qrPayload);

$tipos = [
	'notebook' => 'Notebook', 'desktop' => 'Desktop', 'servidor' => 'Servidor',
	'impressora' => 'Impressora', 'switch' => 'Switch', 'roteador' => 'Roteador',
	'firewall' => 'Firewall', 'access_point' => 'Access Point', 'storage' => 'Storage / NAS',
	'monitor' => 'Monitor', 'mobile' => 'Mobile / Tablet', 'nobreak' => 'Nobreak',
	'camera' => 'Câmera', 'periferico' => 'Periférico', 'software' => 'Software / Licença',
	'outro' => 'Outro',
];
$statusLabels = [
	'em_uso' => 'Em uso', 'estoque' => 'Em estoque', 'manutencao' => 'Em manutenção',
	'reservado' => 'Reservado', 'descartado' => 'Descartado', 'perdido' => 'Perdido',
];
$statusKey = (string)($asset->status_operacional ?? '');
$statusBadge = '<span class="atv-badge atv-badge-' . h($statusKey ? str_replace('_', '-', $statusKey) : 'estoque') . '"><span class="atv-badge-dot"></span>' . h($statusLabels[$statusKey] ?? '—') . '</span>';

$fmt = function ($d) {
	if (empty($d)) {
		return '—';
	}
	if ($d instanceof \DateTimeInterface) {
		return $d->format('d/m/Y');
	}
	$t = strtotime((string)$d);

	return $t ? date('d/m/Y', $t) : '—';
};

$cli = $asset->cliente ?? null;
$cliNome = $cli ? ($cli->razaosocial ?: ($cli->nomefantasia ?: ($cli->nome ?: ('Cliente #' . $cli->id)))) : '—';
?>
<div class="col-md-12 p-0">
<div class="atv-root">
	<div class="atv-topbar">
		<div>
			<div class="atv-eyebrow">Cadastros &rsaquo; CMDB</div>
			<h1 class="atv-h1">
				<?= h($asset->descricao ?: 'Ativo') ?>
				<span class="atv-form-head-id" style="margin-left:10px"><?= h($idTag) ?></span>
				<?= $statusBadge ?>
			</h1>
		</div>
		<div class="atv-topbar-actions">
			<?= $this->Html->link('<i class="fas fa-edit"></i> Editar', ['action' => 'edit', $asset->id], ['class' => 'btn-atv-outline', 'escape' => false]) ?>
			<?= $this->Html->link('<i class="fas fa-qrcode"></i> Etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-atv-outline', 'escape' => false, 'target' => '_blank']) ?>
			<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn-atv-outline', 'escape' => false]) ?>
		</div>
	</div>

	<div class="atv-view-grid">
		<div>
			<div class="atv-section">
				<div class="atv-section-title">Identificação</div>
				<dl class="atv-info-list">
					<dt>Cliente</dt><dd><?= h($cliNome) ?></dd>
					<dt>Tipo</dt><dd><?= h($tipos[$asset->tipo ?? ''] ?? ($asset->tipo ?: '—')) ?></dd>
					<dt>Categoria</dt><dd><?= h($asset->categoria ?: '—') ?></dd>
					<dt>Patrimônio</dt><dd><?= h($asset->patrimonio ?: '—') ?></dd>
					<dt>Identificador</dt><dd class="atv-mono"><?= h($idTag) ?></dd>
					<dt>Código QR</dt><dd class="atv-mono"><?= h($asset->codigo_qr ?: '—') ?></dd>
				</dl>
			</div>

			<div class="atv-section">
				<div class="atv-section-title">Hardware / Rede</div>
				<dl class="atv-info-list">
					<dt>Marca</dt><dd><?= h($asset->marca ?: '—') ?></dd>
					<dt>Modelo</dt><dd><?= h($asset->modelo ?: '—') ?></dd>
					<dt>Nº de série</dt><dd class="atv-mono"><?= h($asset->numero_serie ?: '—') ?></dd>
					<dt>Hostname</dt><dd class="atv-mono"><?= h($asset->hostname ?: '—') ?></dd>
					<dt>IP</dt><dd class="atv-mono"><?= h($asset->ip ?: '—') ?></dd>
					<dt>MAC</dt><dd class="atv-mono"><?= h($asset->mac ?: '—') ?></dd>
					<dt>Sistema</dt><dd><?= h($asset->sistema_operacional ?: '—') ?></dd>
					<dt>Localização</dt><dd><?= h($asset->localizacao ?: '—') ?></dd>
				</dl>
			</div>

			<div class="atv-section">
				<div class="atv-section-title">Garantia &amp; Financeiro</div>
				<dl class="atv-info-list">
					<dt>Aquisição</dt><dd><?= $fmt($asset->dt_aquisicao) ?></dd>
					<dt>Instalação</dt><dd><?= $fmt($asset->dt_instalacao) ?></dd>
					<dt>Fim da garantia</dt><dd><?= $fmt($asset->dt_garantia_fim) ?></dd>
					<dt>Fornecedor</dt><dd><?= h($asset->fornecedor ?: '—') ?></dd>
					<dt>Custo</dt><dd><?= $asset->custo_aquisicao !== null ? 'R$ ' . number_format((float)$asset->custo_aquisicao, 2, ',', '.') : '—' ?></dd>
					<dt>Propriedade</dt><dd><?= h(ucfirst((string)($asset->propriedade ?: '—'))) ?></dd>
				</dl>
				<?php if (!empty($asset->observacoes)) : ?>
					<div style="margin-top:12px;color:var(--atv-text2);font-size:13px;white-space:pre-wrap"><?= h($asset->observacoes) ?></div>
				<?php endif; ?>
			</div>

			<div class="atv-section">
				<div class="atv-section-title">Histórico de chamados</div>
				<?php if (empty($tickets)) : ?>
					<div class="atv-empty">Sem chamados vinculados a este ativo.</div>
				<?php else : ?>
					<table class="atv-table">
						<thead>
							<tr>
								<th>Ticket</th>
								<th>Papel</th>
								<th>Vinculado em</th>
								<th class="atv-actions">Ações</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($tickets as $ta) :
							$tk = $ta->ticket ?? null;
							$num = $tk ? '#' . (int)$tk->id : '—';
							$titulo = $tk ? ($tk->titulo ?? '') : '';
							$dt = $ta->created instanceof \DateTimeInterface ? $ta->created->format('d/m/Y H:i') : '';
						?>
							<tr>
								<td class="atv-mono"><?= h($num) ?></td>
								<td><?= h(ucfirst((string)($ta->papel ?: 'afetado'))) ?></td>
								<td><?= h($dt) ?></td>
								<td class="atv-actions">
									<?php if ($tk) :
										echo $this->Html->link('<i class="fas fa-external-link-alt"></i>', ['controller' => 'Tickets', 'action' => 'view', $tk->id], ['class' => 'btn-atv-icon', 'escape' => false, 'title' => $titulo]);
									endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<div class="atv-aside-card" style="text-align:center">
				<img src="<?= h($qrUrl) ?>" alt="QR" style="max-width:160px"/>
				<div class="atv-mono" style="margin-top:8px"><?= h($qrPayload) ?></div>
				<div style="margin-top:10px">
					<?= $this->Html->link('<i class="fas fa-print"></i> Imprimir etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-atv-outline', 'escape' => false, 'target' => '_blank']) ?>
				</div>
			</div>

			<div class="atv-aside-card" style="margin-top:14px">
				<div class="atv-section-title">Status</div>
				<div style="margin-bottom:10px"><?= $statusBadge ?></div>
				<div class="atv-info-list" style="grid-template-columns:1fr">
					<div>
						<dt>Cadastro</dt>
						<dd><span class="atv-badge <?= $asset->ativo ? 'atv-badge-on' : 'atv-badge-off' ?>"><span class="atv-badge-dot"></span><?= $asset->ativo ? 'Ativo' : 'Inativo' ?></span></dd>
					</div>
					<div>
						<dt>Responsável</dt>
						<dd><?= h(($asset->responsavel->name ?? null) ?: ($asset->responsavel->username ?? null) ?: '—') ?></dd>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
