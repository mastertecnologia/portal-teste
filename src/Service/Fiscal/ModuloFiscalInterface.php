<?php
namespace App\Service\Fiscal;

/**
 * Contrato para módulos que contribuem com tributos da escrita fiscal.
 * Integrações reais (SEFAZ NF-e, SPED, eSocial, etc.) implementam esta interface e são registradas no orquestrador.
 */
interface ModuloFiscalInterface {

	/**
	 * Identificador estável (ex.: ibpt_transparencia, sefaz_nfe, erp_db9).
	 */
	public function getId();

	/**
	 * @param array $context Deve incluir: fatura, empresa, carrinho, idempresa; opcional ibpt_breakdown.
	 * @return array Mapa campo_tabela => valor escalar (apenas chaves conhecidas em faturas_escrita_fiscal).
	 */
	public function contribuir(array $context);
}
