<?php
/**
 * Shell da UI React dos tickets: injeta boot JSON e assets estáticos do Vite (webroot/tickets-app; espelho em public/ após npm run build).
 *
 * @var list<array{title:string,url?:array|string,options?:array<string,mixed>}>|null $reactAppBreadcrumbs
 */
if (!empty($reactAppBreadcrumbs) && is_array($reactAppBreadcrumbs)) {
	foreach ($reactAppBreadcrumbs as $bcRow) {
		$bcTitle = $bcRow['title'] ?? '';
		if ($bcTitle === '') {
			continue;
		}
		$this->Breadcrumbs->add($bcTitle, $bcRow['url'] ?? [], $bcRow['options'] ?? []);
	}
}
$this->assign('title', $title ?? 'Tickets');
$w = $this->request->getAttribute('webroot');
$bootJson = json_encode(
	$reactBoot ?? [],
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
$ticketsCssFs = defined('WWW_ROOT') ? WWW_ROOT . 'tickets-app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tickets.css' : '';
$ticketsJsFs = defined('WWW_ROOT') ? WWW_ROOT . 'tickets-app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tickets.js' : '';
$ticketsAssetV = '1';
if ($ticketsCssFs !== '' && $ticketsJsFs !== '' && is_file($ticketsCssFs) && is_file($ticketsJsFs)) {
	$ticketsAssetV = (string)max(filemtime($ticketsCssFs), filemtime($ticketsJsFs));
}
$this->append(
	'css',
	'<link rel="stylesheet" href="' . h($w . 'tickets-app/assets/tickets.css?v=' . $ticketsAssetV) . '">'
);
if (!empty($reactAppExtraCss) && is_array($reactAppExtraCss)) {
	foreach ($reactAppExtraCss as $extraCssHref) {
		$this->append('css', '<link rel="stylesheet" href="' . h((string)$extraCssHref) . '">');
	}
}
/*
 * Boot e tickets.js precisam ficar no corpo da resposta dentro do que o Turbo troca em <turbo-frame id="pgm-main-frame">.
 * Scripts apenas em fetch('script') no <head> do layout default não rodam em navegação por frame — painel ficava em branco.
 */
$ticketsJsSrc = $w . 'tickets-app/assets/tickets.js?v=' . $ticketsAssetV;
?>
<?php /* Filho direto de .row precisa ser .col-* no Bootstrap, senão largura/overflow quebram o React */ ?>
<div class="col-md-12 tickets-react-shell p-0">
	<link rel="stylesheet" href="<?= h($w . 'tickets-app/assets/tickets.css?v=' . $ticketsAssetV) ?>">
	<?php if (!empty($reactAppExtraCss) && is_array($reactAppExtraCss)) : ?>
		<?php foreach ($reactAppExtraCss as $extraCssHref) : ?>
	<link rel="stylesheet" href="<?= h((string)$extraCssHref) ?>">
		<?php endforeach; ?>
	<?php endif; ?>
	<div id="tickets-react-root" class="tickets-react-host tickets-react-sd w-100"></div>
</div>
<script>
window.__TICKETS_BOOT__ = <?= $bootJson ?>;
(function () {
	var ticketsJsSrc = <?= json_encode($ticketsJsSrc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
	function pgmTicketsReactMountWhenReady(attemptsLeft) {
		if (typeof window.__pgmTicketsReactMount !== 'function') {
			return;
		}
		var el = document.getElementById('tickets-react-root');
		if (el) {
			window.__pgmTicketsReactMount();
			return;
		}
		if (attemptsLeft > 0) {
			requestAnimationFrame(function () {
				pgmTicketsReactMountWhenReady(attemptsLeft - 1);
			});
		}
	}
	function pgmTicketsReactBoot() {
		if (window.__pgmTicketsReactMount) {
			pgmTicketsReactMountWhenReady(12);
			return;
		}
		var s = document.createElement('script');
		s.type = 'module';
		s.src = ticketsJsSrc;
		document.head.appendChild(s);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', pgmTicketsReactBoot, { once: true });
	} else {
		pgmTicketsReactBoot();
	}
})();
</script>
