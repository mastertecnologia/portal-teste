<?php /** @var array<int,array<string,mixed>> $licCofreItens */ $items = (array)($licCofreItens ?? []); ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= h(__('Cofre')) ?></h1>
<p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;"><?= h(__('Metadados das credenciais vinculadas à sua empresa. Segredos não são exibidos no portal.')) ?></p>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Título')) ?></th><th><?= h(__('Nível')) ?></th><th><?= h(__('Licença')) ?></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="3" style="text-align:center;padding:24px;"><?= h(__('Nenhum item.')) ?></td></tr>
		<?php else : foreach ($items as $row) : ?>
		<tr>
			<td><?= h($row['titulo']) ?></td>
			<td><?= h($row['nivel']) ?></td>
			<td><?= h($row['licenca_codigo'] ?: '—') ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
