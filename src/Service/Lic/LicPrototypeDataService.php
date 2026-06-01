<?php
declare(strict_types=1);

namespace App\Service\Lic;

use Cake\ORM\TableRegistry;

/**
 * Dados reais do módulo Licenciamento (tabelas lic_*).
 */
class LicPrototypeDataService {

	/** @var int */
	private $idempresa;

	public function __construct(int $idempresa) {
		$this->idempresa = $idempresa;
	}

	public function tablesAvailable(): bool {
		try {
			$conn = TableRegistry::getTableLocator()->get('Users')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();

			return in_array('lic_licencas', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<string,int>
	 */
	public function dashboardKpis(): array {
		$out = [
			'licencas_ativas' => 0,
			'assentos' => 0,
			'venc_30' => 0,
			'dispositivos' => 0,
			'solicitacoes_abertas' => 0,
		];
		if (!$this->tablesAvailable() || $this->idempresa <= 0) {
			return $out;
		}
		$loc = TableRegistry::getTableLocator();
		try {
			if (!$loc->exists('LicLicencas')) {
				return $out;
			}
			$lic = $loc->get('LicLicencas');
			$out['licencas_ativas'] = $lic->find()
				->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
				->count();
			$assentos = 0;
			foreach ($lic->find()
				->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
				->select(['assentos'])
				->all() as $row) {
				$assentos += (int)$row->get('assentos');
			}
			$out['assentos'] = $assentos;
			$limite = (new \DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d');
			$hoje = (new \DateTimeImmutable('today'))->format('Y-m-d');
			$out['venc_30'] = $lic->find()
				->where([
					'idempresa' => $this->idempresa,
					'status' => 'ativa',
					'fim <=' => $limite,
					'fim >=' => $hoje,
				])
				->count();
		} catch (\Throwable $e) {
		}
		try {
			$conn = $loc->get('Users')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
			if (in_array('lic_dispositivos', $tables, true)) {
				$out['dispositivos'] = (int)$conn->execute(
					'SELECT COUNT(*) AS c FROM lic_dispositivos WHERE idempresa = ?',
					[$this->idempresa]
				)->fetch('assoc')['c'];
			}
			if (in_array('lic_solicitacoes', $tables, true)) {
				$out['solicitacoes_abertas'] = (int)$conn->execute(
					'SELECT COUNT(*) AS c FROM lic_solicitacoes WHERE idempresa = ? AND status = ?',
					[$this->idempresa, 'aberta']
				)->fetch('assoc')['c'];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}
}
