<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Mailer\Email;
use Cake\View\View;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class OrcamentosController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Orcamentos');
		$this->loadModel('Orcamentosmovs');
		$this->loadModel('Orcamentosservicos');
		$this->loadModel('Orcamentositens');
		$this->loadModel('Empresas');
		$this->loadModel('Produtos');
		$this->loadModel('Ordensservico');
		$this->loadModel('Areas');
		$this->loadModel('Problemas');
		$this->loadModel('Itensordem');
		$this->loadModel('Ordemservicositens');
		$this->loadModel('Config');
		$this->loadModel('Tickets');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		if($this->Auth->user('role') == 1 && !$this->Auth->user('permissaoacesso')) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
			$orcamento = $this->Orcamentos->get($event->_subject->request->params['pass'][0])->idcliente;
			$cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;
			
			if ($orcamento != $cliente) {
				$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		}

		$this->set('title', 'Orçamentos');
		$this->Auth->allow(['viewhash', 'carrinhoedit', 'aprovarhash']);
	}

	public function criarMov($idorcamento = null, $sitantiga = null, $sitnova = null, $observacao = null, $idempresa = null) {
		$mov = $this->Orcamentosmovs->newEntity();
		$mov->idorcamento = $idorcamento;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->idusuario = empty($this->Auth->user('id')) ? 0 : $this->Auth->user('id');
		$mov->idempresa = !empty($idempresa) ? $idempresa : $this->Auth->user('idempresa');
		$mov->datetime = date('d/m/Y H:i:s', time());

		if (!empty($observacao)) $mov->observacao = $observacao;

		return $this->Orcamentosmovs->save($mov);
	}

	/**
	 * Soma itens do orçamento (Orcamentosservicos ligados via Orcamentositens.iditem).
	 *
	 * @param int|string $idempresa
	 * @param int[]      $orcamentoIds
	 * @return array<int,float> id orçamento => total
	 */
	protected function _valorTotaisPorOrcamentoIds($idempresa, array $orcamentoIds) {
		$orcamentoIds = array_values(array_unique(array_filter(array_map('intval', $orcamentoIds))));
		$out = [];
		foreach ($orcamentoIds as $oid) {
			$out[$oid] = 0.0;
		}
		if ($orcamentoIds === []) {
			return $out;
		}
		$rows = $this->Orcamentositens->find()
			->select(['idorcamento', 'iditem'])
			->where(['idempresa' => $idempresa, 'idorcamento IN' => $orcamentoIds])
			->order(['id' => 'ASC'])
			->toArray();
		$orcToItem = [];
		foreach ($rows as $row) {
			$oid = (int)$row->idorcamento;
			if (!isset($orcToItem[$oid]) && $row->iditem !== null && $row->iditem !== '') {
				$orcToItem[$oid] = (int)$row->iditem;
			}
		}
		$itemIds = array_values(array_unique(array_filter($orcToItem)));
		if ($itemIds === []) {
			return $out;
		}
		$servicos = $this->Orcamentosservicos->find()
			->where(['idempresa' => $idempresa, 'idorcamento IN' => $itemIds])
			->toArray();
		$sumByItem = [];
		foreach ($servicos as $s) {
			$bid = (int)$s->idorcamento;
			if (!isset($sumByItem[$bid])) {
				$sumByItem[$bid] = 0.0;
			}
			$vm = (float)($s->valormensal ?? 0);
			$vd = (float)($s->valordoservico ?? 0);
			$sumByItem[$bid] += ($vm > 0 ? $vm : $vd);
		}
		foreach ($orcToItem as $oid => $iid) {
			$out[$oid] = $sumByItem[$iid] ?? 0.0;
		}

		return $out;
	}

	/**
	 * Rótulo de versão para lista (ex.: v1): 1 + quantidade de registros em orcamentosnovosdesmovs.
	 * Não existe coluna dedicada de revisão no legado; movimentações aproximam “ciclos” do orçamento.
	 *
	 * @param int|string $idempresa
	 * @param int[]      $orcamentoIds
	 * @return array<int,string> id orçamento => "vN"
	 */
	protected function _versaoRotuloPorOrcamentoIds($idempresa, array $orcamentoIds): array {
		$orcamentoIds = array_values(array_unique(array_filter(array_map('intval', $orcamentoIds))));
		$out = [];
		foreach ($orcamentoIds as $oid) {
			$out[$oid] = 'v1';
		}
		if ($orcamentoIds === []) {
			return $out;
		}
		$movRows = $this->Orcamentosmovs->find()
			->select(['idorcamento'])
			->where(['idempresa' => $idempresa, 'idorcamento IN' => $orcamentoIds])
			->enableHydration(false)
			->toArray();
		$cntByOrc = [];
		foreach ($movRows as $row) {
			$oid = (int)($row['idorcamento'] ?? 0);
			if ($oid > 0) {
				$cntByOrc[$oid] = ($cntByOrc[$oid] ?? 0) + 1;
			}
		}
		foreach ($cntByOrc as $oid => $c) {
			$out[$oid] = 'v' . max(1, 1 + (int)$c);
		}

		return $out;
	}

	/**
	 * Soma custo estimado por orçamento (mesmo vínculo Orcamentositens → carrinho → Orcamentosservicos).
	 * Usa coluna de custo na linha do serviço, se existir; senão custo unitário do produto × quantidade.
	 *
	 * @param int|string $idempresa
	 * @param int[]      $orcamentoIds
	 * @return array<int,float>
	 */
	protected function _custoTotaisPorOrcamentoIds($idempresa, array $orcamentoIds): array {
		$orcamentoIds = array_values(array_unique(array_filter(array_map('intval', $orcamentoIds))));
		$out = [];
		foreach ($orcamentoIds as $oid) {
			$out[$oid] = 0.0;
		}
		if ($orcamentoIds === []) {
			return $out;
		}

		$svcSchema = $this->Orcamentosservicos->getSchema();
		$lineCostCol = null;
		foreach (['valorcusto', 'valor_custo', 'custototal', 'valorcustomo', 'custo', 'precocusto'] as $col) {
			if ($svcSchema->hasColumn($col)) {
				$lineCostCol = $col;
				break;
			}
		}

		$prodSchema = $this->Produtos->getSchema();
		$prodCostCol = null;
		foreach (['vlcusto', 'vlcustounitario', 'precocusto', 'preco_custo', 'custounitario', 'custo'] as $col) {
			if ($prodSchema->hasColumn($col)) {
				$prodCostCol = $col;
				break;
			}
		}

		$rows = $this->Orcamentositens->find()
			->select(['idorcamento', 'iditem'])
			->where(['idempresa' => $idempresa, 'idorcamento IN' => $orcamentoIds])
			->order(['id' => 'ASC'])
			->toArray();
		$orcToItem = [];
		foreach ($rows as $row) {
			$oid = (int)$row->idorcamento;
			if (!isset($orcToItem[$oid]) && $row->iditem !== null && $row->iditem !== '') {
				$orcToItem[$oid] = (int)$row->iditem;
			}
		}
		$itemIds = array_values(array_unique(array_filter($orcToItem)));
		if ($itemIds === []) {
			return $out;
		}

		$servicos = $this->Orcamentosservicos->find()
			->where(['idempresa' => $idempresa, 'idorcamento IN' => $itemIds])
			->toArray();

		$produtoIds = [];
		foreach ($servicos as $s) {
			if (!empty($s->idproduto)) {
				$produtoIds[(int)$s->idproduto] = true;
			}
		}
		$custoUnitPorProdutoId = [];
		if ($prodCostCol !== null && $produtoIds !== []) {
			foreach ($this->Produtos->find()
				->where(['idempresa' => $idempresa, 'id IN' => array_keys($produtoIds)])
				->all() as $p) {
				$custoUnitPorProdutoId[(int)$p->id] = (float)($p->{$prodCostCol} ?? 0);
			}
		}

		$sumCostByItem = [];
		foreach ($servicos as $s) {
			$bid = (int)$s->idorcamento;
			if (!isset($sumCostByItem[$bid])) {
				$sumCostByItem[$bid] = 0.0;
			}
			$lineCost = 0.0;
			if ($lineCostCol !== null) {
				$lineCost = (float)($s->{$lineCostCol} ?? 0);
			} elseif ($prodCostCol !== null && !empty($s->idproduto)) {
				$pu = $custoUnitPorProdutoId[(int)$s->idproduto] ?? 0.0;
				$lineCost = $pu * $this->_quantidadeServicoParaCalculo($s->quantidade ?? 1);
			}
			$sumCostByItem[$bid] += $lineCost;
		}

		foreach ($orcToItem as $oid => $iid) {
			$out[$oid] = $sumCostByItem[$iid] ?? 0.0;
		}

		return $out;
	}

	/**
	 * @param mixed $quantidade Valor bruto do item (número ou texto tipo hora "1:30").
	 */
	protected function _quantidadeServicoParaCalculo($quantidade): float {
		if (is_numeric($quantidade)) {
			$q = (float)$quantidade;

			return $q > 0 ? $q : 1.0;
		}
		$str = trim((string)$quantidade);
		if ($str === '') {
			return 1.0;
		}
		if (strpos($str, ':') !== false) {
			$parts = explode(':', $str);
			$h = (float)str_replace(',', '.', preg_replace('/[^\d.,-]/', '', (string)($parts[0] ?? '0')));
			$m = (float)str_replace(',', '.', preg_replace('/[^\d.,-]/', '', (string)($parts[1] ?? '0')));
			$q = $h + ($m / 60.0);

			return $q > 0 ? $q : 1.0;
		}
		$q = (float)str_replace(',', '.', preg_replace('/[^\d.,-]/', '', $str));

		return $q > 0 ? $q : 1.0;
	}

	/**
	 * Margem bruta % por orçamento (venda e custo > 0); senão null (UI mostra —).
	 *
	 * @param array<int,float> $valorVendaPorId
	 * @return array<int,int|null>
	 */
	protected function _margemBrutaPctPorOrcamentoIds($idempresa, array $orcamentoIds, array $valorVendaPorId): array {
		$orcamentoIds = array_values(array_unique(array_filter(array_map('intval', $orcamentoIds))));
		$custos = $this->_custoTotaisPorOrcamentoIds($idempresa, $orcamentoIds);
		$out = [];
		foreach ($orcamentoIds as $oid) {
			$oid = (int)$oid;
			$v = (float)($valorVendaPorId[$oid] ?? 0);
			$c = (float)($custos[$oid] ?? 0);
			if ($v <= 0 || $c <= 0) {
				$out[$oid] = null;
			} else {
				$out[$oid] = (int)round((($v - $c) / $v) * 100);
			}
		}

		return $out;
	}

	/**
	 * Detecta driver PostgreSQL na conexão de Orcamentositens.
	 */
	protected function _isPostgresOrcamentositensConnection(): bool {
		$conn = $this->Orcamentositens->getConnection();
		$cfg = $conn->config();
		if (!empty($cfg['driver']) && stripos((string)$cfg['driver'], 'Postgres') !== false) {
			return true;
		}
		$driver = $conn->getDriver();

		return is_object($driver) && stripos(get_class($driver), 'Postgres') !== false;
	}

	/**
	 * Indica violação de unicidade na PK de orcamentosnovositens (sequence atrasada, import, etc.).
	 */
	protected function _isOrcamentositensPkDuplicateException(\Throwable $e): bool {
		$msg = $e->getMessage();
		if (stripos($msg, '23505') === false && stripos($msg, 'Unique violation') === false) {
			return false;
		}

		return stripos($msg, 'orcamentosnovositens') !== false;
	}

	/**
	 * Realinha a sequence da coluna id com MAX(id) (PostgreSQL).
	 *
	 * @return bool true se a sequence existir e o comando tiver sucesso
	 */
	protected function _resyncOrcamentositensIdSequence(): bool {
		if (!$this->_isPostgresOrcamentositensConnection()) {
			return false;
		}
		$conn = $this->Orcamentositens->getConnection();
		try {
			$seqRow = $conn->execute(
				"SELECT pg_get_serial_sequence('orcamentosnovositens', 'id') AS s"
			)->fetch('assoc');
			if (empty($seqRow['s'])) {
				return false;
			}
			$maxRow = $conn->execute('SELECT COALESCE(MAX(id), 0) AS m FROM orcamentosnovositens')->fetch('assoc');
			$max = (int)($maxRow['m'] ?? 0);
			if ($max <= 0) {
				$conn->execute('SELECT setval(pg_get_serial_sequence(\'orcamentosnovositens\', \'id\'), 1, false)');
			} else {
				$conn->execute(
					'SELECT setval(pg_get_serial_sequence(\'orcamentosnovositens\', \'id\'), ?, true)',
					[$max]
				);
			}

			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Insere a linha de vínculo carrinho ↔ orçamento; em 23505 tenta corrigir a sequence e repetir uma vez.
	 *
	 * @param int|string $iditem
	 * @return bool
	 */
	protected function _saveOrcamentositensNovoOrcamento($iditem, $idorcamento, $idempresa): bool {
		$data = [
			'iditem' => $iditem,
			'idorcamento' => $idorcamento,
			'idempresa' => $idempresa,
		];
		$carrinho = $this->Orcamentositens->newEntity($data, ['validate' => false]);
		try {
			return (bool)$this->Orcamentositens->save($carrinho);
		} catch (\Throwable $e) {
			if ($this->_isOrcamentositensPkDuplicateException($e) && $this->_resyncOrcamentositensIdSequence()) {
				$carrinho = $this->Orcamentositens->newEntity($data, ['validate' => false]);
				try {
					return (bool)$this->Orcamentositens->save($carrinho);
				} catch (\Throwable $e2) {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Quantidade em linha de orçamento (número ou formato hora "H:M" como no JS legado).
	 */
	protected function _parseQuantidadeOrcamentoLinha($quantidade): float {
		$str = trim((string)$quantidade);
		if ($str === '') {
			return 0.0;
		}
		if (strpos($str, ':') !== false) {
			$parts = explode(':', $str);
			$h = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', (string)($parts[0] ?? '0')));
			$m = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', (string)($parts[1] ?? '0')));

			return $h + ($m / 6 / 10);
		}

		return (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', $str));
	}

	/**
	 * Custo e margem por linha do carrinho (custo unitário do cadastro de produtos × qtde).
	 *
	 * @param \Cake\Datasource\EntityInterface[] $carrinho
	 * @return array<int,array{custoLinha:float,margemPct:int|null}>
	 */
	protected function _carrinhoLinhasCustoMargem(array $carrinho, $idempresa): array {
		$schema = $this->Produtos->getSchema();
		$costCol = null;
		foreach (['vlcusto', 'precocusto', 'vlcustounitario', 'custo'] as $c) {
			if ($schema->hasColumn($c)) {
				$costCol = $c;
				break;
			}
		}
		$byId = [];
		$byCodigo = [];
		if ($costCol) {
			foreach ($this->Produtos->find()
				->select(['id', 'codigo', $costCol])
				->where(['idempresa' => $idempresa])
				->enableHydration(false) as $p) {
				$byId[(int)$p['id']] = (float)($p[$costCol] ?? 0);
				$byCodigo[trim((string)$p['codigo'])] = (float)($p[$costCol] ?? 0);
			}
		}
		$out = [];
		foreach ($carrinho as $reg) {
			$rid = (int)$reg->id;
			$cu = 0.0;
			$idp = $reg->idproduto ?? null;
			if ($idp !== null && $idp !== '' && (string)$idp !== '0') {
				if (is_numeric($idp) && isset($byId[(int)$idp])) {
					$cu = $byId[(int)$idp];
				}
				$key = trim((string)$idp);
				if ($cu <= 0 && $key !== '' && isset($byCodigo[$key])) {
					$cu = $byCodigo[$key];
				}
			}
			$q = $this->_parseQuantidadeOrcamentoLinha($reg->quantidade ?? 0);
			$custoLinha = $cu * $q;
			$venda = 0.0;
			if ((float)($reg->valormensal ?? 0) > 0) {
				$venda = (float)$reg->valormensal;
			} else {
				$venda = (float)($reg->valordoservico ?? 0);
			}
			$margemPct = null;
			if ($venda > 0.0001) {
				$margemPct = (int)round((($venda - $custoLinha) / $venda) * 100);
			}
			$out[$rid] = ['custoLinha' => $custoLinha, 'margemPct' => $margemPct];
		}

		return $out;
	}

	/**
	 * Opções de “Pagamento” na proposta (gravadas em orcamentosnovosdes.formapagamento).
	 *
	 * @return array<string,string> valor => rótulo
	 */
	protected function _orcFormaPagamentoOpcoes(): array {
		return [
			'À vista' => 'À vista',
			'Parcelado' => 'Parcelado',
			'Boleto 30/60/90' => 'Boleto 30/60/90',
			'1+5 parcelas boleto' => '1+5 parcelas boleto',
		];
	}

	public function index() {
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$orcamentosCliente = $this->Orcamentos->find('all', ['contain' => 'Users'
			])->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idcliente' => $idcliente])
		->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosPendentes = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 0]
		])->where(['Orcamentos.idempresa' => $idempresa])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosEnviados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 1]
		])->where(['Orcamentos.idempresa' => $idempresa])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosAprovados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 2]
		])->where(['Orcamentos.idempresa' => $idempresa])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosRecusados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 3]
		])->where(['Orcamentos.idempresa' => $idempresa])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosArquivados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 4]
		])->where(['Orcamentos.idempresa' => $idempresa])->order(['Orcamentos.id DESC'])->toArray();

		// Contagens por status (BD legada: inteiros + UserConstants; não usar strings tipo 'enviado').
		$statusEmAndamento = [C_OrcamentoStatusPendente];
		if (defined('C_OrcamentoStatusRascunho')) {
			$statusEmAndamento[] = constant('C_OrcamentoStatusRascunho');
		}
		$totais = [
			'em_andamento' => $this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.status IN' => $statusEmAndamento])
				->count(),
			'enviados' => $this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.status' => C_OrcamentoStatusEnviado])
				->count(),
			'aprovados' => $this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.status' => C_OrcamentoStatusAprovado])
				->count(),
			'recusados' => $this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.status' => C_OrcamentoStatusRecusado])
				->count(),
			'arquivados' => $this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.status' => C_OrcamentoStatusArquivado])
				->count(),
		];

		$allIds = [];
		foreach ([$orcamentosCliente, $orcamentosPendentes, $orcamentosEnviados, $orcamentosAprovados, $orcamentosRecusados, $orcamentosArquivados] as $lista) {
			foreach ($lista as $o) {
				$allIds[(int)$o->id] = true;
			}
		}
		$valorTotalPorOrcamentoId = $this->_valorTotaisPorOrcamentoIds($idempresa, array_keys($allIds));
		$versaoRotuloPorOrcamentoId = $this->_versaoRotuloPorOrcamentoIds($idempresa, array_keys($allIds));
		$margemBrutaPctPorOrcamentoId = $this->_margemBrutaPctPorOrcamentoIds($idempresa, array_keys($allIds), $valorTotalPorOrcamentoId);

		$orcamentos = array_merge(
			$orcamentosPendentes,
			$orcamentosEnviados,
			$orcamentosAprovados,
			$orcamentosRecusados,
			$orcamentosArquivados
		);

		$this->set('orcamentosCliente', $orcamentosCliente);
		$this->set('orcamentosPendentes', $orcamentosPendentes);
		$this->set('orcamentosEnviados', $orcamentosEnviados);
		$this->set('orcamentosAprovados', $orcamentosAprovados);
		$this->set('orcamentosRecusados', $orcamentosRecusados);
		$this->set('orcamentosArquivados', $orcamentosArquivados);
		$this->set(compact(
			'totais',
			'orcamentos',
			'valorTotalPorOrcamentoId',
			'versaoRotuloPorOrcamentoId',
			'margemBrutaPctPorOrcamentoId'
		));
		$this->set('title', 'Orçamentos');
		$this->set('hideLayoutPageTitle', true);
	}

	public function add($idticket = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) return $this->redirect(['controller' => 'Tickets', 'action' => 'add', 4]);

		$orcamento = $this->Orcamentos->newEntity();
        $ticket = $this->Tickets->findById($idticket)->first();
		if(!empty($ticket)) $orcamento->solicitacao = $ticket->solicitacao;

		if(!isset($_SESSION['idcarrinhoadd'])) { 
			$ultimo = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa')])->order(['id ASC'])->last();
			if(empty($ultimo)) $_SESSION['idcarrinhoadd'] = 1 . $this->Auth->user('id');
			else{
				$idcarrinhoorcamento = $ultimo->id + 1 . $this->Auth->user('id');
				$_SESSION['idcarrinhoadd'] = $ultimo->id + 1 . $this->Auth->user('id');
			}
		}

		if ($this->request->is('post')) { 
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $this->request->getData());
			$orcamento->created = date("Y-m-d H:i:s");
			$orcamento->idautor = $this->Auth->user('id');
			$orcamento->id = $this->Empresas->incrementOrcamento($this->Auth->user('idempresa'));
			$orcamento->idempresa = $this->Auth->user('idempresa');
			$orcamento->hash = $orcamento->idautor . $orcamento->id . $orcamento->idempresa . sequenciaAleatoria();
			// cria status por padrao como pendente
			$orcamento->status = C_OrcamentoStatusPendente;

			if ($this->Orcamentos->save($orcamento)) {
				if(isset($idcarrinhoorcamento)) $_SESSION['idcarrinhoadd'] = $idcarrinhoorcamento;
				$savedLink = $this->_saveOrcamentositensNovoOrcamento(
					$_SESSION['idcarrinhoadd'],
					$orcamento->id,
					$orcamento->idempresa
				);
				if ($savedLink) {
					$this->limpasession();
					$this->Flash->success(__('Orçamento gerado com sucesso!'));
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
					return $this->redirect(['action' => 'edit', $orcamento->id]);
				}
				$this->Orcamentos->delete($orcamento);
				$this->Empresas->decrementOrcamento($this->Auth->user('idempresa'));
				$this->Flash->error(__('Não foi possível vincular os itens ao orçamento (erro ao gravar o carrinho). Se o problema persistir, verifique a sequence da tabela orcamentosnovositens no PostgreSQL.'));
			}
			$orcamento->id = $this->Empresas->decrementOrcamento($this->Auth->user('idempresa'));
			$this->Flash->error(__('Não foi possível gerar o orçamento.'));
		} else $this->limpacarrinho();

		if ($orcamento->get('formapagamento') === null || $orcamento->get('formapagamento') === '') {
			$orcamento->set('formapagamento', 'À vista');
		}

		// Combos 
		$clientes = $this->Clientes->find('all')
			->where(['Clientes.idempresa' => $this->Auth->user('idempresa'), 'Clientes.inativo' => 0])
			->contain(['Cidades'])
			->order(['Clientes.razaosocial'])
			->toArray();

		$clientesOpt = [];
		$clientesMeta = [];
		foreach ($clientes as $reg) {
			$nomeCliente = ($reg->tipo == C_ClientesTipoJuridica) ? $reg->razaosocial : $reg->nome;
			$nomeCidade = (!empty($reg->cidade) && !empty($reg->cidade->nome)) ? $reg->cidade->nome : 'Sem Cidade';
			$clientesOpt[$reg->id] = $nomeCliente . ' (' . $nomeCidade . ')';
			$clientesMeta[$reg->id] = [
				'tipo' => (int)$reg->tipo,
				'cnpj' => (string)($reg->cnpj ?? ''),
				'cpf' => (string)($reg->cpf ?? ''),
				'email' => (string)($reg->email ?? ''),
				'nome' => (string)($reg->nome ?? ''),
				'razaosocial' => (string)($reg->razaosocial ?? ''),
			];
		}
		asort($clientesOpt);

			$produtosOpt = [0 => 'Código'];
			$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
			foreach ($produtosOpt1 as $reg) {
				$produtosOpt[$reg->codigo] = $reg->descricao . ' (' . $reg->codigo . ')';
			}

			$produtosCatalogoLista = [];
			$tipoMap = [];
			if (defined('C_ProdutosTipo')) {
				$tc = constant('C_ProdutosTipo');
				$tipoMap = is_array($tc) ? $tc : [];
			}
			foreach ($produtosOpt1 as $reg) {
				$tipoInt = (int)($reg->tipo ?? 0);
				$tipoLabel = $tipoMap[$tipoInt] ?? 'Item';
				$badge = 'outro';
				if (defined('C_ProdutosTipoProduto') && $tipoInt === (int) C_ProdutosTipoProduto) {
					$badge = 'prod';
				} elseif (defined('C_ProdutosTipoServico') && $tipoInt === (int) C_ProdutosTipoServico) {
					$badge = 'srv';
				} elseif (stripos((string) $tipoLabel, 'licen') !== false) {
					$badge = 'lic';
				} elseif (stripos((string) $tipoLabel, 'loca') !== false) {
					$badge = 'loc';
				}
				$produtosCatalogoLista[] = [
					'id' => (string) $reg->codigo,
					'codigo' => (string) $reg->codigo,
					'descricao' => (string) ($reg->descricao ?? ''),
					'nome' => (string) ($reg->descricao ?? ''),
					'tipo' => $tipoInt,
					'tipoLabel' => $tipoLabel,
					'badge' => $badge,
					'vlunitario' => (float) ($reg->vlunitario ?? 0),
					'unidade' => (string) ($reg->unidade ?? 'un'),
				];
			}
			$this->set(
				'produtosCatalogoJson',
				json_encode($produtosCatalogoLista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE)
			);

		$this->set('idcarrinho', $_SESSION['idcarrinhoadd']);
		$this->set('clientes', $clientesOpt);
		$this->set('produtos', $produtosOpt);
		$this->set('orcamento', $orcamento);
		$this->set('clientesMetaJson', json_encode($clientesMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE));
		$this->set('title', 'Gerar Orçamento');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 1);
		$this->set('orcFormaPagamentoOpcoes', $this->_orcFormaPagamentoOpcoes());
	}
	
	public function edit($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$orcamento = $this->Orcamentos->find('all')
		->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa'), 'Orcamentos.id' => $id])
		->contain([
			'Clientes' => [
				'fields' => ['Clientes.razaosocial', 'Clientes.email', 'Clientes.nome', 'Clientes.idcidade', 'Clientes.tipo'] 
			],
			'Clientes.Cidades' 
		])
		->first();
		$carrinho = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $id]])->first();


		if(empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();
			$_SESSION['idcarrinho'] =  $this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id');
		} else { 
			$idcarrinho = $carrinho->iditem;
			$_SESSION['idcarrinho'] = $idcarrinho;
		}


		if ($this->request->is(['post', 'put'])) {
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $this->request->getData());

			if ($this->Orcamentos->save($orcamento)) {
				$this->Flash->success(__('Orçamento alterado com sucesso!'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			} else $this->Flash->error(__('Não foi possível alterar o orçamento.'));
			return $this->redirect(['action' => 'edit', $orcamento->id]);
		}

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';

		$ordem = $this->Ordensservico->findByIdorcamento($id)->first();
		$temordem = empty($ordem) ? 'nao' : $ordem->id;

		$this->set('title', 'Edição de Orçamento');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 2);
		$this->set('produtos', $produtosOpt);
		$this->set('temordem', $temordem);
		$this->set('orcamento', $orcamento);
		$this->set('idcarrinho', $_SESSION['idcarrinho']);
		$this->set('orcFormaPagamentoOpcoes', $this->_orcFormaPagamentoOpcoes());
	}

	public function view($id = null, $idempresa = null) {
		if(!empty($idempresa)) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->idempresa = $idempresa;
			$this->Auth->setUser($user);
		}

		$ordem = $this->Ordensservico->findByIdorcamento($id)->first();
		$temordem = empty($ordem) ? 'nao' : $ordem->id;
		$this->set('temordem', $temordem);

		$orcamento = $this->Orcamentos->find('all')
			->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa'), 'Orcamentos.id' => $id])
			->contain([ 'Users' => ['fields' => ['Users.name']]])
		->first();

		$idcliente = $this->Auth->user('idcliente');
		if ($this->Auth->user('role') == C_RoleCliente && $orcamento->idcliente != $idcliente) {
			$this->Flash->error('Você não possui permissão para visualizar outros orçamentos.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$carrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $id])->toArray();
		$empresaObj = $this->Empresas->get($this->Auth->user('idempresa'));

		if(empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();
			$_SESSION['idcarrinho'] = $this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id');
		} else {
			$idcarrinho = $carrinho[0]->iditem;
			$_SESSION['idcarrinho'] = $idcarrinho;
		}
	
		$this->set('title', 'Visualização de Orçamento');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 4);
		$this->set('empresaObj', $empresaObj);
		$this->set('orcamento', $orcamento);
		$this->set('idcarrinho', $_SESSION['idcarrinho']);
	}

	public function viewhash($hash = null) {
		$this->viewBuilder()->setLayout('orcamentos');
		$orcamento = $this->Orcamentos->findByHash($hash)->contain(['Users' => ['fields' => ['Users.name']], 'Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]])->first();
		if(empty($orcamento)) {
			$this->Flash->error(__('Não foi encontrado um orçamento!'));
			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}
		$carrinho = $this->Orcamentositens->find('all')->where(['idorcamento' => $orcamento->id])->first();
		$idcarrinho = $carrinho->iditem;
		$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $carrinho->iditem])->order(['id'])->toArray();
	
		$this->set('title', 'Visualização de Orçamento');
		$this->set('orcamento', $orcamento);
		$this->set('carrinho', $carrinho);
	}

	public function addservico($edit = null){
		$this->autoRender = false;
		try {
			$data = $this->request->getData();
			$idempresa = $this->Auth->user('idempresa');
			if (empty($idempresa)) {
				return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão inválida. Faça login novamente.']))->withStatus(401);
			}

			if ($edit == 'edit') {
				if (!array_key_exists('idcarrinho', $_SESSION)) {
					return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão do orçamento expirada. Recarregue a página e tente novamente.']))->withStatus(400);
				}
				$idorcamento = $_SESSION['idcarrinho'];
			} else {
				if (!array_key_exists('idcarrinhoadd', $_SESSION)) {
					return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão do orçamento expirada. Recarregue a página e tente novamente.']))->withStatus(400);
				}
				$idorcamento = $_SESSION['idcarrinhoadd'];
			}

			$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $idempresa, 'idorcamento' => $idorcamento]])->order(['id'])->toArray();
			$servico = isset($data['servico']) ? trim($data['servico']) : '';
			foreach ($carrinho as $reg) {
				if ($reg->servico == $servico) {
					$this->response = $this->response->withType('text/html')->withStringBody('nao pode');
					return $this->response;
				}
			}

			$valoruni = function_exists('formatNumber') ? formatNumber($data['valoruni'] ?? 0) : (float) str_replace([',', '.'], ['', '.'], $data['valoruni'] ?? '0');
			$valordoservico = function_exists('formatNumber') ? formatNumber($data['valordoservico'] ?? 0) : (float) str_replace([',', '.'], ['', '.'], $data['valordoservico'] ?? '0');

			// Sempre buscar preço atual do produto no BD (form envia código no select, não id)
			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$idprodutoVal = trim((string) $data['idproduto']);
				$produto = $this->Produtos->findByCodigo($idprodutoVal)->where(['idempresa' => $idempresa])->first();
				if (!$produto && is_numeric($idprodutoVal) && (int)$idprodutoVal > 0) {
					$produto = $this->Produtos->findById((int)$idprodutoVal)->where(['idempresa' => $idempresa])->first();
				}
				if ($produto) {
					$valoruni = (float) ($produto->vlunitario ?? 0);
					$valordoservico = $valoruni * (float) ($data['quantidade'] ?? 1);
				}
			}

			$orcamentond = $this->Orcamentosservicos->newEntity();
			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$orcamentond->idproduto = is_numeric($data['idproduto']) ? (int) $data['idproduto'] : 0;
			}
			$orcamentond->servico = $servico;
			$orcamentond->quantidade = $data['quantidade'] ?? 0;
			$orcamentond->observacao = isset($data['observacao']) ? $data['observacao'] : '';
			$orcamentond->valoruni = $valoruni;
			$orcamentond->tipo = isset($data['tipo']) ? $data['tipo'] : 0;
			$valormensal = isset($data['valormensal']) ? (function_exists('formatNumber') ? formatNumber($data['valormensal']) : (float) str_replace([',', '.'], ['', '.'], $data['valormensal'])) : 0;
			if ($valormensal != 0) {
				$qtd = function_exists('formatNumber') ? formatNumber($data['quantidade'] ?? 1) : (float) ($data['quantidade'] ?? 1);
				$orcamentond->valormensal = $qtd * $valormensal;
			}
			$orcamentond->valordoservico = $valordoservico;
			$orcamentond->idempresa = $idempresa;
			$orcamentond->idorcamento = $idorcamento;

			if ($this->Orcamentosservicos->save($orcamentond)) {
				$this->response = $this->response->withType('text/html')->withStringBody('boa');
				return $this->response;
			}

			$errors = $orcamentond->getErrors();
			$msg = 'Não foi possível salvar o item.';
			if (!empty($errors)) {
				$first = reset($errors);
				$msg = is_array($first) ? implode(' ', reset($first)) : (string) $first;
			}
			return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => $msg]))->withStatus(422);
		} catch (\Throwable $e) {
			$this->log('Orcamentos::addservico ' . $e->getMessage(), 'error');
			return $this->response->withType('application/json')->withStringBody(json_encode([
				'mensagem' => 'Erro ao adicionar item. Tente novamente.',
				'detalhe' => $e->getMessage()
			]))->withStatus(500);
		}
	}

	public function carrinho($idorcamento = null){
		if($idorcamento == null) $idorcamento = $_SESSION['idcarrinhoadd'];
		$idempresa = $this->Auth->user('idempresa');
		$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $idempresa, 'idorcamento' => $idorcamento]])->order(['id'])->toArray();
		$carrinhoLinhasExtra = $this->_carrinhoLinhasCustoMargem($carrinho, $idempresa);

		$this->set('carrinho', $carrinho);
		$this->set('idorcamento', $idorcamento);
		$this->set('carrinhoLinhasExtra', $carrinhoLinhasExtra);
	}
	
	public function carrinhoedit($idorcamento = null){
		$orcamento = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'id' => $idorcamento])->first();
		$idcarrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento])->first();
		$idorcamentoServicos = $idcarrinho ? $idcarrinho->iditem : (isset($_SESSION['idcarrinho']) ? $_SESSION['idcarrinho'] : null);
		$carrinho = $idorcamentoServicos
			? $this->Orcamentosservicos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamentoServicos])->order(['id'])->toArray()
			: [];

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] = "$reg->descricao ($reg->codigo)";

		$this->set('produtos', $produtosOpt);
		$this->set('carrinho', $carrinho);
		$this->set('orcamento', $orcamento);
	}

	public function limpacarrinho(){
		if ($this->request->is(['ajax'])) $this->autoRender = false;
		if(isset($_SESSION['idcarrinhoadd']) && !empty($_SESSION['idcarrinhoadd'])) {
			$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $_SESSION['idcarrinhoadd']])->toArray();
			foreach($carrinho as $item) $this->Orcamentosservicos->delete($item);
		}
	}
	
	public function excluiitemcarrinho($id) {
		$this->autoRender = false;
		$item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		$this->Orcamentosservicos->delete($item);
	}

	public function getitemcarrinho($id) {
		$this->autoRender = false;
		$item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		return $this->jsonResponse($item, 200);
	}

	public function edititemcarrinho() {
        $this->autoRender = false;
        $data = $this->request->getData();
        $item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $data['iditem']]])->first();
        $item = $this->Orcamentosservicos->patchEntity($item, $data);
        $vlMensal = isset($data['valormensal']) ? formatNumber($data['valormensal']) : 0;

        if ($vlMensal > 0) {
            $item->valormensal = $vlMensal;            
            $item->valoruni = 0;
            $item->valordoservico = 0;
        } else {
            if(isset($data['valoruni'])) $item->valoruni = formatNumber($data['valoruni']);
            if(isset($data['valordoservico'])) $item->valordoservico = formatNumber($data['valordoservico']);
            
            $item->valormensal = 0; 
        }
        if( $this->Orcamentosservicos->save($item) ){
            $this->Flash->success(__('Item alterado com sucesso!'));
            $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $item->id);
            return $this->redirect(['action' => 'edit', $data['idorcamentofind']]);
        }else $this->Flash->error(__('Não foi possível alterar o item!'));
    }
	
	public function limpasession() {
		$this->autoRender = false;
		unset($_SESSION['idcarrinho']);
		unset($_SESSION['idcarrinhoadd']);
	}
	
	/**
	 * Carrega orçamento, marca pdfgerado, monta carrinho (mesma regra que imprimir / PDF).
	 *
	 * @param int|string|null $id ID do orçamento
	 * @return bool false se não encontrado
	 */
	protected function _prepareImprimirViewVars($id) {
		$orcamento = $this->Orcamentos->find('all', [
			'contain' => ['Users', 'Clientes.Cidades']
		])->where(['AND' => ['orcamentos.idempresa' => $this->Auth->user('idempresa'), 'orcamentos.id' => $id]])->first();

		if ($orcamento === null) {
			return false;
		}

		$orcamento->pdfgerado = 1;
		$this->Orcamentos->save($orcamento);

		$idprapesquisa = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $orcamento->id]])->toArray();

		$carrinho = [];
		$carrinhoMensal = [];
		if (!empty($idprapesquisa)) {
			$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idprapesquisa[0]->iditem]])->order(['id'])->toArray();
			foreach ($carrinho as $reg) {
				if ($reg->valormensal != null) {
					$carrinhoMensal[] = $reg;
				}
			}
		}

		$this->set('carrinho', $carrinho);
		if (!empty($carrinhoMensal)) {
			$this->set('carrinhoMensal', $carrinhoMensal);
		}
		$this->set('idorcamento', $id);
		$this->set('orcamento', $orcamento);

		return true;
	}

	public function imprimir($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		if (!$this->_prepareImprimirViewVars($id)) {
			$this->Flash->error('Orçamento não encontrado.');
			return $this->redirect(['action' => 'index']);
		}

		$vid = (int)$id;
		$versaoMap = $this->_versaoRotuloPorOrcamentoIds($this->Auth->user('idempresa'), [$vid]);
		$this->set('orcVersaoLabel', $versaoMap[$vid] ?? 'v1');
		try {
			$empresaPdf = $this->Empresas->get($this->Auth->user('idempresa'), [
				'contain' => ['Cidades' => ['Estados']],
			]);
		} catch (\Throwable $e) {
			$empresaPdf = null;
		}
		$this->set('empresaPdf', $empresaPdf);

		$this->set('title', 'Pré-visualização PDF');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 5);
	}

	/**
	 * PDF gerado no servidor (mPDF). Requer: composer require mpdf/mpdf
	 */
	public function imprimirPdf($id = null) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error('Biblioteca mPDF não instalada. No servidor, execute: composer require mpdf/mpdf');
			return $this->redirect(['action' => 'imprimir', $id]);
		}

		if (!$this->_prepareImprimirViewVars($id)) {
			$this->Flash->error('Orçamento não encontrado.');
			return $this->redirect(['action' => 'index']);
		}

		$this->autoRender = false;
		$pdf = $this->_renderOrcamentoPdfHtmlToPdf();

		$filename = 'Orcamento-' . (int)$id . '.pdf';
		$this->response = $this->response
			->withType('application/pdf')
			->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
			->withStringBody($pdf);

		return $this->response;
	}

	/**
	 * Rota do PROMPT 7: /orcamentos/{id}/pdf — mesmo fluxo que imprimirPdf (mPDF, não TCPDF).
	 */
	public function pdf($id = null) {
		return $this->imprimirPdf($id);
	}

	/**
	 * Catálogo JSON (PROMPT 3/4): GET ?q= ou ?busca= — produtos ativos da empresa.
	 * Schema legado: nome ← descricao; preco_custo/estoque não existem na tabela → 0 / null.
	 */
	public function catalogo() {
		if ((int)$this->Auth->user('role') === 1) {
			$this->autoRender = false;

			return $this->response
				->withType('application/json')
				->withStringBody(json_encode(['error' => 'Forbidden']))
				->withStatus(403);
		}

		$this->request->allowMethod(['get']);
		$this->autoRender = false;

		$q = trim((string)$this->request->getQuery('q', ''));
		$busca = trim((string)$this->request->getQuery('busca', $q));

		$query = $this->Produtos->find('all')
			->select(['id', 'codigo', 'descricao', 'tipo', 'vlunitario', 'ativo'])
			->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])
			->order(['descricao' => 'ASC'])
			->limit(80);

		if ($busca !== '') {
			$query->where([
				'OR' => [
					'codigo LIKE' => '%' . $busca . '%',
					'descricao LIKE' => '%' . $busca . '%',
				],
			]);
		}

		$out = [];
		foreach ($query->toArray() as $p) {
			$pv = (float)($p->vlunitario ?? 0);
			$out[] = [
				'id' => (int)$p->id,
				'codigo' => (string)$p->codigo,
				'nome' => (string)$p->descricao,
				'tipo' => (int)$p->tipo,
				'preco_venda' => $pv,
				'preco_custo' => 0.0,
				'estoque' => null,
				'margem' => 0.0,
			];
		}

		return $this->response
			->withType('application/json')
			->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
	}

	protected function _renderOrcamentoPdfHtmlToPdf() {
		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}

		$view = new View($this->request, $this->response, $this->getEventManager(), [
			'layout' => false,
		]);
		$view->setTemplatePath('Orcamentos');
		$view->set($this->viewVars);
		$html = $view->render('imprimir_pdf');

		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4',
			'tempDir' => $tmpDir,
		]);
		$mpdf->WriteHTML($html);

		return $mpdf->Output('', 'S');
	}
	
	public function aprovar($id = null) {
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		if($orcamento->status != C_OrcamentoStatusPendente && $orcamento->status != C_OrcamentoStatusEnviado){
			$this->Flash->error('Você não possui permissão para aprovar este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];
			
			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusAprovado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusAprovado, $observacao);
				$this->Flash->success('O orçamento foi aprovado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Aprovar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function aprovarhash($hash){
		$orcamento = $this->Orcamentos->findByHash($hash)->first();
		$sitantiga = $orcamento->status;
		$observacao = "O orçamento foi aprovado pelo cliente.";
		$orcamento->ipaprovacao = get_client_ip();
		$orcamento->navegadoraprovacao = VerificaNavegadorSO();
		$orcamento->status = C_OrcamentoStatusAprovado;

		if ($this->Orcamentos->save($orcamento)) {
			$this->Flash->success('O orçamento foi aprovado com sucesso!');
			$this->criarMov($orcamento->id, $sitantiga, C_OrcamentoStatusAprovado, $observacao, $orcamento->idempresa);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			// Email
				$empresa = $this->Empresas->get($orcamento->idempresa);
				// Mensagem 
					$url = $this->Config->get(1)->urlfora.'orcamentos/edit/'.$orcamento->id;
					
					$assunto = "Orçamento $orcamento->id aprovado!";
					$mensagem = "
						<h3> O orçamento $orcamento->id foi aprovado pelo cliente! </h3>
						<p> Para acessá-lo, <a href='$url'>clique aqui!</a> </p>
						<p> IP: $orcamento->ipaprovacao </p>
						<p> Navegador: $orcamento->navegadoraprovacao </p>
					";
				// Assinautra 
					if(empty($this->Auth->user('assinaturapgm'))) $message = $mensagem . '<hr> PGM' ;
					else {
						$img = '<img src="'.$this->Auth->user('assinaturapgm') .'" alt=""/>';
						$message = $mensagem . '<hr>' . $img;
					}
				// Empresa 
					if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
					else $nomeempresa = $empresa->razaosocial;
				//
				$email = new Email();
				$email->transport(((int)$orcamento->idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm');
				$from = 'helpdesk@pgm.inf.br';
				$email->from([$from => $nomeempresa]);
				$email->to($this->Config->get(1)->emailtickets)
				// $email->to('joaomario3224@gmail.com')
					->emailFormat('html')
					->subject($assunto);
	
				if($email->send($message)) $this->enviar($orcamento->id);
			//

		}
		else $this->Flash->error('Ocorreu um erro ao aprovar o orçamento.');

		if(!empty($this->Auth->user('id'))) return $this->redirect(['action' => 'view', $orcamento->id]);
		else return $this->redirect(['action' => 'viewhash', $orcamento->hash]);

		$this->set('title', 'Aprovar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function recusar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();

		if($orcamento->status != C_OrcamentoStatusPendente && $orcamento->status != C_OrcamentoStatusEnviado){
			$this->Flash->error('Você não possui permissão para imprimir este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusRecusado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusRecusado, $observacao);
				$this->Flash->success('O orçamento foi recusado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Recusar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function arquivar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();

		if($orcamento->status != C_OrcamentoStatusEnviado){
            $this->Flash->error('Você não possui permissão para arquivar este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusArquivado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusArquivado, $observacao);
				$this->Flash->success('O orçamento foi arquivado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Arquivar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function enviar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];
			else $observacao = null;

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusEnviado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusEnviado, $observacao);
				$this->Flash->success('O orçamento foi enviado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Enviar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	/**
	 * Passo 6: Envio & Assinatura Digital (UI espelhada do mock pgm_orcamentos_premium.html).
	 * - GET: mostra tela.
	 * - POST: marca o orçamento como enviado e cria movimento.
	 */
	public function envioassinatura($id = null){
		// Permissão para backoffice (cliente/role 1 não acessa).
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$orcamento = $this->Orcamentos->find('all')
			->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])
			->contain(['Users', 'Clientes'])
			->first();

		if (empty($orcamento)) {
			$this->Flash->error('Orçamento não encontrado.');
			return $this->redirect(['action' => 'index']);
		}

		$envioSucesso = ((int)$this->request->getQuery('ok', 0)) === 1;
		if ($this->request->is(['post', 'put'])) {
			$oldStatus = $orcamento->status;
			$orcamento->status = C_OrcamentoStatusEnviado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $oldStatus, C_OrcamentoStatusEnviado, null);
				$envioSucesso = true;
			} else {
				$this->Flash->error('Ocorreu um erro ao enviar para assinatura.');
			}
		}

		$versaoMap = $this->_versaoRotuloPorOrcamentoIds($this->Auth->user('idempresa'), [(int)$orcamento->id]);
		$orcVersaoLabel = $versaoMap[(int)$orcamento->id] ?? 'v1';

		// Link do portal onde o cliente assina (viewhash).
		$assinarUrl = null;
		try {
			$base = $this->Config->get(1)->urlfora ?? null;
			if (!empty($base) && !empty($orcamento->hash)) {
				$assinarUrl = $base . 'orcamentos/viewhash/' . $orcamento->hash;
			}
		} catch (\Throwable $e) {
			$assinarUrl = null;
		}

		$this->set('title', 'Envio & Assinatura Digital');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 6);

		$this->set(compact('orcamento', 'orcVersaoLabel', 'assinarUrl', 'envioSucesso'));
	}

	public function alterarsituacao($id = null) {
		$orcamento = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'id' => $id])->first();

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (isset($data['motivo'])) {
				$motivo = $data['motivo'];
				$status = $data['status'];
				$sitantiga = $orcamento->status;

				if (!empty($motivo)) {
					$orcamento->status = $status;
					$orcamento->motivo = $motivo;

					if ($this->Orcamentos->save($orcamento)) {
						$this->criarMov($orcamento->id, $sitantiga, $status, $motivo);
						$this->Flash->success("Situação do orçamento alterada.");
						return $this->redirect(['action' => 'edit', $id]);
					}
				}
			}
		}
		
		$this->set('title', 'Alterar Situação');
		$this->set('orcamento', $orcamento);
	}

	public function email() {
		$this->autoRender = false;

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$isStep6 = isset($data['step6']) && (int)$data['step6'] === 1;
			$empresa = $this->Empresas->get($this->Auth->user('idempresa'));
			$orcamento = $this->Orcamentos->findById($data['idorcamento'])->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			// Link de acesso 
				$idorcamento = $data['idorcamento'];
				$url = $this->Config->get(1)->urlfora."orcamentos/view/$idorcamento/$orcamento->idempresa";
				$linkacesso = "<a href='$url'>Portal Web - Orçamentos</a>";
				$urlHash = $this->Config->get(1)->urlfora.'orcamentos/viewhash/'.$orcamento->hash;
				$linkacesso .= " ou se não possuir login, acesse <a href='$urlHash'>este link</a>";
			// Subistitui as tags 
				$mensagem = $empresa->orcamentomensagem;
				$mensagem = str_replace("#LINKACESSO#", $linkacesso, $empresa->orcamentomensagem);
				$assunto = str_replace("#NROORCAMENTO#", $idorcamento, $empresa->orcamentoassunto);
			// Assinautra 
				if(empty($this->Auth->user('assinaturapgm'))) $message = $mensagem . '<hr> PGM' ;
				else {
					$img = '<img src="'.$this->Auth->user('assinaturapgm') .'" alt=""/>';
					$message = $mensagem . '<hr>' . $img;
				}
			// Anexos (fluxo real)
				$arrayEmail = [];
				$tmpGeneratedPdfPath = null;

				$hasUploads = false;
				if (!empty($data['file']) && is_array($data['file'])) {
					foreach ($data['file'] as $reg) {
						if (!empty($reg['tmp_name'])) {
							$hasUploads = true;
							$arrayEmail[$reg['name']] = ['file' => $reg['tmp_name']];
						}
					}
				}

				// Passo 6: se não foi enviado arquivo, gera e anexa PDF automaticamente.
				if ($isStep6 && !$hasUploads) {
					try {
						if ($this->_prepareImprimirViewVars((int)$idorcamento)) {
							$pdfBytes = $this->_renderOrcamentoPdfHtmlToPdf();
							$tmpGeneratedPdfPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Orcamento_' . (int)$idorcamento . '_' . uniqid('', true) . '.pdf';
							file_put_contents($tmpGeneratedPdfPath, $pdfBytes);
							$arrayEmail['Orcamento_' . (int)$idorcamento . '.pdf'] = ['file' => $tmpGeneratedPdfPath];
						}
					} catch (\Throwable $e) {
						// Se falhar gerar PDF, envia mesmo assim (sem anexo) para não bloquear o fluxo.
					}
				}
			// Empresa 
				$empresa = $this->Empresas->get($this->Auth->user('idempresa'));
				if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
				else $nomeempresa = $empresa->razaosocial;
			//

			$destinatario = $data['emailemail'];
			$email = new Email();
			
			$email->transport(((int)$this->Auth->user('idempresa') === (int)C_EmpresaMaster) ? 'master' : 'pgm');
			$from = 'helpdesk@pgm.inf.br';

			$email->from([$from => $nomeempresa]);
			$email->attachments($arrayEmail)
				->to($destinatario)
				->emailFormat('html')
				->subject($assunto);

			if($email->send($message) ){
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $data['idorcamento']);
				// Atualiza status para "Enviado" e registra movimento.
				$sitantiga = (int)($orcamento->status ?? C_OrcamentoStatusPendente);
				$orcamento->status = C_OrcamentoStatusEnviado;
				$this->Orcamentos->save($orcamento);
				$this->criarMov($idorcamento, $sitantiga, C_OrcamentoStatusEnviado, null);

				if (!empty($tmpGeneratedPdfPath) && is_file($tmpGeneratedPdfPath)) {
					@unlink($tmpGeneratedPdfPath);
				}

				if ($isStep6) {
					return $this->redirect(['action' => 'envioassinatura', $data['idorcamento'], '?' => ['ok' => 1]]);
				}
				return $this->redirect(['action' => 'edit', $data['idorcamento']]);
			} else $this->Flash->error('Erro ao enviar e-mail.');

			if (!empty($tmpGeneratedPdfPath) && is_file($tmpGeneratedPdfPath)) {
				@unlink($tmpGeneratedPdfPath);
			}

			if ($isStep6) {
				$this->Flash->error('Erro ao enviar e-mail.');
				return $this->redirect(['action' => 'envioassinatura', $data['idorcamento']]);
			}

			return $this->redirect(['action' => 'edit', $data['idorcamento']]);
		}
	}

	public function novaordem($idorcamento) {
        $idempresa = $this->Auth->user('idempresa');

        $ultimo = $this->Ordensservico->find('all')->where(['idempresa' => $idempresa])->order(['id' => 'ASC'])->last();
        if(empty($ultimo)) {
            $_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $this->Auth->user('id') . $idorcamento;
            $idcarrinhoorcamento = $idempresa . 1 . $this->Auth->user('id') . $idorcamento;
        } else{
            $idcarrinhoorcamento = $idempresa . $ultimo->id + 1 . $this->Auth->user('id') . $idorcamento;
            $_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo->id + 1 . $this->Auth->user('id') . $idorcamento;
        }

        $orcamento = $this->Orcamentos->findById($idorcamento)->where(['idempresa' => $idempresa])->first();
        
        // Se não achar o orçamento, aborta para não dar erro depois
        if (!$orcamento) {
            $this->Flash->error('Orçamento não encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $novaordem = $this->Ordensservico->newEntity();
        $novaordem->idcliente = $orcamento->idcliente;
        $novaordem->idempresa = $orcamento->idempresa;
        $novaordem->dataabertura = date('d/m-Y');
        $novaordem->contrato = C_OrdensContratoNao;
        $novaordem->prioridade = C_OrdensPrioridadeNormal;
        $novaordem->relato = $orcamento->relato;

        $areaObj = $this->Areas->find('all')->where([ 'LOWER(descricao)' => 'pendente'])->first();
        if ($areaObj) {
            $novaordem->idarea = $areaObj->id;
        } else {
            $areaQualquer = $this->Areas->find()->first();
            $novaordem->idarea = $areaQualquer ? $areaQualquer->id : null;
        }

        $problemaObj = $this->Problemas->find('all')->where([ 'LOWER(descricao)' => 'venda'])->first();
        if ($problemaObj) {
            $novaordem->idproblema = $problemaObj->id;
        } else {
            $problemaQualquer = $this->Problemas->find()->first();
            $novaordem->idproblema = $problemaQualquer ? $problemaQualquer->id : null;
        }
        // Combos
            $clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
            $clientesOpt = [];
            foreach($clientes as $reg){
                if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
                else $clientesOpt[$reg->id] = $reg->nome;
            }
            asort($clientesOpt);
            $areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where()->order(['descricao'])->toArray();
            $problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where()->order(['descricao'])->toArray();
            
            $produtosOpt = []; // Inicializa array vazio para evitar erro se não tiver produtos
            $produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['codigo'])->toArray();
            foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];

            $this->set('produtosMobile', $produtosOpt);
            $this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
            $this->set('problemas', $problemas);
            $this->set('areas', $areas);
            $this->set('clientes', $clientesOpt);
            
        // Resincroniza a sequência da PK de itensordem para evitar violação de unique key
        try {
            $conn = $this->Itensordem->getConnection();
            $conn->execute("SELECT setval(pg_get_serial_sequence('itensordem', 'id'), COALESCE((SELECT MAX(id) FROM itensordem), 0) + 1)");
        } catch (\Exception $e) {
            // Ignora se o banco não for PostgreSQL ou a função não existir
        }

        // Carrinho
            $idcarrinho = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento]])->first();

            if ($idcarrinho) {
                $carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idcarrinho->iditem]])->order(['id'])->toArray();

                foreach($carrinho as $reg){
                    if($reg->virouitemordem === 0){
                        $pode = 'sim';
                        $produto = $this->Produtos->findByCodigo($reg->idproduto)->where(['idempresa' => $idempresa])->first();
                        if(empty($produto)) $this->set('temalgumsemproduto', true);
                        if($pode == 'sim'){
                            $itemordem = $this->Itensordem->newEntity();
                                $itemordem->idordempk = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
                                $itemordem->iditemorcamento = $reg->id;
                                $itemordem->idempresa = $reg->idempresa;
                                if(!empty($produto)){
                                    // debug($produto);
                                    $itemordem->unidade = $produto->unidade;
                                    $itemordem->tipo = $produto->tipo;
                                    $itemordem->codproduto =  $produto->codigo;
                                }else{
                                    $itemordem->tipo = C_ProdutosTipoProduto;
                                }
                                if($reg->tipo == 1){
                                    $itemordem->quantidade = 1;
                                    if($reg->valormensal > 0) $itemordem->valortotal = $reg->valormensal;
                                    else $itemordem->valortotal = $reg->valordoservico;
                                }else{
                                    $itemordem->quantidade = $reg->quantidade;
                                    if ($reg->valormensal > 0) $itemordem->valortotal = $reg->valormensal;
                                    else $itemordem->valortotal = $reg->valordoservico;
                                }
                                $itemordem->descricao =  $reg->servico;
                                $itemordem->observacao = $reg->observacao;
                                if ($reg->valormensal > 0) $itemordem->valorunitario = (float)$reg->valormensal / (float)$reg->quantidade;
                                else $itemordem->valorunitario = $reg->valoruni;
                                $itemordem->valordesconto = 0;

                            if($this->Itensordem->save($itemordem)){
                                $reg->virouitemordem = 1;
                                $this->Orcamentosservicos->save($reg);
                            }
                        }
                    }
                }
            }
        // 

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $ordem = $this->Ordensservico->newEntity();
            $ordem = $this->Ordensservico->patchEntity($ordem, $data);
            $ordem->idempresa = $idempresa;
            $ordem->iduser = $this->Auth->user('id');
            $ordem->situacao = C_OrdensSituacaoAberta;
            $ordem->valortotal = $data['valortotalordem'];
            $ordem->id = $this->Empresas->incrementOrdem($this->Auth->user('idempresa'));
            $ordem->idorcamento = $idorcamento;

            if ($this->Ordensservico->save($ordem)) {
                // Itens
                $carrinho = $this->Ordemservicositens->newEntity();
                    $carrinho->iditens = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
                    $carrinho->idordem = $ordem->id;
                    $carrinho->idempresa = $idempresa;
                $this->Ordemservicositens->save($carrinho);
                unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);
                // Movimentação
                // $this->Ordensservico->criarMov($ordem->id, C_OrdensSituacaoLiberadaParaFaturamento, C_OrdensSituacaoLiberadaParaFaturamento, $this->Auth->user('idempresa'), $this->Auth->user('id'), $idorcamento);
                
                // ao criar ela vai ficar em aberto e não em liberada para faturamento
                $this->Ordensservico->criarMov($ordem->id, C_OrdensSituacaoAberta, C_OrdensSituacaoAberta, $this->Auth->user('idempresa'), $this->Auth->user('id'), $idorcamento);

                $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi cadastrada com sucesso!'));
                return $this->redirect(['controller' => 'Ordensservico', 'action' => 'edit', $ordem->id]);
            }
            // Decrementa o último id em caso de erro
            $this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
            $this->Flash->error(__('Não foi possível cadastrar a ordem de serviço.'));
        }

        $this->set('tiposMobile', C_ProdutosTipo);
        $this->set('tiposOpt', json_encode(C_ProdutosTipo, JSON_PRETTY_PRINT));
        $this->set('novaordem', $novaordem);
        $this->set('idorcamento', $idorcamento);
        $this->set('title', 'Nova Ordem de Serviço');
    }


	public function editaitemcarrinho() {
		$this->autoRender = false;
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$item = $this->Orcamentosservicos->find('all')
				->where([
					'AND' => [
						'idempresa' => $this->Auth->user('idempresa'), 
						'id' => $data['id']
					]
				])
				->first();
				
			if ($item) {
				function formatNumber($value) {
					if (empty($value)) return 0;
					$value = str_replace('.', '', $value);
					$value = str_replace(',', '.', $value);
					return floatval($value);
				}
				
				// Atualiza os dados do item
				$item->servico = $data['servico'];
				$item->quantidade = $data['quantidade'];
				$item->observacao = $data['observacao'];
				$item->valoruni = formatNumber($data['valoruni']);
				$item->valormensal = formatNumber($data['valormensal']);
				$item->valordoservico = formatNumber($data['valordoservico']);
				$item->idproduto = $data['idproduto'];
				$item->tipo = $data['tipo'];
				
				if ($this->Orcamentosservicos->save($item)) {
					echo 'success';
					return;
				}
			}
			
			echo 'error';
		}
	}
}