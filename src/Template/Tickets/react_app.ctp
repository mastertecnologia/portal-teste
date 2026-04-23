<?php
/**
 * Shell da UI React dos tickets: injeta boot JSON e assets estáticos do Vite (public/tickets-app).
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
$this->append(
	'script',
	'<script>window.__TICKETS_BOOT__ = ' . $bootJson . ';</script>'
	. '<script type="module" src="' . h($w . 'tickets-app/assets/tickets.js?v=' . $ticketsAssetV) . '"></script>'
);
?>
<?php /* Filho direto de .row precisa ser .col-* no Bootstrap, senão largura/overflow quebram o React */ ?>
<div class="col-md-12 tickets-react-shell p-0">
	<div id="tickets-react-root" class="tickets-react-host tickets-react-sd w-100"></div>
</div>
