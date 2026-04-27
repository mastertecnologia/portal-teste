<?php
/**
 * @var \App\View\AppView $this
 * @var string $name Slug sem .css (ex.: produtos-premium, pgm-action-buttons)
 */
$name = $name ?? '';
$allowed = ['produtos-premium', 'clientes-premium', 'clientes-layout-unificado', 'orcamentos-premium', 'pgm-action-buttons', 'pgm-estoque', 'ativos-premium'];
if (!in_array($name, $allowed, true)) {
	return;
}
$path = WWW_ROOT . 'css' . DIRECTORY_SEPARATOR . $name . '.css';
$t = is_readable($path) ? filemtime($path) : time();
$url = $this->Url->build('/pgm-assets/css/' . rawurlencode($name) . '?t=' . $t);
echo '<link rel="stylesheet" href="' . h($url) . '"/>';
