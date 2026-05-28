<?php
/** @var array<int,array<string,mixed>> $pcpRows */
$rows = (array)($pcpRows ?? []);
?>
<div class="card">
	<div class="sec-title"><?= h(__('MRP · Necessidades de material')) ?></div>
	<p style="font-size:12px;color:var(--text-muted);margin:0 0 12px;"><?= h(__('Calculado a partir de OPs abertas e estrutura BOM (sem saldo inventado).')) ?></p>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Componente')) ?></th><th><?= h(__('OP')) ?></th><th class="r"><?= h(__('Necessidade')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Sem necessidades calculadas.')) ?></td></tr>
			<?php else : ?>
				<?php foreach ($rows as $r) : ?>
				<tr><td><?= h($r['componente']) ?></td><td><?= h($r['op']) ?></td><td class="r"><?= h(number_format((float)$r['necessidade'], 4, ',', '.')) ?></td></tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
