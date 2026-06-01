<?php
/**
 * pg-config-integracoes — documentação das APIs existentes (sem alterar contratos).
 *
 * @var \App\View\AppView $this
 */
$endpoints = [
	['/clientes/list-api', 'Clientes', 'listAPI', 'ERP→Portal', 'clientes'],
	['/clientes/add-api', 'Clientes', 'addAPI', 'ERP→Portal', 'clientes, clicontratos'],
	['/produtos/list-api', 'Produtos', 'listAPI', 'ERP→Portal', 'produtos'],
	['/produtos/add-api', 'Produtos', 'addAPI', 'ERP→Portal', 'produtos'],
	['/ordensservico/list-api', 'Ordensservico', 'listAPI', 'Portal→ERP', 'ordensservico'],
	['/ordensservico/refresh-api', 'Ordensservico', 'refreshAPI', 'ERP→Portal', 'ordensservico'],
];
?>
<div style="margin-bottom:14px;">
	<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
		PGM ERP › <?= $this->Html->link(__('Configurações'), ['action' => 'config'], ['style' => 'color:var(--teal)']) ?> › <?= h(__('Integrações')) ?>
	</div>
	<h1 style="font-size:22px;font-weight:600;">🔌 <?= h(__('Integrações')) ?></h1>
	<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Contratos HTTP e SOAP mantidos. URL ERP: empresas.urlerp.')) ?></p>
</div>

<div class="card" style="margin-bottom:14px;">
	<p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">
		<?= h(__('Documentação completa:')) ?>
		<code>docs/PGM_ERP_INTEGRACOES_GRID.md</code> ·
		<code>config/erp_api.php</code>
	</p>
	<table class="tbl">
		<thead>
			<tr>
				<th><?= h(__('Endpoint')) ?></th>
				<th><?= h(__('Controller')) ?></th>
				<th><?= h(__('Direção')) ?></th>
				<th><?= h(__('Tabelas')) ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($endpoints as $row) : ?>
			<tr>
				<td><code><?= h($row[0]) ?></code></td>
				<td><?= h($row[1] . '::' . $row[2]) ?></td>
				<td><?= h($row[3]) ?></td>
				<td><?= h($row[4]) ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="card">
	<div class="sec-title">SOAP / WebGrid</div>
	<ul style="font-size:12px;color:var(--text-muted);margin-left:18px;line-height:1.7;">
		<li><?= h(__('Base: empresas.urlerp (não em config estático)')) ?></li>
		<li><?= h(__('Estoque/preço: GetEstoqueProdutos (produtos, OS)')) ?></li>
		<li><?= h(__('Contratos SLA: ClicontratosController WSDL')) ?></li>
		<li><?= h(__('NF-e: FiscalNotasController::sincronizarErp')) ?></li>
	</ul>
</div>
