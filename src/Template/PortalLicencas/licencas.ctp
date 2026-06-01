<?php /** @var array<int,array<string,mixed>> $licItems */ $items = (array)($licItems ?? []); ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= h(__('Minhas licenças')) ?></h1>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Produto')) ?></th><th><?= h(__('Assentos')) ?></th><th><?= h(__('Vigência')) ?></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="4" style="text-align:center;padding:24px;"><?= h(__('Nenhuma licença ativa.')) ?></td></tr>
		<?php else : foreach ($items as $lic) :
			$ini = $lic['inicio'];
			$fim = $lic['fim'];
			$iniS = is_object($ini) && method_exists($ini, 'format') ? $ini->format('d/m/Y') : (string)$ini;
			$fimS = is_object($fim) && method_exists($fim, 'format') ? $fim->format('d/m/Y') : (string)$fim;
			?>
		<tr>
			<td><?= h($lic['codigo']) ?></td>
			<td><?= h($lic['produto']) ?></td>
			<td><?= (int)$lic['assentos'] ?></td>
			<td><?= h(trim($iniS . ' — ' . $fimS, ' —')) ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
<p style="margin-top:12px;"><?= $this->Html->link('← ' . __('Painel'), ['action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?></p>
