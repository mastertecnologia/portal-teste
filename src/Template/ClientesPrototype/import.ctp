<?php
/**
 * Importador de clientes — placeholder com fluxo visual em 4 etapas.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$steps = [
	['label' => __('Selecionar arquivo'), 'state' => 'active'],
	['label' => __('Mapear colunas'), 'state' => 'pending'],
	['label' => __('Validar duplicatas'), 'state' => 'pending'],
	['label' => __('Importar'), 'state' => 'pending'],
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Clientes · Importação')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📥 <?= h(__('Importar clientes em lote')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Lista'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($steps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('1. Selecionar arquivo')) ?></div>
	<div style="border:2px dashed var(--border);border-radius:var(--radius-lg);padding:42px 22px;text-align:center;background:var(--bg-surface);">
		<div style="font-size:42px;margin-bottom:12px;">📂</div>
		<strong style="display:block;margin-bottom:6px;"><?= h(__('Arraste seu arquivo aqui')) ?></strong>
		<div style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
			<?= h(__('XLSX, CSV · UTF-8 · primeira linha = cabeçalho · até 10.000 linhas')) ?>
		</div>
		<button type="button" class="btn btn-primary btn-sm" disabled><?= h(__('Selecionar arquivo')) ?></button>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Modelo esperado de colunas')) ?></div>
	<table class="tbl" style="margin:0;">
		<thead><tr><th><?= h(__('Coluna')) ?></th><th><?= h(__('Obrigatório')) ?></th><th><?= h(__('Exemplo')) ?></th></tr></thead>
		<tbody>
			<?php foreach ([
				['nome', true, 'Fellicci Móveis Ltda'],
				['cnpj', true, '12.345.678/0001-90'],
				['tipo', false, '1 (PF) | 2 (PJ)'],
				['email', false, 'contato@fellicci.com.br'],
				['fone', false, '11 4001-1234'],
				['endereco', false, 'Av. Paulista, 1000'],
				['bairro', false, 'Bela Vista'],
				['estado', false, 'SP'],
			] as $col) : ?>
				<tr>
					<td style="font-family:monospace;font-size:12px;font-weight:600;color:var(--teal-dark);"><?= h((string)$col[0]) ?></td>
					<td><?= $col[1] ? '<span class="badge b-paga">' . h(__('Sim')) . '</span>' : '<span class="badge b-arq">' . h(__('Não')) . '</span>' ?></td>
					<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h((string)$col[2]) ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Regras de duplicata')) ?></div>
	<ul style="font-size:12px;line-height:1.8;margin-left:18px;">
		<li><?= h(__('Mesma combinação CNPJ + idempresa: registro existente é atualizado (upsert)')) ?></li>
		<li><?= h(__('CNPJ inválido ou ausente: linha vai para fila de revisão manual')) ?></li>
		<li><?= h(__('E-mail/Telefone vazios: cliente é importado, mas marcado para enriquecimento')) ?></li>
	</ul>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Upload completo ainda está disponível apenas no módulo Clientes clássico.')) ?>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Importador clássico'), ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
