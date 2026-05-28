<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Configurador de produto')) ?></div>
	<p style="font-size:12px;color:var(--text-muted);margin:0 0 12px;"><?= h(__('Produtos com ficha técnica e/ou BOM cadastrados.')) ?></p>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Produto')) ?></th><th class="r"><?= h(__('Fichas')) ?></th><th class="r"><?= h(__('Itens BOM')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhum produto configurável.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr><td><?= h($r['codigo']) ?></td><td><?= h($r['produto']) ?></td><td class="r"><?= (int)$r['fichas'] ?></td><td class="r"><?= (int)$r['bom_itens'] ?></td></tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
