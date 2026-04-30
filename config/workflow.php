<?php

$enabledRaw = env('WORKFLOW_ENABLED');
$enabled = $enabledRaw === null ? false : in_array(strtolower((string)$enabledRaw), ['1', 'true', 'on', 'yes'], true);

$empresasRaw = trim((string)env('WORKFLOW_EMPRESAS', ''));
$empresas = [];
if ($empresasRaw !== '') {
	foreach (explode(',', $empresasRaw) as $part) {
		$id = (int)trim($part);
		if ($id > 0) {
			$empresas[] = $id;
		}
	}
	$empresas = array_values(array_unique($empresas));
}

return [
	'Workflow' => [
		'workflowEnabled' => $enabled,
		'enabledEmpresas' => $empresas,
	],
];
