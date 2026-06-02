<?php
declare(strict_types=1);

namespace App\Service\Lic;

use Cake\I18n\FrozenDate;
use App\Utility\LicCofreCipher;
use Cake\ORM\TableRegistry;

/**
 * Dados reais do módulo Licenciamento (tabelas lic_*).
 */
class LicPrototypeDataService {
	/**
	 * Carrega Table ORM (TableLocator::exists dá falso negativo antes do primeiro get).
	 */
	protected function table(string $alias): ?\Cake\ORM\Table {
		try {
			return TableRegistry::getTableLocator()->get($alias);
		} catch (\Throwable $e) {
			return null;
		}
	}


	/** @var int */
	private $idempresa;

	/** @var int|null */
	private $idclienteScope;

	public function __construct(int $idempresa, ?int $idclienteScope = null) {
		$this->idempresa = $idempresa;
		$this->idclienteScope = $idclienteScope !== null && $idclienteScope > 0 ? $idclienteScope : null;
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
			$disp = $this->table('LicDispositivos');
			if ($disp !== null) {
				$out['dispositivos'] = $disp->find()
					->where(['idempresa' => $this->idempresa])
					->count();
			}
			$sol = $this->table('LicSolicitacoes');
			if ($sol !== null) {
				$out['solicitacoes_abertas'] = $sol->find()
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
		if ($this->idclienteScope !== null) {
			$q->where(['LicLicencas.idcliente' => $this->idclienteScope]);
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
		$whereLic = ['LicLicencas.id' => $id, 'LicLicencas.idempresa' => $this->idempresa];
		if ($this->idclienteScope !== null) {
			$whereLic['LicLicencas.idcliente'] = $this->idclienteScope;
		}
		try {
			$row = $lic->find()
				->contain(['Clientes', 'LicCatalogoProdutos', 'LicAssentos'])
				->where($whereLic)
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
		if ($this->table('LicCatalogoProdutos') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicCatalogoProdutos')->find()
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
		if ($this->table('LicAssentos') === null) {
			return;
		}
		$tbl = $this->table('LicAssentos');
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

	protected function audit(string $acao, string $entidade, int $entidadeId, int $userId = 0, ?string $detalhe = null, ?string $ip = null): void {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicAuditoriaEventos') === null) {
			return;
		}
		try {
			$tbl = $this->table('LicAuditoriaEventos');
			$entity = $tbl->newEntity([
				'idempresa' => $this->idempresa,
				'iduser' => $userId > 0 ? $userId : null,
				'acao' => $acao,
				'entidade' => $entidade,
				'entidade_id' => $entidadeId,
				'detalhe' => $detalhe,
				'ip' => $ip,
				'created' => date('Y-m-d H:i:s'),
			], ['validate' => false]);
			$tbl->save($entity);
		} catch (\Throwable $e) {
		}
	}


	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCategorias(): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCategorias') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicCategorias')->find()
				->where(['idempresa' => $this->idempresa])
				->order(['nome' => 'ASC'])
				->all() as $c) {
				$out[] = [
					'id' => (int)$c->get('id'),
					'codigo' => (string)$c->get('codigo'),
					'nome' => (string)$c->get('nome'),
					'ativo' => (bool)$c->get('ativo'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCatalogoProdutos(): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCatalogoProdutos') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicCatalogoProdutos')->find()
				->contain(['LicCategorias'])
				->where(['LicCatalogoProdutos.idempresa' => $this->idempresa])
				->order(['LicCatalogoProdutos.nome' => 'ASC'])
				->limit(200)
				->all() as $p) {
				$cat = $p->get('lic_categoria');
				$out[] = [
					'id' => (int)$p->get('id'),
					'sku' => (string)($p->get('sku') ?? ''),
					'nome' => (string)$p->get('nome'),
					'ativo' => (bool)$p->get('ativo'),
					'categoria' => $cat ? (string)$cat->get('nome') : '',
					'idcategoria' => (int)$p->get('idcategoria'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getCatalogoProduto(int $id): ?array {
		if ($id <= 0 || $this->table('LicCatalogoProdutos') === null) {
			return null;
		}
		try {
			$p = $this->table('LicCatalogoProdutos')->find()
				->contain(['LicCategorias'])
				->where(['LicCatalogoProdutos.id' => $id, 'LicCatalogoProdutos.idempresa' => $this->idempresa])
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($p === null) {
			return null;
		}
		$cat = $p->get('lic_categoria');

		return [
			'id' => (int)$p->get('id'),
			'sku' => (string)($p->get('sku') ?? ''),
			'nome' => (string)$p->get('nome'),
			'ativo' => (bool)$p->get('ativo'),
			'idcategoria' => (int)$p->get('idcategoria'),
			'categoria' => $cat ? (string)$cat->get('nome') : '',
			'idfornecedor_cliente' => (int)$p->get('idfornecedor_cliente'),
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function saveCatalogoProduto(array $data, ?int $id = null): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCatalogoProdutos') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$tbl = $this->table('LicCatalogoProdutos');
		$payload = [
			'idempresa' => $this->idempresa,
			'idcategoria' => (int)($data['idcategoria'] ?? 0) ?: null,
			'sku' => trim((string)($data['sku'] ?? '')) ?: null,
			'nome' => trim((string)($data['nome'] ?? '')),
			'idfornecedor_cliente' => (int)($data['idfornecedor_cliente'] ?? 0) ?: null,
			'ativo' => !empty($data['ativo']),
			'modified' => date('Y-m-d H:i:s'),
		];
		if ($payload['nome'] === '') {
			return ['ok' => false, 'errors' => ['nome' => __('Nome obrigatório.')]];
		}
		if ($id !== null && $id > 0) {
			$entity = $tbl->find()->where(['id' => $id, 'idempresa' => $this->idempresa])->first();
			if ($entity === null) {
				return ['ok' => false, 'errors' => ['_base' => __('Produto não encontrado.')]];
			}
			$entity = $tbl->patchEntity($entity, $payload, ['validate' => false]);
		} else {
			$payload['created'] = date('Y-m-d H:i:s');
			$entity = $tbl->newEntity($payload, ['validate' => false]);
		}
		if (!$tbl->save($entity)) {
			return ['ok' => false, 'errors' => $entity->getErrors()];
		}
		$pid = (int)$entity->get('id');
		$this->audit('catalogo.salvar', 'lic_catalogo_produtos', $pid, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $pid];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function saveCategoria(array $data, ?int $id = null): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCategorias') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$tbl = $this->table('LicCategorias');
		$payload = [
			'idempresa' => $this->idempresa,
			'codigo' => trim((string)($data['codigo'] ?? '')),
			'nome' => trim((string)($data['nome'] ?? '')),
			'ativo' => !isset($data['ativo']) || !empty($data['ativo']),
			'modified' => date('Y-m-d H:i:s'),
		];
		if ($payload['codigo'] === '' || $payload['nome'] === '') {
			return ['ok' => false, 'errors' => ['_base' => __('Código e nome são obrigatórios.')]];
		}
		if ($id !== null && $id > 0) {
			$entity = $tbl->find()->where(['id' => $id, 'idempresa' => $this->idempresa])->first();
			if ($entity === null) {
				return ['ok' => false, 'errors' => ['_base' => __('Categoria não encontrada.')]];
			}
			$entity = $tbl->patchEntity($entity, $payload, ['validate' => false]);
		} else {
			$payload['created'] = date('Y-m-d H:i:s');
			$entity = $tbl->newEntity($payload, ['validate' => false]);
		}
		if (!$tbl->save($entity)) {
			return ['ok' => false, 'errors' => $entity->getErrors()];
		}
		$cid = (int)$entity->get('id');
		$this->audit('categoria.salvar', 'lic_categorias', $cid, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $cid];
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function listRenovacoes(int $dias = 90): array {
		$lic = $this->licTable();
		$out = ['vencido' => [], 'd30' => [], 'd60' => [], 'd90' => []];
		if ($lic === null) {
			return $out;
		}
		$hoje = new \DateTimeImmutable('today');
		$lim90 = $hoje->modify('+' . $dias . ' days');
		try {
			$rows = $lic->find()
				->contain(['Clientes'])
				->where([
					'LicLicencas.idempresa' => $this->idempresa,
					'LicLicencas.status IN' => ['ativa', 'rascunho'],
					'LicLicencas.fim IS NOT' => null,
					'LicLicencas.fim <=' => $lim90->format('Y-m-d'),
				])
				->order(['LicLicencas.fim' => 'ASC'])
				->limit(150)
				->all();
		} catch (\Throwable $e) {
			return $out;
		}
		foreach ($rows as $row) {
			$fim = $row->get('fim');
			if ($fim === null) {
				continue;
			}
			if (is_object($fim) && method_exists($fim, 'format')) {
				$fimDt = new \DateTimeImmutable($fim->format('Y-m-d'));
			} else {
				$fimDt = new \DateTimeImmutable((string)$fim);
			}
			$cli = $row->get('cliente');
			$nomeCli = $cli ? ((int)$cli->get('tipo') === 2 ? (string)($cli->get('razaosocial') ?? $cli->get('nome')) : (string)$cli->get('nome')) : '';
			$item = [
				'id' => (int)$row->get('id'),
				'codigo' => (string)$row->get('codigo'),
				'cliente' => $nomeCli,
				'fim' => $fimDt->format('Y-m-d'),
				'valor_anual' => $row->get('valor_anual'),
				'dias' => (int)$hoje->diff($fimDt)->format('%r%a'),
			];
			$d30 = $hoje->modify('+30 days');
			$d60 = $hoje->modify('+60 days');
			if ($fimDt < $hoje) {
				$out['vencido'][] = $item;
			} elseif ($fimDt <= $d30) {
				$out['d30'][] = $item;
			} elseif ($fimDt <= $d60) {
				$out['d60'][] = $item;
			} else {
				$out['d90'][] = $item;
			}
		}

		return $out;
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function listCalendarioPorMes(int $meses = 3): array {
		$lic = $this->licTable();
		$out = [];
		if ($lic === null) {
			return $out;
		}
		$inicio = new \DateTimeImmutable('first day of this month');
		$fim = $inicio->modify('+' . $meses . ' months')->modify('last day of this month');
		try {
			$rows = $lic->find()
				->contain(['Clientes'])
				->where([
					'LicLicencas.idempresa' => $this->idempresa,
					'LicLicencas.fim >=' => $inicio->format('Y-m-d'),
					'LicLicencas.fim <=' => $fim->format('Y-m-d'),
				])
				->order(['LicLicencas.fim' => 'ASC'])
				->all();
		} catch (\Throwable $e) {
			return $out;
		}
		foreach ($rows as $row) {
			$f = $row->get('fim');
			if ($f === null) {
				continue;
			}
			$key = is_object($f) && method_exists($f, 'format') ? $f->format('Y-m') : substr((string)$f, 0, 7);
			$cli = $row->get('cliente');
			$nomeCli = $cli ? ((int)$cli->get('tipo') === 2 ? (string)($cli->get('razaosocial') ?? $cli->get('nome')) : (string)$cli->get('nome')) : '';
			$out[$key][] = [
				'id' => (int)$row->get('id'),
				'codigo' => (string)$row->get('codigo'),
				'cliente' => $nomeCli,
				'fim' => is_object($f) && method_exists($f, 'format') ? $f->format('Y-m-d') : (string)$f,
			];
		}
		ksort($out);

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listDispositivos(array $filters = []): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicDispositivos') === null) {
			return [];
		}
		$q = $this->table('LicDispositivos')->find()
			->contain(['Clientes'])
			->where(['LicDispositivos.idempresa' => $this->idempresa])
			->order(['LicDispositivos.modified' => 'DESC'])
			->limit(200);
		$cliente = (int)($filters['cliente'] ?? 0);
		if ($cliente > 0) {
			$q->where(['LicDispositivos.idcliente' => $cliente]);
		}
		$out = [];
		foreach ($q->all() as $d) {
			$cli = $d->get('cliente');
			$nomeCli = $cli ? ((int)$cli->get('tipo') === 2 ? (string)($cli->get('razaosocial') ?? $cli->get('nome')) : (string)$cli->get('nome')) : '';
			$uv = $d->get('ultimo_visto');
			$out[] = [
				'id' => (int)$d->get('id'),
				'cliente' => $nomeCli,
				'idcliente' => (int)$d->get('idcliente'),
				'hostname' => (string)($d->get('hostname') ?? ''),
				'serial' => (string)($d->get('serial') ?? ''),
				'so' => (string)($d->get('so') ?? ''),
				'ultimo_visto' => $uv && is_object($uv) && method_exists($uv, 'format') ? $uv->format('Y-m-d H:i') : (string)$uv,
			];
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getDispositivo(int $id): ?array {
		if ($id <= 0 || $this->table('LicDispositivos') === null) {
			return null;
		}
		try {
			$d = $this->table('LicDispositivos')->find()
				->contain(['Clientes'])
				->where(['LicDispositivos.id' => $id, 'LicDispositivos.idempresa' => $this->idempresa])
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($d === null) {
			return null;
		}
		$cli = $d->get('cliente');
		$nomeCli = $cli ? ((int)$cli->get('tipo') === 2 ? (string)($cli->get('razaosocial') ?? $cli->get('nome')) : (string)$cli->get('nome')) : '';

		return [
			'id' => (int)$d->get('id'),
			'idcliente' => (int)$d->get('idcliente'),
			'cliente' => $nomeCli,
			'hostname' => (string)($d->get('hostname') ?? ''),
			'serial' => (string)($d->get('serial') ?? ''),
			'so' => (string)($d->get('so') ?? ''),
			'ultimo_visto' => $d->get('ultimo_visto'),
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function saveDispositivo(array $data, ?int $id = null): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicDispositivos') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$idcliente = (int)($data['idcliente'] ?? 0);
		if ($idcliente <= 0) {
			return ['ok' => false, 'errors' => ['idcliente' => __('Cliente obrigatório.')]];
		}
		$tbl = $this->table('LicDispositivos');
		$payload = [
			'idempresa' => $this->idempresa,
			'idcliente' => $idcliente,
			'hostname' => trim((string)($data['hostname'] ?? '')) ?: null,
			'serial' => trim((string)($data['serial'] ?? '')) ?: null,
			'so' => trim((string)($data['so'] ?? '')) ?: null,
			'ultimo_visto' => date('Y-m-d H:i:s'),
			'modified' => date('Y-m-d H:i:s'),
		];
		if ($id !== null && $id > 0) {
			$entity = $tbl->find()->where(['id' => $id, 'idempresa' => $this->idempresa])->first();
			if ($entity === null) {
				return ['ok' => false, 'errors' => ['_base' => __('Dispositivo não encontrado.')]];
			}
			$entity = $tbl->patchEntity($entity, $payload, ['validate' => false]);
		} else {
			$payload['created'] = date('Y-m-d H:i:s');
			$entity = $tbl->newEntity($payload, ['validate' => false]);
		}
		if (!$tbl->save($entity)) {
			return ['ok' => false, 'errors' => $entity->getErrors()];
		}
		$did = (int)$entity->get('id');
		$this->audit('dispositivo.salvar', 'lic_dispositivos', $did, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $did];
	}


	protected function clienteNomeFromEntity($cli): string {
		if ($cli === null) {
			return '';
		}
		return (int)$cli->get('tipo') === 2
			? (string)($cli->get('razaosocial') ?? $cli->get('nome'))
			: (string)$cli->get('nome');
	}

	protected function encodeSecret(?string $plain): ?string {
		$s = trim((string)$plain);
		if ($s === '') {
			return null;
		}

		return LicCofreCipher::encrypt($s);
	}

	protected function decodeSecret(?string $blob): ?string {
		return LicCofreCipher::decrypt($blob);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCofreItens(array $filters = [], int $limit = 150): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCofreItens') === null) {
			return [];
		}
		$q = $this->table('LicCofreItens')->find()
			->contain(['Clientes', 'LicLicencas'])
			->where(['LicCofreItens.idempresa' => $this->idempresa])
			->order(['LicCofreItens.modified' => 'DESC'])
			->limit($limit);
		if ($this->idclienteScope !== null) {
			$q->where(['LicCofreItens.idcliente' => $this->idclienteScope]);
		}
		$cliente = (int)($filters['cliente'] ?? 0);
		if ($cliente > 0) {
			$q->where(['LicCofreItens.idcliente' => $cliente]);
		}
		$out = [];
		foreach ($q->all() as $row) {
			$cli = $row->get('cliente');
			$lic = $row->get('lic_licenca');
			$blob = $row->get('secret_blob');
			$out[] = [
				'id' => (int)$row->get('id'),
				'titulo' => (string)$row->get('titulo'),
				'nivel' => (string)$row->get('nivel'),
				'cliente' => $this->clienteNomeFromEntity($cli),
				'idcliente' => (int)($row->get('idcliente') ?? 0),
				'idlicenca' => (int)($row->get('idlicenca') ?? 0),
				'licenca_codigo' => $lic ? (string)$lic->get('codigo') : '',
				'tem_segredo' => $blob !== null && $blob !== '',
			];
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getCofreItem(int $id, bool $includeSecret = false): ?array {
		if ($id <= 0 || $this->table('LicCofreItens') === null) {
			return null;
		}
		$where = ['LicCofreItens.id' => $id, 'LicCofreItens.idempresa' => $this->idempresa];
		if ($this->idclienteScope !== null) {
			$where['LicCofreItens.idcliente'] = $this->idclienteScope;
		}
		try {
			$row = $this->table('LicCofreItens')->find()
				->contain(['Clientes', 'LicLicencas'])
				->where($where)
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($row === null) {
			return null;
		}
		$cli = $row->get('cliente');
		$lic = $row->get('lic_licenca');
		$item = [
			'id' => (int)$row->get('id'),
			'titulo' => (string)$row->get('titulo'),
			'nivel' => (string)$row->get('nivel'),
			'idcliente' => (int)($row->get('idcliente') ?? 0),
			'idlicenca' => (int)($row->get('idlicenca') ?? 0),
			'cliente' => $this->clienteNomeFromEntity($cli),
			'licenca_codigo' => $lic ? (string)$lic->get('codigo') : '',
			'tem_segredo' => $row->get('secret_blob') !== null && $row->get('secret_blob') !== '',
		];
		if ($includeSecret) {
			$item['segredo'] = $this->decodeSecret($row->get('secret_blob'));
		}

		return $item;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function saveCofreItem(array $data, ?int $id = null): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicCofreItens') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$tbl = $this->table('LicCofreItens');
		$titulo = trim((string)($data['titulo'] ?? ''));
		if ($titulo === '') {
			return ['ok' => false, 'errors' => ['titulo' => __('Título obrigatório.')]];
		}
		$idcliente = (int)($data['idcliente'] ?? 0) ?: null;
		if ($this->idclienteScope !== null) {
			$idcliente = $this->idclienteScope;
		}
		$payload = [
			'idempresa' => $this->idempresa,
			'idcliente' => $idcliente,
			'idlicenca' => (int)($data['idlicenca'] ?? 0) ?: null,
			'titulo' => $titulo,
			'nivel' => trim((string)($data['nivel'] ?? 'medio')) ?: 'medio',
			'modified' => date('Y-m-d H:i:s'),
		];
		$segredo = trim((string)($data['segredo'] ?? ''));
		if ($segredo !== '') {
			$payload['secret_blob'] = $this->encodeSecret($segredo);
		}
		if ($id !== null && $id > 0) {
			$entity = $tbl->find()->where(['id' => $id, 'idempresa' => $this->idempresa])->first();
			if ($entity === null) {
				return ['ok' => false, 'errors' => ['_base' => __('Item não encontrado.')]];
			}
			if ($this->idclienteScope !== null && (int)$entity->get('idcliente') !== $this->idclienteScope) {
				return ['ok' => false, 'errors' => ['_base' => __('Acesso negado.')]];
			}
			$entity = $tbl->patchEntity($entity, $payload, ['validate' => false]);
		} else {
			$payload['created'] = date('Y-m-d H:i:s');
			$entity = $tbl->newEntity($payload, ['validate' => false]);
		}
		if (!$tbl->save($entity)) {
			return ['ok' => false, 'errors' => $entity->getErrors()];
		}
		$iid = (int)$entity->get('id');
		$this->audit('cofre.salvar', 'lic_cofre_itens', $iid, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $iid];
	}

	public function revealCofreSecret(int $id, int $userId, ?string $ip = null): ?string {
		$item = $this->getCofreItem($id, true);
		if ($item === null || empty($item['segredo'])) {
			return null;
		}
		$this->audit(
			'cofre.revelar_segredo',
			'lic_cofre_itens',
			$id,
			$userId,
			(string)($item['titulo'] ?? ''),
			$ip
		);

		return (string)$item['segredo'];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listSolicitacoes(array $filters = [], int $limit = 100): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicSolicitacoes') === null) {
			return [];
		}
		$q = $this->table('LicSolicitacoes')->find()
			->contain(['Clientes'])
			->where(['LicSolicitacoes.idempresa' => $this->idempresa])
			->order(['LicSolicitacoes.created' => 'DESC'])
			->limit($limit);
		if ($this->idclienteScope !== null) {
			$q->where(['LicSolicitacoes.idcliente' => $this->idclienteScope]);
		}
		$st = trim((string)($filters['status'] ?? ''));
		if ($st !== '') {
			$q->where(['LicSolicitacoes.status' => $st]);
		}
		$out = [];
		foreach ($q->all() as $row) {
			$cli = $row->get('cliente');
			$created = $row->get('created');
			$out[] = [
				'id' => (int)$row->get('id'),
				'tipo' => (string)$row->get('tipo'),
				'status' => (string)$row->get('status'),
				'cliente' => $this->clienteNomeFromEntity($cli),
				'idcliente' => (int)$row->get('idcliente'),
				'created' => $created && is_object($created) && method_exists($created, 'format')
					? $created->format('Y-m-d H:i')
					: (string)$created,
				'payload' => $this->decodeSolicitacaoPayload((string)($row->get('payload_json') ?? '')),
			];
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getSolicitacao(int $id): ?array {
		if ($id <= 0 || $this->table('LicSolicitacoes') === null) {
			return null;
		}
		$where = ['LicSolicitacoes.id' => $id, 'LicSolicitacoes.idempresa' => $this->idempresa];
		if ($this->idclienteScope !== null) {
			$where['LicSolicitacoes.idcliente'] = $this->idclienteScope;
		}
		try {
			$row = $this->table('LicSolicitacoes')->find()
				->contain(['Clientes'])
				->where($where)
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($row === null) {
			return null;
		}
		$cli = $row->get('cliente');
		$created = $row->get('created');

		return [
			'id' => (int)$row->get('id'),
			'tipo' => (string)$row->get('tipo'),
			'status' => (string)$row->get('status'),
			'cliente' => $this->clienteNomeFromEntity($cli),
			'idcliente' => (int)$row->get('idcliente'),
			'created' => $created && is_object($created) && method_exists($created, 'format')
				? $created->format('Y-m-d H:i')
				: (string)$created,
			'payload' => $this->decodeSolicitacaoPayload((string)($row->get('payload_json') ?? '')),
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,id?:int,errors?:array}
	 */
	public function createSolicitacao(array $data): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicSolicitacoes') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$idcliente = (int)($data['idcliente'] ?? 0);
		if ($this->idclienteScope !== null) {
			$idcliente = $this->idclienteScope;
		}
		if ($idcliente <= 0) {
			return ['ok' => false, 'errors' => ['idcliente' => __('Cliente obrigatório.')]];
		}
		$tipo = trim((string)($data['tipo'] ?? 'nova_licenca'));
		if ($tipo === '') {
			$tipo = 'nova_licenca';
		}
		$payload = [
			'produto' => trim((string)($data['produto'] ?? '')),
			'assentos' => max(1, (int)($data['assentos'] ?? 1)),
			'observacao' => trim((string)($data['observacao'] ?? '')),
		];
		$tbl = $this->table('LicSolicitacoes');
		$entity = $tbl->newEntity([
			'idempresa' => $this->idempresa,
			'idcliente' => $idcliente,
			'tipo' => $tipo,
			'status' => 'aberta',
			'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'created' => date('Y-m-d H:i:s'),
		], ['validate' => false]);
		if (!$tbl->save($entity)) {
			return ['ok' => false, 'errors' => $entity->getErrors()];
		}
		$sid = (int)$entity->get('id');
		$this->audit('solicitacao.criar', 'lic_solicitacoes', $sid, (int)($data['iduser'] ?? 0));

		return ['ok' => true, 'id' => $sid];
	}

	/**
	 * @return array{ok:bool,errors?:array}
	 */
	public function updateSolicitacaoStatus(int $id, string $status, int $userId = 0): array {
		if ($id <= 0 || $this->table('LicSolicitacoes') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Indisponível.')]];
		}
		$allowed = ['aberta', 'em_analise', 'aprovada', 'recusada', 'cancelada'];
		$status = trim($status);
		if (!in_array($status, $allowed, true)) {
			return ['ok' => false, 'errors' => ['status' => __('Status inválido.')]];
		}
		$tbl = $this->table('LicSolicitacoes');
		$row = $tbl->find()->where(['id' => $id, 'idempresa' => $this->idempresa])->first();
		if ($row === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Solicitação não encontrada.')]];
		}
		$row = $tbl->patchEntity($row, [
			'status' => $status,
			'modified' => date('Y-m-d H:i:s'),
		], ['validate' => false]);
		if (!$tbl->save($row)) {
			return ['ok' => false, 'errors' => $row->getErrors()];
		}
		$this->audit('solicitacao.status', 'lic_solicitacoes', $id, $userId, $status);

		return ['ok' => true];
	}

	/**
	 * @return array<string,string>
	 */
	protected function decodeSolicitacaoPayload(string $json): array {
		if ($json === '') {
			return [];
		}
		$decoded = json_decode($json, true);

		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listAuditoria(int $limit = 120): array {
		if ($this->idclienteScope !== null) {
			return [];
		}
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicAuditoriaEventos') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicAuditoriaEventos')->find()
				->where(['idempresa' => $this->idempresa])
				->order(['created' => 'DESC'])
				->limit($limit)
				->all() as $row) {
				$created = $row->get('created');
				$out[] = [
					'id' => (int)$row->get('id'),
					'acao' => (string)$row->get('acao'),
					'entidade' => (string)($row->get('entidade') ?? ''),
					'entidade_id' => (int)($row->get('entidade_id') ?? 0),
					'detalhe' => (string)($row->get('detalhe') ?? ''),
					'iduser' => (int)($row->get('iduser') ?? 0),
					'ip' => (string)($row->get('ip') ?? ''),
					'created' => $created && is_object($created) && method_exists($created, 'format')
						? $created->format('Y-m-d H:i')
						: (string)$created,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getModuloConfig(): array {
		$defaults = [
			'alerta_vencimento_dias' => 30,
			'notificar_email' => '',
			'cofre_exige_aprovacao' => false,
		];
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicModuloConfig') === null) {
			return $defaults;
		}
		try {
			$row = $this->table('LicModuloConfig')->find()
				->where(['idempresa' => $this->idempresa])
				->first();
		} catch (\Throwable $e) {
			return $defaults;
		}
		if ($row === null) {
			return $defaults;
		}

		return [
			'alerta_vencimento_dias' => (int)$row->get('alerta_vencimento_dias'),
			'notificar_email' => (string)($row->get('notificar_email') ?? ''),
			'cofre_exige_aprovacao' => (bool)$row->get('cofre_exige_aprovacao'),
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,errors?:array}
	 */
	public function saveModuloConfig(array $data, int $userId = 0): array {
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicModuloConfig') === null) {
			return ['ok' => false, 'errors' => ['_base' => __('Execute a migration lic_modulo_config.')]];
		}
		$tbl = $this->table('LicModuloConfig');
		$payload = [
			'idempresa' => $this->idempresa,
			'alerta_vencimento_dias' => max(1, min(365, (int)($data['alerta_vencimento_dias'] ?? 30))),
			'notificar_email' => trim((string)($data['notificar_email'] ?? '')) ?: null,
			'cofre_exige_aprovacao' => !empty($data['cofre_exige_aprovacao']),
			'modified' => date('Y-m-d H:i:s'),
		];
		$row = $tbl->find()->where(['idempresa' => $this->idempresa])->first();
		if ($row === null) {
			$payload['created'] = date('Y-m-d H:i:s');
			$row = $tbl->newEntity($payload, ['validate' => false]);
		} else {
			$row = $tbl->patchEntity($row, $payload, ['validate' => false]);
		}
		if (!$tbl->save($row)) {
			return ['ok' => false, 'errors' => $row->getErrors()];
		}
		$this->audit('config.salvar', 'lic_modulo_config', (int)$this->idempresa, $userId);

		return ['ok' => true];
	}

	/**
	 * KPIs resumidos para portal cliente.
	 *
	 * @return array<string,int>
	 */
	public function portalDashboardKpis(): array {
		$licencas = $this->listLicencas(['status' => 'ativa'], 500);
		$solicitacoes = $this->listSolicitacoes(['status' => 'aberta'], 50);
		$cofre = $this->listCofreItens([], 500);

		return [
			'licencas_ativas' => count($licencas),
			'solicitacoes_abertas' => count($solicitacoes),
			'itens_cofre' => count($cofre),
		];
	}


	/**
	 * KPIs e insights derivados de lic_* (sem ML externo).
	 *
	 * @return array<string,mixed>
	 */
	public function buildInteligencia(): array {
		$kpis = $this->dashboardKpis();
		$renov = $this->listRenovacoes();
		$vencido = count($renov['vencido'] ?? []);
		$d30 = count($renov['d30'] ?? []);
		$custoAnual = 0.0;
		$ociosos = 0;
		$lic = $this->licTable();
		if ($lic !== null) {
			try {
				foreach ($lic->find()
					->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
					->contain(['LicAssentos'])
					->all() as $row) {
					$va = $row->get('valor_anual');
					if ($va !== null && $va !== '') {
						$custoAnual += (float)$va;
					}
					$cap = (int)$row->get('assentos');
					$used = 0;
					foreach ($row->get('lic_assentos') ?? [] as $a) {
						$em = trim((string)$a->get('email'));
						if ($em !== '') {
							$used++;
						}
					}
					if ($cap > $used && $cap > 0) {
						$ociosos += ($cap - $used);
					}
				}
			} catch (\Throwable $e) {
			}
		}
		$insights = [];
		if ($vencido > 0) {
			$insights[] = [
				'severity' => 'danger',
				'title' => __('{0} licença(s) vencida(s)', $vencido),
				'detail' => __('Revise renovações e status no pipeline.'),
				'url' => ['controller' => 'LicencasPrototype', 'action' => 'view', 'renovacoes'],
			];
		}
		if ($d30 > 0) {
			$insights[] = [
				'severity' => 'warn',
				'title' => __('{0} licença(s) vencem em 30 dias', $d30),
				'detail' => __('Antecipe contato com clientes e fornecedores.'),
				'url' => ['controller' => 'LicencasPrototype', 'action' => 'view', 'calendario'],
			];
		}
		if ($ociosos > 0) {
			$insights[] = [
				'severity' => 'ok',
				'title' => __('{0} assento(s) sem e-mail atribuído', $ociosos),
				'detail' => __('Possível subutilização — compare assentos contratados vs. LicAssentos.'),
				'url' => ['controller' => 'LicencasPrototype', 'action' => 'licencas'],
			];
		}
		if ((int)($kpis['solicitacoes_abertas'] ?? 0) > 0) {
			$insights[] = [
				'severity' => 'info',
				'title' => __('{0} solicitação(ões) aberta(s)', (int)$kpis['solicitacoes_abertas']),
				'detail' => __('Portal cliente aguardando triagem.'),
				'url' => ['controller' => 'LicencasPrototype', 'action' => 'view', 'solicitacoes'],
			];
		}
		if ($insights === []) {
			$insights[] = [
				'severity' => 'info',
				'title' => __('Sem alertas automáticos no momento'),
				'detail' => __('Cadastre licenças e assentos para enriquecer os insights.'),
				'url' => ['controller' => 'LicencasPrototype', 'action' => 'dashboard'],
			];
		}

		return [
			'kpis' => [
				'custo_anual_estimado' => $custoAnual,
				'vencidas' => $vencido,
				'venc_30' => $d30,
				'assentos_ociosos' => $ociosos,
				'solicitacoes_abertas' => (int)($kpis['solicitacoes_abertas'] ?? 0),
			],
			'insights' => $insights,
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listLicencaHistorico(int $licId, int $limit = 50): array {
		if ($licId <= 0) {
			return [];
		}
		$loc = TableRegistry::getTableLocator();
		if ($this->table('LicAuditoriaEventos') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicAuditoriaEventos')->find()
				->where([
					'idempresa' => $this->idempresa,
					'entidade' => 'lic_licencas',
					'entidade_id' => $licId,
				])
				->order(['created' => 'DESC'])
				->limit($limit)
				->all() as $row) {
				$created = $row->get('created');
				$out[] = [
					'acao' => (string)$row->get('acao'),
					'detalhe' => (string)($row->get('detalhe') ?? ''),
					'iduser' => (int)($row->get('iduser') ?? 0),
					'created' => $created && is_object($created) && method_exists($created, 'format')
						? $created->format('Y-m-d H:i')
						: (string)$created,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<int,string>>
	 */
	public function logRelatorioExport(string $tipo, int $userId): void {
		$this->audit('relatorio.exportar', 'lic_relatorios', 0, $userId, $tipo);
	}

	public function buildRelatorioCsvRows(string $tipo): array {
		$tipo = strtolower(trim($tipo));
		if ($tipo === 'renovacoes') {
			$rows = [['codigo', 'cliente', 'fim', 'dias', 'valor_anual']];
			$renov = $this->listRenovacoes(120);
			foreach (['vencido', 'd30', 'd60', 'd90'] as $bucket) {
				foreach ($renov[$bucket] ?? [] as $item) {
					$rows[] = [
						(string)$item['codigo'],
						(string)$item['cliente'],
						(string)$item['fim'],
						(string)($item['dias'] ?? ''),
						(string)($item['valor_anual'] ?? ''),
					];
				}
			}

			return $rows;
		}
		if ($tipo === 'dispositivos') {
			$rows = [['cliente', 'hostname', 'serial', 'so', 'ultimo_visto']];
			foreach ($this->listDispositivos() as $d) {
				$rows[] = [
					(string)$d['cliente'],
					(string)$d['hostname'],
					(string)$d['serial'],
					(string)$d['so'],
					(string)($d['ultimo_visto'] ?? ''),
				];
			}

			return $rows;
		}
		$rows = [['codigo', 'cliente', 'produto', 'status', 'assentos', 'inicio', 'fim', 'valor_anual']];
		foreach ($this->listLicencas([], 500) as $lic) {
			$ini = $lic['inicio'];
			$fim = $lic['fim'];
			$iniS = is_object($ini) && method_exists($ini, 'format') ? $ini->format('Y-m-d') : (string)$ini;
			$fimS = is_object($fim) && method_exists($fim, 'format') ? $fim->format('Y-m-d') : (string)$fim;
			$rows[] = [
				(string)$lic['codigo'],
				(string)$lic['cliente'],
				(string)$lic['produto'],
				(string)$lic['status'],
				(string)$lic['assentos'],
				$iniS,
				$fimS,
				(string)($lic['valor_anual'] ?? ''),
			];
		}

		return $rows;
	}


	/**
	 * Empresas-cliente: resumo por cliente (ORM, sem SQL raw).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function listEmpresasClienteResumo(int $limit = 80): array {
		if (!$this->tablesAvailable() || $this->idempresa <= 0) {
			return [];
		}
		$loc = TableRegistry::getTableLocator();
		$clientes = [];
		try {
			foreach ($this->table('Clientes')->find()
				->where(['Clientes.idempresa' => $this->idempresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit($limit)
				->all() as $c) {
				$clientes[(int)$c->get('id')] = $c;
			}
		} catch (\Throwable $e) {
			return [];
		}
		if ($clientes === []) {
			return [];
		}
		$ids = array_keys($clientes);
		$licCounts = [];
		$devCounts = [];
		$valorAnual = [];
		$vencidas = [];
		$hoje = (new \DateTimeImmutable('today'))->format('Y-m-d');
		$lic = $this->licTable();
		if ($lic !== null) {
			try {
				foreach ($lic->find()
					->select(['idcliente', 'status', 'valor_anual', 'fim'])
					->where(['idempresa' => $this->idempresa, 'idcliente IN' => $ids])
					->all() as $row) {
					$cid = (int)$row->get('idcliente');
					$licCounts[$cid] = ($licCounts[$cid] ?? 0) + 1;
					$va = $row->get('valor_anual');
					if ($va !== null && $va !== '') {
						$valorAnual[$cid] = ($valorAnual[$cid] ?? 0) + (float)$va;
					}
					$st = (string)$row->get('status');
					$fim = $row->get('fim');
					$fimS = is_object($fim) && method_exists($fim, 'format') ? $fim->format('Y-m-d') : (string)$fim;
					if ($st === 'ativa' && $fimS !== '' && $fimS < $hoje) {
						$vencidas[$cid] = ($vencidas[$cid] ?? 0) + 1;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		if ($this->table('LicDispositivos') !== null) {
			try {
				foreach ($this->table('LicDispositivos')->find()
					->select(['idcliente'])
					->where(['idempresa' => $this->idempresa, 'idcliente IN' => $ids])
					->all() as $d) {
					$cid = (int)$d->get('idcliente');
					$devCounts[$cid] = ($devCounts[$cid] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		$out = [];
		foreach ($clientes as $cid => $c) {
			$nome = $this->clienteNomeFromEntity($c);
			$out[] = [
				'id' => $cid,
				'nome' => $nome,
				'cnpj' => (string)($c->get('cnpj') ?? $c->get('cpf') ?? ''),
				'email' => (string)($c->get('email') ?? ''),
				'licencas' => (int)($licCounts[$cid] ?? 0),
				'dispositivos' => (int)($devCounts[$cid] ?? 0),
				'valor_anual' => (float)($valorAnual[$cid] ?? 0),
				'vencidas' => (int)($vencidas[$cid] ?? 0),
			];
		}
		usort($out, static function ($a, $b) {
			return ($b['valor_anual'] <=> $a['valor_anual']) ?: strcmp((string)$a['nome'], (string)$b['nome']);
		});

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getEmpresaClienteResumo(int $idcliente): ?array {
		if ($idcliente <= 0) {
			return null;
		}
		$loc = TableRegistry::getTableLocator();
		try {
			$c = $this->table('Clientes')->find()
				->where(['Clientes.id' => $idcliente, 'Clientes.idempresa' => $this->idempresa])
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($c === null) {
			return null;
		}
		foreach ($this->listEmpresasClienteResumo(500) as $row) {
			if ((int)$row['id'] === $idcliente) {
				$row['licencas_rows'] = $this->listLicencas(['cliente' => (string)$idcliente], 30);

				return $row;
			}
		}
		return [
			'id' => $idcliente,
			'nome' => $this->clienteNomeFromEntity($c),
			'cnpj' => (string)($c->get('cnpj') ?? $c->get('cpf') ?? ''),
			'email' => (string)($c->get('email') ?? ''),
			'licencas' => 0,
			'dispositivos' => 0,
			'valor_anual' => 0.0,
			'vencidas' => 0,
			'licencas_rows' => [],
		];
	}


	/**
	 * Fornecedores (clientes PJ) com vínculo ao catálogo/licenças.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function listFornecedoresResumo(int $limit = 80): array {
		if (!$this->tablesAvailable() || $this->idempresa <= 0) {
			return [];
		}
		$loc = TableRegistry::getTableLocator();
		$pjTipo = defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2;
		$fornecedores = [];
		try {
			foreach ($this->table('Clientes')->find()
				->where(['Clientes.idempresa' => $this->idempresa, 'Clientes.tipo' => $pjTipo, 'Clientes.inativo' => 0])
				->order(['Clientes.razaosocial' => 'ASC'])
				->limit($limit)
				->all() as $c) {
				$fornecedores[(int)$c->get('id')] = $c;
			}
		} catch (\Throwable $e) {
			return [];
		}
		if ($fornecedores === []) {
			return [];
		}
		$ids = array_keys($fornecedores);
		$prodCounts = [];
		$licCounts = [];
		if ($this->table('LicCatalogoProdutos') !== null) {
			try {
				foreach ($this->table('LicCatalogoProdutos')->find()
					->select(['id', 'idfornecedor_cliente'])
					->where(['idempresa' => $this->idempresa, 'idfornecedor_cliente IN' => $ids])
					->all() as $prod) {
					$fid = (int)$prod->get('idfornecedor_cliente');
					$prodCounts[$fid] = ($prodCounts[$fid] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		$lic = $this->licTable();
		if ($lic !== null && $this->table('LicCatalogoProdutos') !== null) {
			try {
				foreach ($lic->find()
					->select(['LicLicencas.id', 'LicCatalogoProdutos.idfornecedor_cliente'])
					->contain(['LicCatalogoProdutos'])
					->where(['LicLicencas.idempresa' => $this->idempresa])
					->all() as $row) {
					$prod = $row->get('lic_catalogo_produto');
					if ($prod === null) {
						continue;
					}
					$fid = (int)$prod->get('idfornecedor_cliente');
					if ($fid <= 0) {
						continue;
					}
					$licCounts[$fid] = ($licCounts[$fid] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		$out = [];
		foreach ($fornecedores as $fid => $c) {
			$out[] = [
				'id' => $fid,
				'nome' => $this->clienteNomeFromEntity($c),
				'cnpj' => (string)($c->get('cnpj') ?? ''),
				'email' => (string)($c->get('email') ?? ''),
				'produtos_catalogo' => (int)($prodCounts[$fid] ?? 0),
				'licencas' => (int)($licCounts[$fid] ?? 0),
			];
		}
		usort($out, static function ($a, $b) {
			return ($b['produtos_catalogo'] <=> $a['produtos_catalogo']) ?: strcmp((string)$a['nome'], (string)$b['nome']);
		});

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getFornecedorResumo(int $idfornecedor): ?array {
		foreach ($this->listFornecedoresResumo(500) as $row) {
			if ((int)$row['id'] === $idfornecedor) {
				$loc = TableRegistry::getTableLocator();
				$produtos = [];
				if ($this->table('LicCatalogoProdutos') !== null) {
					try {
						foreach ($this->table('LicCatalogoProdutos')->find()
							->where([
								'idempresa' => $this->idempresa,
								'idfornecedor_cliente' => $idfornecedor,
							])
							->order(['nome' => 'ASC'])
							->limit(50)
							->all() as $p) {
							$produtos[] = [
								'id' => (int)$p->get('id'),
								'nome' => (string)$p->get('nome'),
								'sku' => (string)($p->get('sku') ?? ''),
							];
						}
					} catch (\Throwable $e) {
					}
				}
				$row['produtos'] = $produtos;

				return $row;
			}
		}

		return null;
	}

	/**
	 * @return \App\Model\Table\LicLicencasTable|null
	 */
	protected function licTable() {
		if (!$this->tablesAvailable()) {
			return null;
		}

		return $this->table('LicLicencas');
	}
}
