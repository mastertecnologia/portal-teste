<?php
/**
 * @var array<int,array<string,mixed>> $licCategorias
 * @var string $licWizardReturn
 */
$cats = (array)($licCategorias ?? []);
$return = trim((string)($licWizardReturn ?? ''));
$voltar = $return === 'nova'
	? ['action' => 'view', 'nova']
	: ['action' => 'view', 'catalogo'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM ERP › <?= $this->Html->link(__('Licenciamento'), ['action' => 'dashboard'], ['style' => 'color:var(--teal)']) ?>
			› <?= h(__('Categorias')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📂 <?= h(__('Categorias de produto')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Organize o catálogo usado no wizard de nova licença')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Voltar'), $voltar, ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova categoria'), ['action' => 'view', 'categoria-editar', '?' => $return !== '' ? ['return' => $return] : []], ['class' => 'btn btn-primary btn-sm']) ?>
		<?= $this->Html->link(__('Catálogo'), ['action' => 'view', 'catalogo'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:14px;">
	<?php foreach ($cats as $c) :
		$border = 'var(--border)';
		$bg = '#fff';
		$cod = mb_strtoupper((string)($c['codigo'] ?? ''));
		if ($cod === 'OFFICE') {
			$border = 'var(--teal-mid)';
			$bg = 'linear-gradient(135deg,#D1F2E7,#fff)';
		}
		?>
	<a href="<?= h($this->Url->build(['action' => 'view', 'categoria-editar', '?' => array_filter(['id' => (int)$c['id'], 'return' => $return !== '' ? $return : null])])) ?>" class="card" style="text-align:center;padding:14px;text-decoration:none;color:inherit;border-left:3px solid <?= h($border) ?>;background:<?= h($bg) ?>;">
		<div style="font-size:30px;margin-bottom:4px;"><?= h($c['icon'] ?? '📦') ?></div>
		<strong style="font-size:13px;"><?= h($c['nome']) ?></strong>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('{0} produtos', (int)($c['produtos'] ?? 0))) ?></div>
		<div style="font-size:10px;color:var(--text-hint);margin-top:4px;"><?= h($cod) ?></div>
	</a>
	<?php endforeach; ?>
</div>

<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Nome')) ?></th><th><?= h(__('Produtos')) ?></th><th></th></tr></thead>
		<tbody>
		<?php foreach ($cats as $c) : ?>
		<tr>
			<td><strong><?= h($c['codigo']) ?></strong></td>
			<td><?= h($c['nome']) ?></td>
			<td><?= (int)($c['produtos'] ?? 0) ?></td>
			<td><?= $this->Html->link(__('Editar'), ['action' => 'view', 'categoria-editar', '?' => array_filter(['id' => (int)$c['id'], 'return' => $return !== '' ? $return : null])], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
