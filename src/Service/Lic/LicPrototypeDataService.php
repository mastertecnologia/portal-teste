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
	 * @return array<int,array<string,mixed>>
	 */
	public function listCategorias(): array {
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicCategorias')) {
			return [];
		}
		$out = [];
		try {
			foreach ($loc->get('LicCategorias')->find()
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
		if (!$loc->exists('LicCatalogoProdutos')) {
			return [];
		}
		$out = [];
		try {
			foreach ($loc->get('LicCatalogoProdutos')->find()
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
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicCatalogoProdutos') || $id <= 0) {
			return null;
		}
		try {
			$p = $loc->get('LicCatalogoProdutos')->find()
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
		if (!$loc->exists('LicCatalogoProdutos')) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$tbl = $loc->get('LicCatalogoProdutos');
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
		if (!$loc->exists('LicCategorias')) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$tbl = $loc->get('LicCategorias');
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
		if (!$loc->exists('LicDispositivos')) {
			return [];
		}
		$q = $loc->get('LicDispositivos')->find()
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
		$loc = TableRegistry::getTableLocator();
		if (!$loc->exists('LicDispositivos') || $id <= 0) {
			return null;
		}
		try {
			$d = $loc->get('LicDispositivos')->find()
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
		if (!$loc->exists('LicDispositivos')) {
			return ['ok' => false, 'errors' => ['_base' => __('Tabelas indisponíveis.')]];
		}
		$idcliente = (int)($data['idcliente'] ?? 0);
		if ($idcliente <= 0) {
			return ['ok' => false, 'errors' => ['idcliente' => __('Cliente obrigatório.')]];
		}
		$tbl = $loc->get('LicDispositivos');
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
