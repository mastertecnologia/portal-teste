<?php
/**
 * Pacotes legado em vendor/PGMPackages (não vêm do Composer).
 * Se a pasta não existir no deploy, não gera fatal — copie vendor/PGMPackages/ a partir de um ambiente completo.
 */
(function () {
	if (!defined('ROOT')) {
		return;
	}
	$dir = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS;
	foreach (['Utilities.php', 'UserConstants.php', 'TicketConstants.php'] as $file) {
		$path = $dir . $file;
		if (is_file($path)) {
			require_once $path;
		}
	}
})();
