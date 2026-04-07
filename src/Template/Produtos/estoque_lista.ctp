<?php
/**
 * Resposta AJAX — reutiliza o mesmo markup do element (evita duplicar).
 *
 * @var array $produtos
 * @var bool $bApenasComSaldo
 * @var array<string,int>|null $mapCodigoId
 */
echo $this->element('Produtos/estoque_lista', [
	'produtos' => $produtos ?? [],
	'bApenasComSaldo' => $bApenasComSaldo ?? true,
	'mapCodigoId' => $mapCodigoId ?? [],
]);
