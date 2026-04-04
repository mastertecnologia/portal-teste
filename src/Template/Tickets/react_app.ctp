<?php
/**
 * Shell da UI React dos tickets: injeta boot JSON e assets estáticos do Vite (public/tickets-app).
 */
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
$this->append(
	'script',
	'<script>window.__TICKETS_BOOT__ = ' . $bootJson . ';</script>'
	. '<script type="module" src="' . h($w . 'tickets-app/assets/tickets.js?v=' . $ticketsAssetV) . '"></script>'
);
?>
<?php /* Dentro de .pgm-page-body (layout default): flex column + min-width:0 evita overflow horizontal */ ?>
<div class="tickets-react-shell w-100 p-0">
	<div id="tickets-react-root" class="tickets-react-host pgm-react-embed w-100"></div>
</div>
