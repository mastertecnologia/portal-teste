<?php
/**
 * Etiqueta QR — layout simples para impressão.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asset $asset
 * @var string $qrPayload
 * @var string $qrImageUrl
 */
$idTag = $asset->identificador ?: ('ATV-' . str_pad((string)$asset->id, 6, '0', STR_PAD_LEFT));
?><!doctype html>
<html lang="pt-BR">
<head>
	<meta charset="utf-8"/>
	<title>Etiqueta — <?= h($asset->descricao ?: $idTag) ?></title>
	<style>
		* { box-sizing: border-box; }
		body { margin: 0; padding: 24px; font-family: 'Segoe UI', system-ui, sans-serif; color: #111; background: #fff; }
		.tag {
			width: 320px; border: 2px solid #1d9e75; border-radius: 12px;
			padding: 16px; text-align: center; margin: 0 auto;
		}
		.tag h1 { margin: 0 0 6px; font-size: 16px; }
		.tag .id { font-family: 'Courier New', monospace; background: #f5f5f5; border-radius: 6px; padding: 4px 8px; display: inline-block; font-size: 12px; }
		.tag img { display: block; margin: 12px auto; }
		.tag .meta { font-size: 12px; color: #444; margin-top: 8px; }
		.actions { text-align: center; margin: 18px 0; }
		.actions button { padding: 8px 18px; background: #1d9e75; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
		@media print {
			.actions { display: none; }
			body { padding: 0; }
		}
	</style>
</head>
<body>
	<div class="actions">
		<button onclick="window.print()">Imprimir etiqueta</button>
	</div>
	<div class="tag">
		<h1><?= h($asset->descricao ?: 'Ativo de TI') ?></h1>
		<div class="id"><?= h($idTag) ?></div>
		<img src="<?= h($qrImageUrl) ?>" alt="QR <?= h($qrPayload) ?>" width="240" height="240"/>
		<div class="meta">
			<?php if (!empty($asset->marca) || !empty($asset->modelo)) : ?>
				<div><?= h(trim((string)$asset->marca . ' ' . (string)$asset->modelo)) ?></div>
			<?php endif; ?>
			<?php if (!empty($asset->numero_serie)) : ?>
				<div>Série: <?= h($asset->numero_serie) ?></div>
			<?php endif; ?>
			<?php if (!empty($asset->cliente)) :
				$cli = $asset->cliente;
				$cliNome = $cli->razaosocial ?: ($cli->nomefantasia ?: ($cli->nome ?: ''));
				if ($cliNome) : ?>
					<div><?= h($cliNome) ?></div>
				<?php endif;
			endif; ?>
		</div>
	</div>
</body>
</html>
