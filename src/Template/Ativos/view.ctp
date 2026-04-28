<?php
/**
 * Ficha somente-leitura do Ativo (tema claro alinhado a Clientes).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asset $asset
 * @var array $tickets
 */
$this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Ativos', ['controller' => 'Ativos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($asset->descricao ?: ('#' . $asset->id), '#', ['class' => 'breadcrumb-item active']);
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

$soEdicaoLabels = [
	'windows_pro' => 'Windows Pro', 'windows_home' => 'Windows Home',
	'windows_enterprise' => 'Windows Enterprise', 'windows_ltsc' => 'Windows LTSC',
	'windows_server' => 'Windows Server', 'linux' => 'Linux',
	'macos' => 'macOS', 'outro' => 'Outro',
];

$officeVersaoLabels = [
	'm365' => 'Microsoft 365', 'office_2024' => 'Office 2024',
	'office_2021' => 'Office 2021', 'office_2019' => 'Office 2019',
	'office_2016' => 'Office 2016', 'libreoffice' => 'LibreOffice',
	'nao_possui' => 'Não possui', 'outro' => 'Outro',
];

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
<?= $this->element('pgm_premium_css', ['name' => 'clientes-premium']) ?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="col-md-12 p-0">
<div class="cli-form-root cli-layout-unificado">

	<div class="cli-page-head">
		<div class="cli-page-head-left">
			<div class="cli-eyebrow">Cadastros &rsaquo; CMDB</div>
			<h1><?= h($asset->descricao ?: 'Ativo') ?> <span class="cli-page-head-code" translate="no"><?= h($idTag) ?></span> <?= atvBadgeStatusCli($asset->status_operacional) ?></h1>
			<p class="mb-0 text-muted"><i class="fas fa-building"></i> <?= h($cliNome) ?></p>
		</div>
		<div class="d-flex align-items-center flex-wrap pgm-gap-8">
			<?= $this->Html->link('<i class="fas fa-edit"></i> Editar', ['action' => 'edit', $asset->id], ['class' => 'btn-cli-outline', 'escape' => false]) ?>
			<?= $this->Html->link('<i class="fas fa-qrcode"></i> Etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-cli-outline', 'escape' => false, 'target' => '_blank']) ?>
			<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn-cli-outline', 'escape' => false]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8">
			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-id-card"></i></div>
					<div class="cli-section-title">Identificação</div>
				</div>
				<div class="cli-section-body">
					<dl class="atv-cli-dl">
						<dt>Cliente</dt><dd><?= h($cliNome) ?></dd>
						<dt>Tipo</dt><dd><?= h($tipos[$asset->tipo ?? ''] ?? ($asset->tipo ?: '—')) ?></dd>
						<dt>Categoria</dt><dd><?= h($asset->categoria ?: '—') ?></dd>
						<dt>Patrimônio</dt><dd><?= h($asset->patrimonio ?: '—') ?></dd>
						<dt>Identificador</dt><dd><code><?= h($idTag) ?></code></dd>
						<dt>Código QR</dt><dd><code><?= h($asset->codigo_qr ?: '—') ?></code></dd>
					</dl>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-microchip"></i></div>
					<div class="cli-section-title">Hardware / Rede</div>
				</div>
				<div class="cli-section-body">
					<dl class="atv-cli-dl">
						<dt>Marca</dt><dd><?= h($asset->marca ?: '—') ?></dd>
						<dt>Modelo</dt><dd><?= h($asset->modelo ?: '—') ?></dd>
						<dt>Nº de série</dt><dd><code><?= h($asset->numero_serie ?: '—') ?></code></dd>
						<dt>Hostname</dt><dd><code><?= h($asset->hostname ?: '—') ?></code></dd>
						<dt>IP</dt><dd><code><?= h($asset->ip ?: '—') ?></code></dd>
						<dt>MAC</dt><dd><code><?= h($asset->mac ?: '—') ?></code></dd>
						<dt>Sistema</dt><dd><?= h($asset->sistema_operacional ?: '—') ?></dd>
						<dt>Usuário</dt><dd><?= h($asset->usuario ?: '—') ?></dd>
						<dt>Senha</dt><dd><?= !empty($asset->senha) ? '********' : '—' ?></dd>
						<dt>Porta interna</dt><dd><?= $asset->porta_interna !== null && $asset->porta_interna !== '' ? h((string)$asset->porta_interna) : '—' ?></dd>
						<dt>Porta externa</dt><dd><?= $asset->porta_externa !== null && $asset->porta_externa !== '' ? h((string)$asset->porta_externa) : '—' ?></dd>
						<dt>Localização</dt><dd><?= h($asset->localizacao ?: '—') ?></dd>
					</dl>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-key"></i></div>
					<div class="cli-section-title">SO e Licenças</div>
				</div>
				<div class="cli-section-body">
					<dl class="atv-cli-dl">
						<dt>Edição do SO</dt><dd><?= h($soEdicaoLabels[$asset->so_edicao ?? ''] ?? ($asset->so_edicao ?: '—')) ?></dd>
						<dt>Chave Windows</dt><dd><?= !empty($asset->windows_chave) ? '********' : '—' ?></dd>
						<dt>Versão do Office</dt><dd><?= h($officeVersaoLabels[$asset->office_versao ?? ''] ?? ($asset->office_versao ?: '—')) ?></dd>
						<dt>Chave Office</dt><dd><?= !empty($asset->office_chave) ? '********' : '—' ?></dd>
					</dl>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-shield-alt"></i></div>
					<div class="cli-section-title">Garantia &amp; financeiro</div>
				</div>
				<div class="cli-section-body">
					<dl class="atv-cli-dl">
						<dt>Aquisição</dt><dd><?= h($fmt($asset->dt_aquisicao)) ?></dd>
						<dt>Instalação</dt><dd><?= h($fmt($asset->dt_instalacao)) ?></dd>
						<dt>Fim da garantia</dt><dd><?= h($fmt($asset->dt_garantia_fim)) ?></dd>
						<dt>Fornecedor</dt><dd><?= h($asset->fornecedor ?: '—') ?></dd>
						<dt>Custo</dt><dd><?= $asset->custo_aquisicao !== null ? 'R$ ' . number_format((float)$asset->custo_aquisicao, 2, ',', '.') : '—' ?></dd>
						<dt>Propriedade</dt><dd><?= h(ucfirst((string)($asset->propriedade ?: '—'))) ?></dd>
					</dl>
					<?php if (!empty($asset->observacoes)) : ?>
						<div class="atv-cli-notes"><?= h($asset->observacoes) ?></div>
					<?php endif; ?>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-ticket-alt"></i></div>
					<div class="cli-section-title">Histórico de chamados</div>
				</div>
				<div class="cli-section-body">
					<?php if (empty($tickets)) : ?>
						<p class="text-muted mb-0">Sem chamados vinculados a este ativo.</p>
					<?php else : ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead>
									<tr>
										<th>Ticket</th>
										<th>Papel</th>
										<th>Vinculado em</th>
										<th class="text-right">Ações</th>
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
										<td><code><?= h($num) ?></code></td>
										<td><?= h(ucfirst((string)($ta->papel ?: 'afetado'))) ?></td>
										<td><?= h($dt) ?></td>
										<td class="text-right">
											<?php if ($tk) :
												echo $this->Html->link('<i class="fas fa-external-link-alt"></i>', ['controller' => 'Tickets', 'action' => 'view', $tk->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => $titulo]);
											endif; ?>
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

		<div class="col-lg-4">
			<div class="cli-section mb-3 atv-cli-qr-side text-center">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-qrcode"></i></div>
					<div class="cli-section-title">Etiqueta</div>
				</div>
				<div class="cli-section-body">
					<img src="<?= h($qrUrl) ?>" alt="QR"/>
					<div class="mt-2"><code><?= h($qrPayload) ?></code></div>
					<div class="mt-3">
						<?= $this->Html->link('<i class="fas fa-print"></i> Imprimir etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-cli-outline', 'escape' => false, 'target' => '_blank']) ?>
					</div>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-info-circle"></i></div>
					<div class="cli-section-title">Cadastro</div>
				</div>
				<div class="cli-section-body">
					<p class="mb-2"><?= $asset->ativo ? '<span class="badge badge-success">Ativo no cadastro</span>' : '<span class="badge badge-secondary">Inativo no cadastro</span>' ?></p>
					<dl class="atv-cli-dl" style="grid-template-columns:1fr">
						<dt>Responsável</dt>
						<dd><?php
							$res = $asset->responsavel ?? null;
							echo h(($res->name ?? null) ?: ($res->username ?? null) ?: '—');
						?></dd>
					</dl>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
