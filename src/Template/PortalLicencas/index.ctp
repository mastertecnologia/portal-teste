<?php /** @var array<string,int> $licKpi @var bool $licMigrationHint */ $k = (array)($licKpi ?? []); ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= h(__('Licenciamento')) ?></h1>
<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn"><?= h(__('Módulo em configuração pela equipe.')) ?></div>
<?php endif; ?>
<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Licenças ativas')) ?></div><div class="stat-n"><?= (int)($k['licencas_ativas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Solicitações abertas')) ?></div><div class="stat-n"><?= (int)($k['solicitacoes_abertas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Itens no cofre')) ?></div><div class="stat-n"><?= (int)($k['itens_cofre'] ?? 0) ?></div></div>
</div>
<div style="display:flex;flex-wrap:wrap;gap:8px;">
	<?= $this->Html->link(__('Ver licenças'), ['action' => 'licencas'], ['class' => 'btn btn-primary btn-sm']) ?>
	<?= $this->Html->link(__('Cofre'), ['action' => 'cofre'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Solicitar licença'), ['action' => 'solicitar'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Financeiro'), ['action' => 'financeiro'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
