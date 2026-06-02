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
	 * @return array<string,int|float>
	 */
	public function dashboardKpis(): array {
		$out = [
			'empresas_cliente' => 0,
			'empresas_novas_30d' => 0,
			'licencas_ativas' => 0,
			'assentos' => 0,
			'venc_30' => 0,
			'vencidas' => 0,
			'renovacao_valor_30' => 0.0,
			'receita_anual' => 0.0,
			'cofre_itens' => 0,
			'cofre_views_7d' => 0,
			'dispositivos' => 0,
			'subutilizadas' => 0,
			'solicitacoes_abertas' => 0,
		];
		if (!$this->tablesAvailable() || $this->idempresa <= 0) {
			return $out;
		}
		$lic = $this->licTable();
		if ($lic === null) {
			return $out;
		}
		$hoje = new \DateTimeImmutable('today');
		$hojeS = $hoje->format('Y-m-d');
		$lim30 = $hoje->modify('+30 days')->format('Y-m-d');
		$lim30Clientes = $hoje->modify('-30 days')->format('Y-m-d 00:00:00');
		$clientesComLic = [];
		$clientesNovos = [];
		try {
			$out['licencas_ativas'] = $lic->find()
				->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
				->count();
			$assentos = 0;
			$subutil = 0;
			foreach ($lic->find()
				->where(['idempresa' => $this->idempresa, 'status IN' => ['ativa', 'rascunho']])
				->contain(['LicAssentos'])
				->all() as $row) {
				$assentos += (int)$row->get('assentos');
				if ((string)$row->get('status') !== 'ativa') {
					continue;
				}
				$cid = (int)$row->get('idcliente');
				if ($cid > 0) {
					$clientesComLic[$cid] = true;
				}
				$created = $row->get('created');
				if ($created !== null) {
					$createdS = is_object($created) && method_exists($created, 'format')
						? $created->format('Y-m-d H:i:s')
						: (string)$created;
					if ($createdS >= $lim30Clientes) {
						$clientesNovos[$cid] = true;
					}
				}
				$va = $row->get('valor_anual');
				if ($va !== null && $va !== '') {
					$out['receita_anual'] += (float)$va;
				}
				$fim = $row->get('fim');
				$fimS = is_object($fim) && method_exists($fim, 'format') ? $fim->format('Y-m-d') : (string)$fim;
				if ($fimS !== '' && $fimS < $hojeS) {
					$out['vencidas']++;
				} elseif ($fimS !== '' && $fimS >= $hojeS && $fimS <= $lim30) {
					$out['venc_30']++;
					if ($va !== null && $va !== '') {
						$out['renovacao_valor_30'] += (float)$va;
					}
				}
				$cap = (int)$row->get('assentos');
				$used = 0;
				foreach ($row->get('lic_assentos') ?? [] as $a) {
					if (trim((string)$a->get('email')) !== '') {
						$used++;
					}
				}
				if ($cap > $used && $cap > 0) {
					$subutil++;
				}
			}
			$out['assentos'] = $assentos;
			$out['empresas_cliente'] = count($clientesComLic);
			$out['empresas_novas_30d'] = count($clientesNovos);
			$out['subutilizadas'] = $subutil;
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
			$cofre = $this->table('LicCofreItens');
			if ($cofre !== null) {
				$out['cofre_itens'] = $cofre->find()
					->where(['idempresa' => $this->idempresa])
					->count();
			}
			$aud = $this->table('LicAuditoriaEventos');
			if ($aud !== null) {
				$desde = $hoje->modify('-7 days')->format('Y-m-d H:i:s');
				$out['cofre_views_7d'] = $aud->find()
					->where([
						'idempresa' => $this->idempresa,
						'acao' => 'cofre.revelar_segredo',
						'created >=' => $desde,
					])
					->count();
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * Dados agregados do painel (pg-lic-dashboard).
	 *
	 * @return array<string,mixed>
	 */
	public function buildDashboardData(): array {
		return [
			'kpis' => $this->dashboardKpis(),
			'proximos_vencimentos' => $this->dashboardProximosVencimentos(5),
			'top_empresas' => array_slice($this->listEmpresasClienteResumo(4), 0, 4),
			'por_categoria' => $this->dashboardPorCategoria(),
			'atividade_recente' => $this->dashboardAtividadeRecente(5),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function dashboardProximosVencimentos(int $limit = 5): array {
		$lic = $this->licTable();
		if ($lic === null) {
			return [];
		}
		$hoje = new \DateTimeImmutable('today');
		$lim = $hoje->modify('+90 days');
		$out = [];
		try {
			$rows = $lic->find()
				->contain(['Clientes', 'LicCatalogoProdutos'])
				->where([
					'LicLicencas.idempresa' => $this->idempresa,
					'LicLicencas.status' => 'ativa',
					'LicLicencas.fim IS NOT' => null,
					'LicLicencas.fim <=' => $lim->format('Y-m-d'),
				])
				->order(['LicLicencas.fim' => 'ASC'])
				->limit($limit)
				->all();
		} catch (\Throwable $e) {
			return [];
		}
		foreach ($rows as $row) {
			$fim = $row->get('fim');
			if ($fim === null) {
				continue;
			}
			$fimDt = is_object($fim) && method_exists($fim, 'format')
				? new \DateTimeImmutable($fim->format('Y-m-d'))
				: new \DateTimeImmutable((string)$fim);
			$dias = (int)round(($fimDt->getTimestamp() - $hoje->getTimestamp()) / 86400);
			$cli = $row->get('cliente');
			$cat = $row->get('lic_catalogo_produto');
			$produto = (string)($row->get('produto_label') ?? '');
			if ($produto === '' && $cat !== null) {
				$produto = (string)$cat->get('nome');
			}
			$assentos = (int)$row->get('assentos');
			$modelo = (string)$row->get('modelo');
			$licLabel = $produto;
			if ($licLabel !== '' && $assentos > 0) {
				$unidade = $modelo === 'perpetua' ? __('licenças') : __('assentos');
				$licLabel .= ' · ' . $assentos . ' ' . $unidade;
			}
			$va = $row->get('valor_anual');
			$renovacao = $va !== null && $va !== '' ? (float)$va / 12 : 0.0;
			$st = $this->vencimentoStatusBadge($dias);
			$out[] = [
				'id' => (int)$row->get('id'),
				'dias' => $dias,
				'fim' => $fimDt->format('Y-m-d'),
				'fim_fmt' => $fimDt->format('d/m/Y'),
				'cliente' => $this->clienteNomeFromEntity($cli),
				'licenca' => $licLabel,
				'renovacao' => $renovacao,
				'status_label' => $st['label'],
				'status_kind' => $st['kind'],
				'row_bg' => $st['row_bg'],
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function dashboardPorCategoria(): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$counts = [];
		$lic = $this->licTable();
		if ($lic !== null) {
			try {
				foreach ($lic->find()
					->contain(['LicCatalogoProdutos' => ['LicCategorias']])
					->where(['LicLicencas.idempresa' => $this->idempresa, 'LicLicencas.status' => 'ativa'])
					->all() as $row) {
					$cat = $row->get('lic_catalogo_produto');
					$catEnt = $cat ? $cat->get('lic_categoria') : null;
					$nome = $catEnt ? (string)$catEnt->get('nome') : __('Sem categoria');
					$counts[$nome] = ($counts[$nome] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		if ($counts === []) {
			foreach ($this->listCategorias() as $c) {
				$counts[(string)$c['nome']] = 0;
			}
		}
		arsort($counts);
		$max = max(1, max($counts));
		$out = [];
		$palette = [
			__('Sistemas Operacionais') => ['color' => 'var(--blue)', 'icon' => '💻'],
			__('Office') => ['color' => 'var(--teal)', 'icon' => '📄'],
			__('Office & Produtividade') => ['color' => 'var(--teal)', 'icon' => '📄'],
			__('Design & Engenharia') => ['color' => '#6B21A8', 'icon' => '🎨'],
			__('Segurança') => ['color' => '#991B1B', 'icon' => '🛡'],
			__('Cloud & Marketing') => ['color' => '#F59E0B', 'icon' => '☁'],
		];
		foreach ($counts as $nome => $total) {
			$meta = $palette[$nome] ?? ['color' => 'var(--text-muted)', 'icon' => '📦'];
			$out[] = [
				'nome' => $nome,
				'total' => $total,
				'pct' => (int)round(($total / $max) * 100),
				'color' => $meta['color'],
				'icon' => $meta['icon'],
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function dashboardAtividadeRecente(int $limit = 5): array {
		$events = $this->listAuditoria($limit);
		if ($events === []) {
			return [];
		}
		$userNames = [];
		try {
			$ids = array_values(array_unique(array_filter(array_map(static function ($e) {
				return (int)($e['iduser'] ?? 0);
			}, $events))));
			if ($ids !== []) {
				foreach ($this->table('Users')->find()
					->select(['id', 'name'])
					->where(['id IN' => $ids])
					->all() as $u) {
					$userNames[(int)$u->get('id')] = (string)$u->get('name');
				}
			}
		} catch (\Throwable $e) {
		}
		$out = [];
		foreach ($events as $ev) {
			$uid = (int)($ev['iduser'] ?? 0);
			$autor = $uid > 0 ? ($userNames[$uid] ?? __('Usuário')) : __('Sistema');
			$parsed = $this->formatAuditoriaLinha((string)$ev['acao'], (string)($ev['detalhe'] ?? ''), $ev['created']);
			$out[] = [
				'autor' => $autor,
				'titulo' => $parsed['titulo'],
				'detalhe' => $parsed['detalhe'],
				'cor' => $parsed['cor'],
				'quando' => $parsed['quando'],
			];
		}

		return $out;
	}

	/**
	 * Clientes do ERP para busca no wizard (passo 1).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function listClientesWizardBusca(int $limit = 400): array {
		if ($this->idempresa <= 0) {
			return [];
		}
		$licAtivas = [];
		$lic = $this->licTable();
		if ($lic !== null) {
			try {
				foreach ($lic->find()
					->select(['idcliente'])
					->where(['idempresa' => $this->idempresa, 'status' => 'ativa'])
					->all() as $row) {
					$cid = (int)$row->get('idcliente');
					$licAtivas[$cid] = ($licAtivas[$cid] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		$out = [];
		try {
			foreach ($this->table('Clientes')->find()
				->where(['Clientes.idempresa' => $this->idempresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit($limit)
				->all() as $c) {
				$cid = (int)$c->get('id');
				$nome = $this->clienteNomeFromEntity($c);
				$cnpj = (string)($c->get('cnpj') ?? $c->get('cpf') ?? '');
				$out[] = [
					'id' => $cid,
					'nome' => $nome,
					'cnpj' => $cnpj,
					'licencas_ativas' => (int)($licAtivas[$cid] ?? 0),
					'iniciais' => $this->iniciaisNome($nome),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * Categorias com contagem de produtos ativos (wizard / catálogo).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function listCategoriasComContagem(): array {
		$this->ensureLicenciamentoBase();
		$counts = [];
		if ($this->table('LicCatalogoProdutos') !== null) {
			try {
				foreach ($this->table('LicCatalogoProdutos')->find()
					->select(['idcategoria'])
					->where(['idempresa' => $this->idempresa, 'ativo' => true])
					->all() as $p) {
					$idc = (int)$p->get('idcategoria');
					$counts[$idc] = ($counts[$idc] ?? 0) + 1;
				}
			} catch (\Throwable $e) {
			}
		}
		$out = [];
		foreach ($this->listCategorias() as $c) {
			if (empty($c['ativo'])) {
				continue;
			}
			$id = (int)$c['id'];
			$out[] = $c + [
				'produtos' => (int)($counts[$id] ?? 0),
				'icon' => $this->categoriaIcon((string)$c['codigo']),
			];
		}

		return $out;
	}

	/**
	 * Produtos do catálogo com fornecedor (wizard passo 1).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function listCatalogoProdutosWizard(): array {
		$this->ensureLicenciamentoBase();
		if ($this->table('LicCatalogoProdutos') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('LicCatalogoProdutos')->find()
				->contain(['LicCategorias'])
				->where(['LicCatalogoProdutos.idempresa' => $this->idempresa, 'LicCatalogoProdutos.ativo' => true])
				->order(['LicCatalogoProdutos.nome' => 'ASC'])
				->limit(500)
				->all() as $p) {
				$cat = $p->get('lic_categoria');
				$out[] = [
					'id' => (int)$p->get('id'),
					'nome' => (string)$p->get('nome'),
					'sku' => (string)($p->get('sku') ?? ''),
					'idcategoria' => (int)$p->get('idcategoria'),
					'categoria' => $cat ? (string)$cat->get('nome') : '',
					'categoria_codigo' => $cat ? (string)$cat->get('codigo') : '',
					'idfornecedor_cliente' => (int)$p->get('idfornecedor_cliente'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array{label:string,kind:string,row_bg:string}
	 */
	protected function vencimentoStatusBadge(int $dias): array {
		if ($dias < 0) {
			return ['label' => 'URGENTE', 'kind' => 'urgente', 'row_bg' => '#FEF2F2'];
		}
		if ($dias <= 7) {
			return ['label' => __('Aviso'), 'kind' => 'aviso', 'row_bg' => '#FFFBEB'];
		}
		if ($dias <= 30) {
			return ['label' => __('Em renovação'), 'kind' => 'renovacao', 'row_bg' => ''];
		}

		return ['label' => __('Planejada'), 'kind' => 'planejada', 'row_bg' => ''];
	}

	/**
	 * @return array{titulo:string,detalhe:string,cor:string,quando:string}
	 */
	protected function formatAuditoriaLinha(string $acao, string $detalhe, string $created): array {
		$cor = 'var(--teal)';
		$titulo = $acao;
		if (strpos($acao, 'cofre') !== false) {
			$cor = '#6B21A8';
			$titulo = __('revelou senha do cofre');
		} elseif (strpos($acao, 'licenca') !== false) {
			$cor = 'var(--teal)';
			$titulo = __('atualizou licença');
		} elseif (strpos($acao, 'config') !== false) {
			$cor = 'var(--blue)';
			$titulo = __('alterou configurações');
		} elseif (strpos($acao, 'dispositivo') !== false) {
			$cor = 'var(--blue)';
			$titulo = __('registrou dispositivo');
		}
		$quando = $created;
		if ($created !== '') {
			$ts = strtotime($created);
			if ($ts !== false) {
				$diff = time() - $ts;
				if ($diff < 3600) {
					$quando = __('há {0} min', max(1, (int)round($diff / 60)));
				} elseif ($diff < 86400) {
					$quando = __('há {0} h', max(1, (int)round($diff / 3600)));
				}
			}
		}

		return [
			'titulo' => $titulo,
			'detalhe' => $detalhe !== '' ? $detalhe : $acao,
			'cor' => $cor,
			'quando' => $quando,
		];
	}

	protected function iniciaisNome(string $nome): string {
		$nome = trim(preg_replace('/\s+/', ' ', $nome) ?? '');
		if ($nome === '') {
			return '?';
		}
		$parts = explode(' ', $nome);
		$ini = mb_substr($parts[0], 0, 1);
		if (count($parts) > 1) {
			$ini .= mb_substr($parts[count($parts) - 1], 0, 1);
		}

		return mb_strtoupper($ini);
	}

	protected function categoriaIcon(string $codigo): string {
		$map = [
			'SO' => '💻',
			'OFFICE' => '📄',
			'DESIGN' => '🎨',
			'SEG' => '🛡',
			'SEGURANCA' => '🛡',
			'CLOUD' => '☁',
			'COM' => '📞',
			'DEVOPS' => '⚡',
		];
		$key = mb_strtoupper(preg_replace('/[^A-Z0-9]/', '', $codigo) ?? $codigo);

		return $map[$key] ?? '📦';
	}

	/**
	 * @param float $v
	 */
	public function formatReceitaCompacta($v): string {
		$n = is_numeric($v) ? (float)$v : 0.0;
		if ($n >= 1000000) {
			return 'R$ ' . number_format($n / 1000000, 2, ',', '.') . 'M';
		}
		if ($n >= 1000) {
			return 'R$ ' . number_format($n / 1000, 1, ',', '.') . 'k';
		}

		return 'R$ ' . number_format($n, 2, ',', '.');
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
	 * Categorias padrão do mockup (pg-lic-nova) quando a empresa ainda não tem nenhuma.
	 *
	 * @return int quantidade criada
	 */
	public function ensureCategoriasPadrao(int $userId = 0): int {
		if (!$this->tablesAvailable() || $this->idempresa <= 0) {
			return 0;
		}
		if ($this->table('LicCategorias') === null) {
			return 0;
		}
		try {
			$exists = $this->table('LicCategorias')->find()
				->where(['idempresa' => $this->idempresa])
				->count();
		} catch (\Throwable $e) {
			return 0;
		}
		if ($exists > 0) {
			return 0;
		}
		$created = 0;
		foreach ($this->categoriasPadraoDefinicao() as $def) {
			$res = $this->saveCategoria([
				'codigo' => $def['codigo'],
				'nome' => $def['nome'],
				'ativo' => true,
				'iduser' => $userId,
			], null);
			if (!empty($res['ok'])) {
				$created++;
			}
		}

		return $created;
	}

	/**
	 * Produtos de referência no catálogo quando a empresa não tem nenhum (habilita o wizard).
	 *
	 * @return int quantidade criada
	 */
	public function ensureCatalogoInicial(int $userId = 0): int {
		if (!$this->tablesAvailable() || $this->table('LicCatalogoProdutos') === null) {
			return 0;
		}
		try {
			$total = $this->table('LicCatalogoProdutos')->find()
				->where(['idempresa' => $this->idempresa])
				->count();
		} catch (\Throwable $e) {
			return 0;
		}
		if ($total > 0) {
			return 0;
		}
		$this->ensureCategoriasPadrao($userId);
		$byCodigo = [];
		foreach ($this->listCategorias() as $c) {
			$byCodigo[mb_strtoupper((string)$c['codigo'])] = (int)$c['id'];
		}
		$created = 0;
		foreach ($this->catalogoInicialDefinicao() as $item) {
			$cod = mb_strtoupper((string)$item['categoria_codigo']);
			$idcategoria = (int)($byCodigo[$cod] ?? 0);
			if ($idcategoria <= 0) {
				continue;
			}
			$res = $this->saveCatalogoProduto([
				'idcategoria' => $idcategoria,
				'sku' => $item['sku'],
				'nome' => $item['nome'],
				'ativo' => true,
				'iduser' => $userId,
			], null);
			if (!empty($res['ok'])) {
				$created++;
			}
		}

		return $created;
	}

	/**
	 * Garante categorias + catálogo mínimo antes de telas que dependem do grid.
	 */
	public function ensureLicenciamentoBase(int $userId = 0): void {
		$this->ensureCategoriasPadrao($userId);
		$this->ensureCatalogoInicial($userId);
	}

	/**
	 * @return array<int,array{codigo:string,nome:string}>
	 */
	protected function categoriasPadraoDefinicao(): array {
		return [
			['codigo' => 'SO', 'nome' => 'Sistemas Operacionais'],
			['codigo' => 'OFFICE', 'nome' => 'Office & Produtividade'],
			['codigo' => 'DESIGN', 'nome' => 'Design & Engenharia'],
			['codigo' => 'SEGURANCA', 'nome' => 'Segurança'],
			['codigo' => 'CLOUD', 'nome' => 'Cloud & Marketing'],
			['codigo' => 'COMUNICACAO', 'nome' => 'Comunicação'],
			['codigo' => 'DEVOPS', 'nome' => 'DevOps'],
			['codigo' => 'OUTRO', 'nome' => 'Outros'],
		];
	}

	/**
	 * @return array<int,array{categoria_codigo:string,sku:string,nome:string}>
	 */
	protected function catalogoInicialDefinicao(): array {
		return [
			['categoria_codigo' => 'OFFICE', 'sku' => 'M365-BASIC', 'nome' => 'Microsoft 365 Business Basic'],
			['categoria_codigo' => 'OFFICE', 'sku' => 'M365-STANDARD', 'nome' => 'Microsoft 365 Business Standard'],
			['categoria_codigo' => 'OFFICE', 'sku' => 'M365-PREMIUM', 'nome' => 'Microsoft 365 Business Premium'],
			['categoria_codigo' => 'OFFICE', 'sku' => 'M365-E3', 'nome' => 'Microsoft 365 E3'],
			['categoria_codigo' => 'OFFICE', 'sku' => 'M365-E5', 'nome' => 'Microsoft 365 E5'],
			['categoria_codigo' => 'SO', 'sku' => 'WIN11-PRO', 'nome' => 'Windows 11 Pro'],
			['categoria_codigo' => 'SO', 'sku' => 'WIN-SRV-2022', 'nome' => 'Windows Server 2022'],
			['categoria_codigo' => 'DESIGN', 'sku' => 'ACAD-LT', 'nome' => 'AutoCAD LT'],
			['categoria_codigo' => 'DESIGN', 'sku' => 'ADOBE-CC', 'nome' => 'Adobe Creative Cloud'],
			['categoria_codigo' => 'SEGURANCA', 'sku' => 'KASP-EP', 'nome' => 'Kaspersky Endpoint'],
			['categoria_codigo' => 'CLOUD', 'sku' => 'AZURE-SUB', 'nome' => 'Microsoft Azure Subscription'],
			['categoria_codigo' => 'COMUNICACAO', 'sku' => 'TEAMS-PHONE', 'nome' => 'Microsoft Teams Phone'],
			['categoria_codigo' => 'DEVOPS', 'sku' => 'GITHUB-ENT', 'nome' => 'GitHub Enterprise'],
			['categoria_codigo' => 'OUTRO', 'sku' => 'GEN-SW', 'nome' => 'Software genérico'],
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCategorias(): array {
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
	 * Critério ORM: clientes cadastrados como fornecedores (mesmo módulo Fornecedores do ERP).
	 *
	 * @return array<string,mixed>
	 */
	protected function fornecedoresCadastroWhere(): array {
		$tbl = $this->table('Clientes');
		$cols = $tbl !== null && \App\Utility\ClientesPapelCadastro::columnsAvailable($tbl);
		$where = \App\Utility\ClientesPapelCadastro::whereFornecedor($this->idempresa, $cols);
		if ($tbl !== null && !$cols) {
			if ($tbl->hasField('fornecedor')) {
				$where['Clientes.fornecedor'] = 1;
			} elseif ($tbl->hasField('is_fornecedor')) {
				$where['Clientes.is_fornecedor'] = 1;
			}
		}

		return $where;
	}

	/**
	 * @return array<int,\Cake\Datasource\EntityInterface>
	 */
	protected function fetchFornecedoresCadastroEntities(int $limit = 200): array {
		if ($this->idempresa <= 0 || $this->table('Clientes') === null) {
			return [];
		}
		$out = [];
		try {
			foreach ($this->table('Clientes')->find()
				->where($this->fornecedoresCadastroWhere())
				->order(['Clientes.razaosocial' => 'ASC', 'Clientes.nome' => 'ASC'])
				->limit($limit)
				->all() as $c) {
				$out[(int)$c->get('id')] = $c;
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * Opções para selects (wizard, catálogo) — somente fornecedores cadastrados.
	 *
	 * @return array<int,array{id:int,nome:string,cnpj:string}>
	 */
	public function listFornecedoresOptions(int $limit = 200, ?int $idcategoria = null): array {
		$idsFiltro = null;
		if ($idcategoria !== null && $idcategoria > 0) {
			$idsCat = $this->fornecedorIdsFromCatalogoCategoria($idcategoria);
			if ($idsCat !== []) {
				$idsFiltro = $idsCat;
			}
		}
		$out = [];
		foreach ($this->fetchFornecedoresCadastroEntities($limit) as $id => $c) {
			if ($idsFiltro !== null && !in_array($id, $idsFiltro, true)) {
				continue;
			}
			$out[] = [
				'id' => $id,
				'nome' => $this->clienteNomeFromEntity($c),
				'cnpj' => (string)($c->get('cnpj') ?? ''),
			];
		}

		return $out;
	}

	/**
	 * IDs de clientes-fornecedor vinculados a produtos do catálogo na categoria.
	 *
	 * @return array<int,int>
	 */
	protected function fornecedorIdsFromCatalogoCategoria(int $idcategoria): array {
		if ($idcategoria <= 0 || $this->table('LicCatalogoProdutos') === null) {
			return [];
		}
		$ids = [];
		try {
			foreach ($this->table('LicCatalogoProdutos')->find()
				->select(['idfornecedor_cliente'])
				->where([
					'idempresa' => $this->idempresa,
					'idcategoria' => $idcategoria,
					'ativo' => true,
					'idfornecedor_cliente >' => 0,
				])
				->all() as $p) {
				$fid = (int)$p->get('idfornecedor_cliente');
				if ($fid > 0) {
					$ids[$fid] = $fid;
				}
			}
		} catch (\Throwable $e) {
		}

		return array_values($ids);
	}

	/**
	 * Mapa categoria → fornecedores (passo 1 do wizard).
	 *
	 * @return array<int,array<int,array{id:int,nome:string,cnpj:string}>>
	 */
	public function listFornecedoresPorCategoriaMap(): array {
		$map = [];
		foreach ($this->listCategoriasComContagem() as $cat) {
			$map[(int)$cat['id']] = $this->listFornecedoresOptions(200, (int)$cat['id']);
		}

		return $map;
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
		$fornecedores = $this->fetchFornecedoresCadastroEntities($limit);
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
