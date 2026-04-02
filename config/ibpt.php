<?php
/**
 * IBPT — Lei 12.741/2012 (transparência fiscal nos documentos ao consumidor).
 *
 * CDN (sem token): tabelas NCM por UF em JSON (nfe.io).
 * API oficial (opcional): defina IBPT_TOKEN e IBPT_CNPJ no ambiente (.env / servidor).
 *
 * @see https://github.com/nfe/ibpt
 */
$ibptEnabled = getenv('IBPT_ENABLED');
if ($ibptEnabled === false || $ibptEnabled === '') {
	$ibptEnabled = true;
} else {
	$ibptEnabled = filter_var($ibptEnabled, FILTER_VALIDATE_BOOLEAN);
}

return [
	'Ibpt' => [
		'enabled' => $ibptEnabled,
		/** Token gerado em https://deolhonoimposto.ibpt.org.br/ (conta + CNPJ) */
		'token' => getenv('IBPT_TOKEN') ?: '',
		/** CNPJ da empresa (somente dígitos no uso; aqui pode vir formatado) */
		'cnpj' => getenv('IBPT_CNPJ') ?: '',
		/** UF padrão se a empresa não tiver cidade/estado carregado */
		'ufDefault' => getenv('IBPT_UF') ?: 'RS',
		/** NCM 8 posições quando o produto não tiver NCM cadastrado */
		'defaultNcm' => getenv('IBPT_DEFAULT_NCM') ?: '00000000',
		'timeout' => (int) (getenv('IBPT_TIMEOUT') ?: 12),
		'cdnBaseUrl' => rtrim(getenv('IBPT_CDN_BASE') ?: 'https://ibpt.nfe.io', '/'),
		'apiProdutosUrls' => array_filter(array_map('trim', explode(',', (string) (getenv('IBPT_API_URLS') ?: 'https://apidadosabertos.ibpt.org.br/api/v1/produtos,https://apidoni.ibpt.org.br/api/v1/produtos')))),
		/** Única alíquota quando CDN/API falharem (ex.: 0,3145 ≈ 31,45%) */
		'fallbackTotalRate' => (float) (getenv('IBPT_FALLBACK_RATE') ?: 0.3145),
	],
];
