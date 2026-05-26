<?php
namespace App\Utility;

/**
 * Tipo de produto para UI de orçamento (catálogo, carrinho, endpoint produto).
 *
 * O portal persiste, na maioria dos ambientes, o valor do formulário de produtos
 * (1 = Produto, 2 = Serviço, 3 = Licença, 4 = Locação), distinto dos índices
 * das constantes TicketConstants (0 = Produto, 1 = Serviço, …).
 */
class ProdutoTipoOrcamentoUtil
{
	/** @var array<int, array{label:string,badge:string}> */
	private const LEGACY_FORM = [
		1 => ['label' => 'Produto', 'badge' => 'prod'],
		2 => ['label' => 'Serviço', 'badge' => 'srv'],
		3 => ['label' => 'Licença', 'badge' => 'lic'],
		4 => ['label' => 'Locação', 'badge' => 'loc'],
	];

	/**
	 * @param mixed $tipo Valor em produtos.tipo
	 * @return array{tipoLabel:string,badge:string}
	 */
	public static function labelAndBadge($tipo): array
	{
		$tipoInt = (int)($tipo ?? 0);
		if (isset(self::LEGACY_FORM[$tipoInt])) {
			$row = self::LEGACY_FORM[$tipoInt];

			return [
				'tipoLabel' => $row['label'],
				'badge' => $row['badge'],
			];
		}

		$tipoMap = (defined('C_ProdutosTipo') && is_array(constant('C_ProdutosTipo')))
			? constant('C_ProdutosTipo')
			: [];
		$tipoLabel = (string)($tipoMap[$tipoInt] ?? 'Item');
		$badge = 'outro';
		if (defined('C_ProdutosTipoProduto') && $tipoInt === (int)constant('C_ProdutosTipoProduto')) {
			$badge = 'prod';
		} elseif (defined('C_ProdutosTipoServico') && $tipoInt === (int)constant('C_ProdutosTipoServico')) {
			$badge = 'srv';
		} elseif (stripos($tipoLabel, 'licen') !== false) {
			$badge = 'lic';
		} elseif (stripos($tipoLabel, 'loca') !== false) {
			$badge = 'loc';
		} elseif (stripos($tipoLabel, 'produt') !== false) {
			$badge = 'prod';
		} elseif (stripos($tipoLabel, 'serv') !== false) {
			$badge = 'srv';
		}

		return [
			'tipoLabel' => $tipoLabel,
			'badge' => $badge,
		];
	}
}
