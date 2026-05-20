<?php
/**
 * Importador de produtos — placeholder com fluxo visual em 4 etapas.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$steps = [
	['label' => __('Selecionar arquivo'), 'state' => 'active'],
	['label' => __('Mapear colunas'), 'state' => 'pending'],
	['label' => __('Validar amostra'), 'state' => 'pending'],
	['label' => __('Importar'), 'state' => 'pending'],
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Produtos · Importação')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📥 <?= h(__('Importar produtos em lote')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Excel ou CSV · até 5.000 linhas por arquivo')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Lista'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($steps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('1. Selecionar arquivo')) ?></div>
	<div style="border:2px dashed var(--border);border-radius:var(--radius-lg);padding:42px 22px;text-align:center;background:var(--bg-surface);">
		<div style="font-size:42px;margin-bottom:12px;">📂</div>
		<strong style="display:block;margin-bottom:6px;"><?= h(__('Arraste seu arquivo aqui')) ?></strong>
		<div style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
			<?= h(__('XLSX, CSV ou TSV · UTF-8 · primeira linha = cabeçalho')) ?>
		</div>
		<button type="button" class="btn btn-primary btn-sm" disabled><?= h(__('Selecionar arquivo')) ?></button>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Modelo esperado de colunas')) ?></div>
	<table class="tbl" style="margin:0;">
		<thead>
			<tr><th><?= h(__('Coluna')) ?></th><th><?= h(__('Obrigatório')) ?></th><th><?= h(__('Exemplo')) ?></th></tr>
		</thead>
		<tbody>
			<?php foreach ([
				['codigo', true, 'HW-001'],
				['descricao', true, 'Computador Work Office AMD'],
				['tipo', false, 'prod | serv | lic | loc'],
				['unidade', false, 'un'],
				['vlunitario', true, '7890.00'],
				['ncm', false, '8471.50.10'],
			] as $col) :
			?>
				<tr>
					<td style="font-family:monospace;font-size:12px;font-weight:600;color:var(--teal-dark);"><?= h((string)$col[0]) ?></td>
					<td><?= $col[1] ? '<span class="badge b-paga">' . h(__('Sim')) . '</span>' : '<span class="badge b-arq">' . h(__('Não')) . '</span>' ?></td>
					<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h((string)$col[2]) ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Upload completo ainda está disponível apenas no módulo Produtos clássico. A interface premium acima é o desenho do fluxo unificado em desenvolvimento.')) ?>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Importador clássico'), ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
