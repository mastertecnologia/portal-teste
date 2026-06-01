<?php
declare(strict_types=1);

namespace App\Service\Lic;

use Cake\I18n\FrozenDate;
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
		$lic = $this->licTable();
		if ($lic === null) {
			return $out;
		}
		try {
			$out['licencas_ativas'] = $lic->find()
				->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
				->count();
			$assentos = 0;
			foreach ($lic->find()
				->where(['idempresa' => $this->idempresa, 'status IN' => ['ativa', 'rascunho']])
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
			$loc = TableRegistry::getTableLocator();
			if ($loc->exists('LicDispositivos')) {
				$out['dispositivos'] = $loc->get('LicDispositivos')->find()
					->where(['idempresa' => $this->idempresa])
					->count();
			}
			if ($loc->exists('LicSolicitacoes')) {
				$out['solicitacoes_abertas'] = $loc->get('LicSolicitacoes')->find()
					->where(['idempresa' => $this->idempresa, 'status' => 'aberta'])
					->count();
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listLicencas(array $filters = [], int $limit = 100): array {
		$lic = $this->licTable();
		if ($lic === null) {
			return [];
		}
		$q = $lic->find()
			->contain(['Clientes', 'LicCatalogoProdutos'])
			->where(['LicLicencas.idempresa' => $this->idempresa])
			->order(['LicLicencas.created' => 'DESC'])
			->limit($limit);
		$st = trim((string)($filters['status'] ?? ''));
		if ($st !== '') {
			$q->where(['LicLicencas.status' => $st]);
		}
		$cliente = trim((string)($filters['cliente'] ?? ''));
		if ($cliente !== '' && ctype_digit($cliente)) {
			$q->where(['LicLicencas.idcliente' => (int)$cliente]);
		}
		$out = [];
		foreach ($q->all() as $row) {
			$cli = $row->get('cliente');
			$cat = $row->get('lic_catalogo_produto');
			$nomeCli = '';
			if ($cli !== null) {
				$nomeCli = (int)$cli->get('tipo') === 2
					? (string)($cli->get('razaosocial') ?? $cli->get('nome'))
					: (string)$cli->get('nome');
			}
			$produto = (string)($row->get('produto_label') ?? '');
			if ($produto === '' && $cat !== null) {
				$produto = (string)$cat->get('nome');
			}
			$out[] = [
				'id' => (int)$row->get('id'),
				'codigo' => (string)$row->get('codigo'),
				'cliente' => $nomeCli,
				'idcliente' => (int)$row->get('idcliente'),
				'produto' => $produto,
				'assentos' => (int)$row->get('assentos'),
				'modelo' => (string)$row->get('modelo'),
				'status' => (string)$row->get('status'),
				'inicio' => $row->get('inicio'),
				'fim' => $row->get('fim'),
				'valor_anual' => $row->get('valor_anual'),
			];
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getLicenca(int $id): ?array {
		$lic = $this->licTable();
		if ($lic === null || $id <= 0) {
			return null;
		}
		try {
			$row = $lic->find()
				->contain(['Clientes', 'LicCatalogoProdutos', 'LicAssentos'])
				->where(['LicLicencas.id' => $id, 'LicLicencas.idempresa' => $this->idempresa])
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($row === null) {
			return null;
		}
		$cli = $row->get('cliente');
		$cat = $row->get('lic_catalogo_produto');
		$nomeCli = '';
		if ($cli !== null) {
			$nomeCli = (int)$cli->get('tipo') === 2
				? (string)($cli->get('razaosocial') ?? $cli->get('nome'))
				: (string)$cli->get('nome');
		}
		$produto = (string)($row->get('produto_label') ?? '');
		if ($produto === '' && $cat !== null) {
			$produto = (string)$cat->get('nome');
		}
		$assentos = [];
		foreach ($row->get('lic_assentos') ?? [] as $a) {
			$assentos[] = [
				'id' => (int)$a->get('id'),
				'email' => (string)$a->get('email'),
				'status' => (string)$a->get('status'),
			];
		}

		return [
			'id' => (int)$row->get('id'),
			'codigo' => (string)$row->get('codigo'),
			'idcliente' => (int)$row->get('idcliente'),
			'cliente' => $nomeCli,
			'idcatalogo' => (int)$row->get('idcatalogo'),
			'produto' => $produto,
			'produto_label' => (string)($row->get('produto_label') ?? ''),
			'assentos' => (int)$row->get('assentos'),
			'modelo' => (string)$row->get('modelo'),
			'status' => (string)$row->get('status'),
			'inicio' => $row->get('inicio'),
			'fim' => $row->get('fim'),
			'valor_anual' => $row->get('valor_anual'),
			'assentos_rows' => $assentos,
			'entity' => $row,
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCatalogo(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicCatalogoProdutos')) {
			return [];
		}
		$out = [];
		try {
			foreach ($loc->get('LicCatalogoProdutos')->find()
				->where(['idempresa' => $this->idempresa, 'ativo' => true])
				->order(['nome' => 'ASC'])
				->limit($limit)
				->all() as $p) {
				$out[] = [
					'id' => (int)$p->get('id'),
					'nome' => (string)$p->get('nome'),
					'sku' => (string)($p->get('sku') ?? ''),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,string>
	 */
	public function listClientesOptions(int $limit = 200): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('Clientes')->find()
				->where(['Clientes.idempresa' => $this->idempresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit($limit)
				->all();
			foreach ($rows as $c) {
				$nome = (int)$c->get('tipo') === 2
					? (string)($c->get('razaosocial') ?? $c->get('nome'))
					: (string)$c->get('nome');
				$out[(int)$c->get('id')] = $nome;
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function saveWizardStep(int $step, array $data, ?int $licId = null): array {
		$lic = $this->licTable();
		if ($lic === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas do módulo não disponíveis. Execute a migration.')]];
		}
		if ($step === 1) {
			$idcliente = (int)($data['idcliente'] ?? 0);
			if ($idcliente <= 0) {
				return ['ok' => false, 'errors' => ['idcliente' => __('Cliente obrigatório.')]];
			}
			$entity = $lic->newEntity([
				'idempresa' => $this->idempresa,
				'idcliente' => $idcliente,
				'idcatalogo' => (int)($data['idcatalogo'] ?? 0) ?: null,
				'produto_label' => trim((string)($data['produto_label'] ?? '')) ?: null,
				'codigo' => $this->nextCodigo(),
				'modelo' => trim((string)($data['modelo'] ?? 'assinatura')) ?: 'assinatura',
				'assentos' => max(1, (int)($data['assentos'] ?? 1)),
				'status' => 'rascunho',
				'created' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
			$saved = $lic->save($entity);
			if ($saved === false) {
				return ['ok' => false, 'errors' => $entity->getErrors()];
			}
			$this->audit('licenca.criar_rascunho', 'lic_licencas', (int)$entity->get('id'), (int)($data['iduser'] ?? 0));

			return ['ok' => true, 'id' => (int)$entity->get('id')];
		}
		if ($licId === null || $licId <= 0) {
			return ['ok' => false, 'errors' => ['_base' => __('Licença inválida.')]];
		}
		$row = $lic->find()
			->where(['id' => $licId, 'idempresa' => $this->idempresa])
			->first();
		if ($row === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Licença não encontrada.')]];
		}
		if ($step === 2) {
			$row = $lic->patchEntity($row, [
				'assentos' => max(1, (int)($data['assentos'] ?? $row->get('assentos'))),
				'modelo' => trim((string)($data['modelo'] ?? $row->get('modelo'))),
				'inicio' => $this->parseDate($data['inicio'] ?? null),
				'fim' => $this->parseDate($data['fim'] ?? null),
				'valor_anual' => $this->parseMoney($data['valor_anual'] ?? null),
				'modified' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
		} elseif ($step === 3) {
			$this->syncAssentosEmails($licId, (string)($data['emails'] ?? ''));
		} elseif ($step === 4) {
			$row = $lic->patchEntity($row, [
				'status' => trim((string)($data['status_final'] ?? 'ativa')) ?: 'ativa',
				'modified' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
		}
		$saved = $lic->save($row);
		if ($saved === false) {
			return ['ok' => false, 'errors' => $row->getErrors()];
		}
		$this->audit('licenca.wizard_passo_' . $step, 'lic_licencas', $licId, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $licId];
	}

	protected function syncAssentosEmails(int $licId, string $emailsRaw): void {
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicAssentos')) {
			return;
		}
		$tbl = $loc->get('LicAssentos');
		$tbl->deleteAll(['idlicenca' => $licId]);
		$lines = preg_split('/[\r\n,;]+/', $emailsRaw) ?: [];
		foreach ($lines as $line) {
			$email = trim($line);
			if ($email === '' || strpos($email, '@') === false) {
				continue;
			}
			$entity = $tbl->newEntity([
				'idlicenca' => $licId,
				'email' => $email,
				'status' => 'ativo',
				'created' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
			$tbl->save($entity);
		}
	}

	protected function nextCodigo(): string {
		$year = date('Y');
		$prefix = 'LIC-' . $year . '-';
		$n = 1;
		$lic = $this->licTable();
		if ($lic !== null) {
			try {
				$last = $lic->find()
					->select(['codigo'])
					->where([
						'idempresa' => $this->idempresa,
						'codigo LIKE' => $prefix . '%',
					])
					->order(['id' => 'DESC'])
					->first();
				if ($last !== null) {
					$tail = substr((string)$last->get('codigo'), strlen($prefix));
					if (ctype_digit($tail)) {
						$n = (int)$tail + 1;
					}
				}
			} catch (\Throwable $e) {
			}
		}

		return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * @param mixed $v
	 * @return \Cake\I18n\FrozenDate|null
	 */
	protected function parseDate($v) {
		$s = trim((string)$v);
		if ($s === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
			return null;
		}

		return new FrozenDate($s);
	}

	/**
	 * @param mixed $v
	 * @return string|null
	 */
	protected function parseMoney($v) {
		if ($v === null || $v === '') {
			return null;
		}
		$s = str_replace(['.', ' '], '', (string)$v);
		$s = str_replace(',', '.', $s);
		if (!is_numeric($s)) {
			return null;
		}

		return number_format((float)$s, 2, '.', '');
	}

	protected function audit(string $acao, string $entidade, int $entidadeId, int $userId = 0): void {
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicAuditoriaEventos')) {
			return;
		}
		try {
			$tbl = $loc->get('LicAuditoriaEventos');
			$entity = $tbl->newEntity([
				'idempresa' => $this->idempresa,
				'iduser' => $userId > 0 ? $userId : null,
				'acao' => $acao,
				'entidade' => $entidade,
				'entidade_id' => $entidadeId,
				'created' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
			$tbl->save($entity);
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @return \App\Model\Table\LicLicencasTable|null
	 */
	protected function licTable() {
		if (!$this->tablesAvailable()) {
			return null;
		}
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicLicencas')) {
			return null;
		}

		return $loc->get('LicLicencas');
	}
}
