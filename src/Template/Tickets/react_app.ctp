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
/**
 * Inclui hash do conteúdo de `tickets.js` (não só mtime) para o `?v=` mudar após `npm run build` mesmo
 * se o Git/FS preservar datas. A navegação Turbo reutiliza o módulo em memória — o script abaixo força
 * `location.reload()` quando esta versão diverge do valor já carregado.
 */
$ticketsAssetV = '1';
if ($ticketsJsFs !== '' && is_file($ticketsJsFs)) {
	$md5f = @md5_file($ticketsJsFs);
	$jsHash = (is_string($md5f) && strlen($md5f) === 32) ? substr($md5f, 0, 8) : '0';
	$mt = filemtime($ticketsJsFs);
	if ($ticketsCssFs !== '' && is_file($ticketsCssFs)) {
		$mt = max($mt, filemtime($ticketsCssFs));
	}
	$ticketsAssetV = (string)$mt . '-' . $jsHash;
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
<?php /* Se a resposta cair num turbo-frame sem o id esperado, força visita completa em vez de "Content missing". */ ?>
<meta name="turbo-visit-control" content="reload">
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
(function () {
	var ticketsAppAssetV = <?= json_encode($ticketsAssetV, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
	if (window.__TICKETS_APP_ASSET_V__ && window.__TICKETS_APP_ASSET_V__ !== ticketsAppAssetV) {
		window.__TICKETS_APP_ASSET_V__ = ticketsAppAssetV;
		window.location.reload();
		return;
	}
	window.__TICKETS_APP_ASSET_V__ = ticketsAppAssetV;
	window.__TICKETS_BOOT__ = <?= $bootJson ?>;
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
