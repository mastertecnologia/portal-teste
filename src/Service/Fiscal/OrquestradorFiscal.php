<?php
namespace App\Service\Fiscal;

/**
 * Consolida contribuições de vários módulos fiscais.
 *
 * Integrações típicas (cada uma é projeto à parte — certificado A1/A3, XSD, homologação):
 * - SEFAZ (UF): autorização / consulta NF-e, NFC-e; regras ICMS/ST variam por estado.
 * - Receita Federal: obrigações acessórias (EFD-Contribuições, DCTFWeb); não há um REST único "soma tributos".
 * - Prefeituras: NFS-e (ISS) — padrão nacional em evolução; muitas prefeituras têm WS próprio.
 * - eSocial: trabalhistas (INSS/IRRF sobre folha em contexto de serviço tomado/prestado conforme caso).
 *
 * O portal pode orquestrar: IBPT (transparência), ERP legado (SOAP já usado em produtos), ou APIs de terceiros (Focus, NFe.io, etc.).
 */
class OrquestradorFiscal {

	/** @var ModuloFiscalInterface[] */
	protected $modulos = [];

	public function __construct(array $modulos = null) {
		if ($modulos === null) {
			$this->modulos = [new ModuloIbptEscrita()];
		} else {
			$this->modulos = $modulos;
		}
	}

	public function getModuloIds() {
		$ids = [];
		foreach ($this->modulos as $m) {
			if ($m instanceof ModuloFiscalInterface) {
				$ids[] = $m->getId();
			}
		}

		return $ids;
	}

	public function compor(array $context) {
		$out = [];
		foreach ($this->modulos as $m) {
			if (!($m instanceof ModuloFiscalInterface)) {
				continue;
			}
			$patch = $m->contribuir($context);
			if (is_array($patch)) {
				foreach ($patch as $k => $v) {
					if ($v !== null && $v !== '') {
						$out[$k] = $v;
					}
				}
			}
		}

		return $out;
	}
}
