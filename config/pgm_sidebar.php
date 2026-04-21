<?php
/**
 * Sidebar (staff / portal): migração incremental para React.
 *
 * Ativar só em ambiente de teste: no .env use PGM_SIDEBAR_REACT=1
 * (ver .env.example). O layout `default.ctp` mantém o elemento Cake
 * enquanto a flag estiver desligada (predefinição segura).
 */
return [
	'PgmSidebar' => [
		'react_enabled' => filter_var(env('PGM_SIDEBAR_REACT', '0'), FILTER_VALIDATE_BOOLEAN),
	],
];
