<?php
/**
 * @var object $ticket
 * @var object $report
 * @var array $checklist
 * @var array $evidenceUris
 */
$title = 'Laudo técnico — OS ' . (int)($ticket->id ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
		h1 { font-size: 14pt; }
		table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		th, td { border: 1px solid #999; padding: 4px; text-align: left; }
	</style>
	<title><?= h($title) ?></title>
</head>
<body>
	<h1><?= h($title) ?></h1>
	<p><strong>Causa provável</strong><br><?= nl2br(h((string)($report->causa_provavel ?? '—'))) ?></p>
	<p><strong>Conclusão técnica</strong><br><?= nl2br(h((string)($report->conclusao_tecnica ?? '—'))) ?></p>
	<p><strong>Condição / estado</strong> <?= h((string)($report->condition_status ?? '—')) ?></p>
	<h2>Checklist de inspeção</h2>
	<table>
		<tr><th>Item</th><th>Status</th><th>Observação</th></tr>
		<?php if (!empty($checklist)) : foreach ($checklist as $c) : ?>
		<tr>
			<td><?= h((string)($c->item_nome ?? '')) ?></td>
			<td><?= h((string)($c->status ?? '')) ?></td>
			<td><?= h((string)($c->observacao ?? '')) ?></td>
		</tr>
		<?php endforeach; else: ?>
		<tr><td colspan="3">Nenhum item.</td></tr>
		<?php endif; ?>
	</table>
	<h2>Fotos / evidências</h2>
	<?php if (!empty($evidenceUris)) : foreach ($evidenceUris as $i => $uri) : ?>
		<p><img src="<?= h($uri) ?>" style="max-width: 45%; height: auto; margin: 4px;" alt="Evidência <?= (int)$i + 1 ?>" /></p>
	<?php endforeach; else: ?>
		<p style="color:#666;">Nenhuma imagem anexada como evidência.</p>
	<?php endif; ?>
	<p style="margin-top:24px;"><strong>Assinatura do responsável</strong> ________________________________</p>
</body>
</html>
