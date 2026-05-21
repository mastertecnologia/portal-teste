<?php
/**
 * CSS + manifest do shell erp_prototype via Cake (evita 404 em /portal/dist/...).
 *
 * @var \App\View\AppView $this
 * @var bool $includeServicedeskPrototypeCss
 */
$includeServicedeskPrototypeCss = !empty($includeServicedeskPrototypeCss);
$distSlugs = ['style-min', 'pgm-erp-prototype'];
if ($includeServicedeskPrototypeCss) {
	$distSlugs[] = 'pgm-servicedesk-prototype';
}
$relPaths = [
	'style-min' => 'dist/css/style.min.css',
	'pgm-erp-prototype' => 'dist/css/pgm-erp-prototype.css',
	'pgm-servicedesk-prototype' => 'dist/css/pages/pgm-servicedesk-prototype.css',
];
foreach ($distSlugs as $slug) {
	$rel = $relPaths[$slug];
	$full = WWW_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $rel);
	if (!is_readable($full)) {
		$full = ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
	}
	$t = is_readable($full) ? filemtime($full) : time();
	$url = $this->Url->build('/pgm-assets/dist/' . rawurlencode($slug) . '?t=' . $t);
	echo '<link rel="stylesheet" href="' . h($url) . '"/>' . "\n";
}
$manifestPath = WWW_ROOT . 'manifest-erp.json';
if (!is_readable($manifestPath)) {
	$manifestPath = ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . 'manifest-erp.json';
}
$mt = is_readable($manifestPath) ? filemtime($manifestPath) : time();
echo '<link rel="manifest" href="' . h($this->Url->build('/manifest-erp.json?t=' . $mt)) . '"/>' . "\n";
