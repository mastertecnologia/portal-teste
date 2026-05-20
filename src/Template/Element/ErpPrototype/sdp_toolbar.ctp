<?php
/**
 * Barra compacta no protótipo SD: idioma + tema (paridade com topbar ERP).
 *
 * @var \App\View\AppView $this
 */
$session = $this->getRequest()->getSession();
$erpLoc = (string)$session->read('Erp.locale');
if (!in_array($erpLoc, ['pt_BR', 'en_US', 'es'], true)) {
	$erpLoc = 'pt_BR';
}
$locRedirect = \App\Utility\PortalUrlPath::sanitizeInternalRedirect($this->getRequest()->getRequestTarget()) ?? '';
$locUrl = static function (string $code) use ($locRedirect) {
	$params = ['controller' => 'PrototypeHistory', 'action' => 'setLocale', $code];
	if ($locRedirect !== '') {
		$params['?'] = ['redirect' => $locRedirect];
	}

	return $params;
};
?>
<div class="sdp-toolbar" style="display:flex;align-items:center;justify-content:flex-end;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
	<div style="display:flex;align-items:center;gap:2px;font-size:11px;">
		<a href="<?= h($this->Url->build($locUrl('pt_BR'))) ?>" class="btn btn-ghost btn-xs" style="padding:3px 7px;<?= $erpLoc === 'pt_BR' ? 'font-weight:700;color:var(--teal,#1D9E75);' : '' ?>">PT</a>
		<a href="<?= h($this->Url->build($locUrl('en'))) ?>" class="btn btn-ghost btn-xs" style="padding:3px 7px;<?= $erpLoc === 'en_US' ? 'font-weight:700;color:var(--teal,#1D9E75);' : '' ?>">EN</a>
		<a href="<?= h($this->Url->build($locUrl('es'))) ?>" class="btn btn-ghost btn-xs" style="padding:3px 7px;<?= $erpLoc === 'es' ? 'font-weight:700;color:var(--teal,#1D9E75);' : '' ?>">ES</a>
	</div>
	<button type="button" id="pgm-theme-toggle" class="btn btn-ghost btn-xs" onclick="pgmToggleErpTheme()" title="<?= h(__('Alternar tema')) ?>" style="padding:3px 8px;font-size:15px;line-height:1;">🌙</button>
	<span style="font-size:10px;color:var(--text-muted,#6b6a65);">⌨️ <code>?</code> <?= h(__('atalhos')) ?></span>
</div>
