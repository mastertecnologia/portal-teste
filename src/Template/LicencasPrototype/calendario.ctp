<?php /** @var array<string,array<int,array<string,mixed>>> $licCalendario */ $meses = (array)($licCalendario ?? []); ?>
<h1 style="font-size:22px;margin-bottom:14px;">📅 <?= h(__('Calendário de vencimentos')) ?></h1>
<?php if ($meses === []) : ?>
<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h(__('Sem vencimentos nos próximos meses.')) ?></p></div>
<?php else : foreach ($meses as $mes => $items) : ?>
<div class="card" style="margin-bottom:12px;">
	<div class="sec-title"><?= h($mes) ?></div>
	<ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;">
		<?php foreach ((array)$items as $it) : ?>
		<li>
			<?= h($it['fim']) ?> — <strong><?= h($it['codigo']) ?></strong> (<?= h($it['cliente']) ?>)
			<?= $this->Html->link(__('→'), ['action' => 'licencaDetalhe', (int)$it['id']], ['style' => 'margin-left:6px;']) ?>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endforeach; endif; ?>
