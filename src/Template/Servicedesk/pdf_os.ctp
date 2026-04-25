<?php
/**
 * @var \App\Controller\AppView $this
 * @var object $ticket
 * @var int $idticket
 * @var array $timeline
 * @var string|null $signatureRelPath
 * @var string|null $signatureDataUri
 */
$title = 'Ordem de serviço nº ' . (int)$idticket;
$totalSec = 0;
if (!empty($timeline)) {
	foreach ($timeline as $ev) {
		if (!empty($ev->secondsSpent) && (int)$ev->secondsSpent > 0) {
			$totalSec += (int)$ev->secondsSpent;
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
		h1 { font-size: 14pt; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
		.meta { margin: 8px 0; }
		.ev { margin-bottom: 10px; border-left: 2px solid #0a0; padding-left: 8px; }
		.muted { color: #666; font-size: 8pt; }
	</style>
	<title><?= h($title) ?></title>
</head>
<body>
	<h1><?= h($title) ?></h1>
	<div class="meta">Cliente: <?= h((string)($ticket->solicitante ?? '—')) ?> — Assunto: <?= h((string)($ticket->assunto ?? '—')) ?></div>
	<?php if ($totalSec > 0) : ?>
		<p><strong>Total de horas (worklogs):</strong> <?= h(gmdate('H:i:s', $totalSec)) ?></p>
	<?php endif; ?>
	<h2>Timeline / histórico</h2>
	<?php if (!empty($timeline)) : foreach ($timeline as $ev) : ?>
		<div class="ev">
			<strong><?= h((string)($ev->type ?? '—')) ?></strong>
			<span class="muted"><?= h((string)($ev->createdLabel ?? $ev->created ?? '')) ?></span>
			<?php if (!empty($ev->autor)) : ?> — <?= h($ev->autor) ?><?php endif; ?><br>
			<?= nl2br(h((string)($ev->description ?? ''))) ?>
			<?php if (!empty($ev->secondsSpent) && (int)$ev->secondsSpent > 0) : ?>
				<br><em>Tempo: <?= h(gmdate('H:i:s', (int)$ev->secondsSpent)) ?></em>
			<?php endif; ?>
			<?php if (!empty($ev->billingType)) : ?>
				<br><em>Tarifação: <?= h((string)$ev->billingType) ?></em>
			<?php endif; ?>
		</div>
	<?php endforeach; else: ?>
		<p>Nenhum evento.</p>
	<?php endif; ?>
	<h2>Assinatura</h2>
	<?php if (!empty($signatureDataUri)) : ?>
		<p><img src="<?= h($signatureDataUri) ?>" style="max-width: 220px; height: auto;" alt="Assinatura" /></p>
	<?php else: ?>
		<p class="muted">Sem assinatura digital registada neste ticket.</p>
	<?php endif; ?>
</body>
</html>
