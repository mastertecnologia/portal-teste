<?php /** @var array<int,array<string,mixed>> $licEmpresas */ $items = (array)($licEmpresas ?? []); ?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">🏢 <?= h(__('Empresas-cliente')) ?></h1>
	<?= $this->Html->link('+ ' . __('Nova empresa'), ['action' => 'view', 'empresa-nova'], ['class' => 'btn btn-primary btn-sm']) ?>
	<?= $this->Html->link(__('Cadastro completo'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
	<?php if ($items === []) : ?>
	<p><?= h(__('Nenhum cliente ativo.')) ?></p>
	<?php else : foreach ($items as $e) : ?>
	<a class="card" href="<?= h($this->Url->build(['action' => 'view', 'empresa-detalhe', '?' => ['id' => (int)$e['id']]])) ?>" style="text-decoration:none;color:inherit;display:block;">
		<strong><?= h($e['nome']) ?></strong>
		<div style="font-size:11px;color:var(--text-muted);margin:4px 0 8px;"><?= h($e['cnpj'] ?: '—') ?></div>
		<div class="g3" style="gap:6px;font-size:12px;">
			<div><span style="color:var(--text-muted);"><?= h(__('Licenças')) ?></span><br><strong><?= (int)$e['licencas'] ?></strong></div>
			<div><span style="color:var(--text-muted);"><?= h(__('Dispositivos')) ?></span><br><strong><?= (int)$e['dispositivos'] ?></strong></div>
			<div><span style="color:var(--text-muted);"><?= h(__('Anual')) ?></span><br><strong>R$ <?= h(number_format((float)$e['valor_anual'], 0, ',', '.')) ?></strong></div>
		</div>
		<?php if ((int)$e['vencidas'] > 0) : ?>
		<span class="badge" style="margin-top:8px;background:#FEE2E2;color:#991B1B;"><?= h(__('{0} vencida(s)', (int)$e['vencidas'])) ?></span>
		<?php endif; ?>
	</a>
	<?php endforeach; endif; ?>
</div>
