<?php /** @var array<string,mixed> $licEmpresa */ $e = (array)($licEmpresa ?? []); $lics = (array)($e['licencas_rows'] ?? []); ?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($e['nome'] ?? '') ?></h1>
		<p style="font-size:12px;color:var(--text-muted);margin:4px 0 0;"><?= h($e['cnpj'] ?: '') ?> · <?= h($e['email'] ?: '') ?></p>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Cadastro cliente'), ['controller' => 'ClientesPrototype', 'action' => 'view', (int)($e['id'] ?? 0)], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Empresas'), ['action' => 'view', 'empresas'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Licenças')) ?></div><div class="stat-n"><?= (int)($e['licencas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Dispositivos')) ?></div><div class="stat-n"><?= (int)($e['dispositivos'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Valor anual')) ?></div><div class="stat-n">R$ <?= h(number_format((float)($e['valor_anual'] ?? 0), 2, ',', '.')) ?></div></div>
</div>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Produto')) ?></th><th><?= h(__('Status')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($lics === []) : ?>
		<tr><td colspan="4" style="text-align:center;padding:20px;"><?= h(__('Sem licenças.')) ?></td></tr>
		<?php else : foreach ($lics as $lic) : ?>
		<tr>
			<td><?= h($lic['codigo']) ?></td>
			<td><?= h($lic['produto']) ?></td>
			<td><?= h($lic['status']) ?></td>
			<td><?= $this->Html->link(__('Ver'), ['action' => 'licencaDetalhe', (int)$lic['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
