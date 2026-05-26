<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\PortalUi;
use App\Utility\RbacChecker;
use Cake\Event\Event;
use Cake\Mailer\Email;
use Cake\Routing\Router;
use Cake\View\View;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_OrcamentoStatusPendente')) {
	define('C_OrcamentoStatusPendente', 0);
}
if (!defined('C_OrcamentoStatusEnviado')) {
	define('C_OrcamentoStatusEnviado', 1);
}
if (!defined('C_OrcamentoStatusAprovado')) {
	define('C_OrcamentoStatusAprovado', 2);
}
if (!defined('C_OrcamentoStatusRecusado')) {
	define('C_OrcamentoStatusRecusado', 3);
}
if (!defined('C_OrcamentoStatusArquivado')) {
	define('C_OrcamentoStatusArquivado', 4);
}
if (!defined('C_OrcamentoAprovacaoInternaPendente')) {
	define('C_OrcamentoAprovacaoInternaPendente', 'pendente');
}
if (!defined('C_OrcamentoAprovacaoInternaAprovado')) {
	define('C_OrcamentoAprovacaoInternaAprovado', 'aprovado');
}
if (!defined('C_OrcamentoAprovacaoInternaReprovado')) {
	define('C_OrcamentoAprovacaoInternaReprovado', 'reprovado');
}

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
		$parentRet = parent::beforeFilter($event);
		if ($parentRet !== null) {
			return $parentRet;
		}

		if($this->Auth->user('role') == 1 && !$this->Auth->user('permissaoacesso')) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$action = $this->request->getParam('action');
		if (in_array($action, ['solicitar', 'catalogoSugestoes'], true) && (int)$this->Auth->user('role') === 1) {
			if (!RbacChecker::clientePodeSolicitarOrcamento(
				(int)$this->Auth->user('id'),
				!empty($this->Auth->user('permissaoacesso'))
			)) {
				$this->Flash->error(__('Seu usuário não está autorizado a solicitar orçamento. Peça ao administrador a permissão «orcamentos.solicitar» no seu papel (RBAC).'));
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		}
		if (in_array($action, ['solicitar', 'catalogoSugestoes'], true) && (int)$this->Auth->user('role') !== 1) {
			return $this->redirect(['action' => 'index']);
		}

		if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
			$orcamento = $this->Orcamentos->get($event->_subject->request->params['pass'][0])->idcliente;
			$cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;
			
			if ($orcamento != $cliente) {
				$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		}

		if (!in_array($this->request->getParam('action'), ['solicitar', 'catalogoSugestoes'], true)) {
			$this->set('title', 'Orçamentos');
		}
		$this->Auth->allow(['viewhash', 'carrinhoedit', 'aprovarhash', 'seguroProposta']);
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
		$temDescItem = $this->_orcServicoTemDescontoColunas();
		foreach ($servicos as $s) {
			$bid = (int)$s->idorcamento;
			if (!isset($sumByItem[$bid])) {
				$sumByItem[$bid] = 0.0;
			}
			$sumByItem[$bid] += \App\Utility\OrcamentoDescontoUtil::linhaLiquido($s, $temDescItem);
		}
		foreach ($orcToItem as $oid => $iid) {
			$out[$oid] = $sumByItem[$iid] ?? 0.0;
		}

		return $out;
	}

	/**
	 * Rótulo de versão (ex.: v1) — coluna `versao` quando migrada; senão movimentações legado.
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
		if ($this->_orcSchemaHasColumn('versao')) {
			foreach ($this->Orcamentos->find()
				->select(['id', 'versao'])
				->where(['idempresa' => $idempresa, 'id IN' => $orcamentoIds])
				->enableHydration(false) as $row) {
				$oid = (int)($row['id'] ?? 0);
				if ($oid > 0) {
					$out[$oid] = 'v' . max(1, (int)($row['versao'] ?? 1));
				}
			}

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

	protected function _orcSchemaHasColumn(string $column): bool {
		return $this->Orcamentos->getSchema()->hasColumn($column);
	}

	protected function _orcGrupoVersaoId($orcamento): int {
		if ($orcamento === null) {
			return 0;
		}
		if ($this->_orcSchemaHasColumn('idgrupoversao') && !empty($orcamento->idgrupoversao)) {
			return (int)$orcamento->idgrupoversao;
		}

		return (int)$orcamento->id;
	}

	/**
	 * @return \Cake\Datasource\EntityInterface[]
	 */
	protected function _orcVersoesDoGrupo($idempresa, int $grupoId): array {
		if ($grupoId <= 0 || !$this->_orcSchemaHasColumn('idgrupoversao')) {
			return [];
		}

		return $this->Orcamentos->find()
			->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idgrupoversao' => $grupoId])
			->contain(['Users' => ['fields' => ['Users.id', 'Users.name']]])
			->order(['Orcamentos.versao' => 'DESC', 'Orcamentos.id' => 'DESC'])
			->toArray();
	}

	protected function _orcDescontoAbsoluto($orcamento, float $subVenda): float {
		if (!$this->_orcSchemaHasColumn('desconto_valor') || $orcamento === null) {
			return 0.0;
		}
		$dv = (float)($orcamento->desconto_valor ?? 0);
		$tipo = (string)($orcamento->desconto_tipo ?? 'pct');

		return \App\Utility\OrcamentoDescontoUtil::descontoAbsoluto($subVenda, $dv, $tipo);
	}

	protected function _orcServicoTemDescontoColunas(): bool {
		$table = $this->Orcamentosservicos;
		$schema = $table->getSchema();
		if ($schema->hasColumn('desconto_valor') && $schema->hasColumn('desconto_tipo')) {
			return true;
		}
		// Após migration, o cache de schema pode estar desatualizado até clear_all.
		$schema = $table->getSchema(true);

		return $schema->hasColumn('desconto_valor') && $schema->hasColumn('desconto_tipo');
	}

	protected function _orcPatchItemDescontoFromRequest($item, array $data): void {
		if (!$this->_orcServicoTemDescontoColunas() || $item === null) {
			return;
		}
		$rawVal = $data['desconto_valor'] ?? $data['item_desconto_valor'] ?? 0;
		$val = is_numeric($rawVal)
			? (float)$rawVal
			: (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', (string)$rawVal));
		if ($val < 0) {
			$val = 0.0;
		}
		$tipo = (string)($data['desconto_tipo'] ?? $data['item_desconto_tipo'] ?? 'pct');
		if (!in_array($tipo, ['pct', 'fix'], true)) {
			$tipo = 'pct';
		}
		$item->set('desconto_valor', round($val, 2));
		$item->set('desconto_tipo', $tipo);
	}

	protected function _orcPatchDescontoFromRequest($orcamento, array $data): void {
		if (!$this->_orcSchemaHasColumn('desconto_valor') || $orcamento === null) {
			return;
		}
		$rawVal = $data['desconto_valor'] ?? $data['disc_val'] ?? 0;
		$val = is_numeric($rawVal)
			? (float)$rawVal
			: (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,-]/', '', (string)$rawVal));
		if ($val < 0) {
			$val = 0.0;
		}
		$tipo = (string)($data['desconto_tipo'] ?? $data['disc_tipo'] ?? 'pct');
		if (!in_array($tipo, ['pct', 'fix'], true)) {
			$tipo = 'pct';
		}
		$orcamento->set('desconto_valor', round($val, 2));
		$orcamento->set('desconto_tipo', $tipo);
	}

	protected function _orcEnsureGrupoVersaoOnSave($orcamento): void {
		if ($orcamento === null) {
			return;
		}
		if ($this->_orcSchemaHasColumn('idgrupoversao') && empty($orcamento->idgrupoversao) && !empty($orcamento->id)) {
			$orcamento->set('idgrupoversao', (int)$orcamento->id);
		}
		if ($this->_orcSchemaHasColumn('versao') && (empty($orcamento->versao) || (int)$orcamento->versao < 1)) {
			$orcamento->set('versao', 1);
		}
		if ($this->_orcSchemaHasColumn('aprovacao_interna') && ($orcamento->aprovacao_interna === null || $orcamento->aprovacao_interna === '')) {
			$orcamento->set('aprovacao_interna', C_OrcamentoAprovacaoInternaPendente);
		}
	}

	protected function _orcPodeAprovarInterno(): bool {
		if ((int)$this->Auth->user('role') !== 0) {
			return false;
		}
		if (!empty($this->Auth->user('admin'))) {
			return true;
		}

		return RbacChecker::userHasPermissionCode((int)$this->Auth->user('id'), 'orcamentos.approve');
	}

	/**
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function _orcamentoEquipe($id, $idempresa) {
		$id = (int)$id;
		if ($id <= 0) {
			return null;
		}

		return $this->Orcamentos->find()
			->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.id' => $id])
			->first();
	}

	protected function _orcCopiarCarrinhoParaIditem($idempresa, $iditemOrigem, $iditemDestino): bool {
		$servicos = $this->Orcamentosservicos->find()
			->where(['idempresa' => $idempresa, 'idorcamento' => $iditemOrigem])
			->order(['id' => 'ASC'])
			->toArray();
		foreach ($servicos as $s) {
			$data = $s->toArray();
			unset($data['id'], $data['created'], $data['modified']);
			$data['idorcamento'] = $iditemDestino;
			$data['idempresa'] = $idempresa;
			$novo = $this->Orcamentosservicos->newEntity($data, ['validate' => false]);
			if (!$this->Orcamentosservicos->save($novo)) {
				return false;
			}
		}

		return true;
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

	protected function _orcParseDecimalBr($value): float {
		if ($value === null || $value === '') {
			return 0.0;
		}
		if (is_int($value) || is_float($value)) {
			return (float)$value;
		}
		if (is_numeric($value) && strpos((string)$value, ',') === false) {
			return (float)$value;
		}
		$str = trim((string)$value);
		if ($str === '') {
			return 0.0;
		}
		$str = preg_replace('/[^\d.,-]/', '', $str);
		if ($str === '' || $str === '-') {
			return 0.0;
		}
		if (strpos($str, ',') !== false) {
			$str = str_replace('.', '', $str);
			$str = str_replace(',', '.', $str);
		}

		return (float)$str;
	}

	/**
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function _orcFindProdutoByIdprodutoCampo($idprodutoVal, $idempresa) {
		$idprodutoVal = trim((string)$idprodutoVal);
		if ($idprodutoVal === '' || $idprodutoVal === '0') {
			return null;
		}
		$produto = $this->Produtos->findByCodigo($idprodutoVal)->where(['idempresa' => $idempresa])->first();
		if ($produto === null && is_numeric($idprodutoVal) && (int)$idprodutoVal > 0) {
			$produto = $this->Produtos->findById((int)$idprodutoVal)->where(['idempresa' => $idempresa])->first();
		}

		return $produto;
	}

	/**
	 * Preenche valoruni / valordoservico a partir do cadastro quando a linha está sem bruto.
	 */
	protected function _orcSincronizarPrecoProdutoNaLinhaSeBrutoZero($item, $idempresa): void {
		if ($item === null || \App\Utility\OrcamentoDescontoUtil::linhaBruto($item) > 0.0001) {
			return;
		}
		$produto = $this->_orcFindProdutoByIdprodutoCampo($item->idproduto ?? '', $idempresa);
		if ($produto === null) {
			return;
		}
		$preco = (float)($produto->vlunitario ?? 0);
		if ($preco <= 0) {
			return;
		}
		$qtd = $this->_parseQuantidadeOrcamentoLinha($item->quantidade ?? 0);
		if ($qtd <= 0) {
			$qtd = 1.0;
		}
		$item->valoruni = $preco;
		$item->valormensal = 0;
		$item->valordoservico = $preco * $qtd;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $item
	 */
	protected function _orcAplicarPrecosLinhaServicoFromRequest($item, array $data): void {
		$vlMensalUnit = $this->_orcParseDecimalBr($data['valormensal'] ?? 0);
		$qtd = $this->_parseQuantidadeOrcamentoLinha($data['quantidade'] ?? ($item->quantidade ?? 0));
		if ($qtd <= 0) {
			$qtd = 1.0;
		}

		if ($vlMensalUnit > 0.0001) {
			$item->valormensal = $vlMensalUnit * $qtd;
			$item->valoruni = 0;
			$item->valordoservico = 0;

			return;
		}

		$valoruni = $this->_orcParseDecimalBr($data['valoruni'] ?? ($item->valoruni ?? 0));
		$valordoservico = $this->_orcParseDecimalBr($data['valordoservico'] ?? 0);
		$item->valormensal = 0;
		$item->valoruni = $valoruni;
		if ($valordoservico > 0.0001) {
			$item->valordoservico = $valordoservico;
		} elseif ($valoruni > 0.0001) {
			$item->valordoservico = $valoruni * $qtd;
		} else {
			$item->valordoservico = 0;
		}
	}

	/**
	 * Custo unitário por código de produto (mesma coluna usada em _carrinhoLinhasCustoMargem).
	 *
	 * @return array<string,float> codigo => custo unitário
	 */
	protected function _produtosCustoUnitPorCodigo($idempresa): array {
		$schema = $this->Produtos->getSchema();
		$costCol = null;
		foreach (['vlcusto', 'precocusto', 'vlcustounitario', 'custo'] as $c) {
			if ($schema->hasColumn($c)) {
				$costCol = $c;
				break;
			}
		}
		if ($costCol === null) {
			return [];
		}
		$out = [];
		foreach ($this->Produtos->find()
			->select(['codigo', $costCol])
			->where(['idempresa' => $idempresa])
			->enableHydration(false) as $p) {
			$out[trim((string) $p['codigo'])] = (float) ($p[$costCol] ?? 0);
		}

		return $out;
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
		$tipoMeta = $this->_produtoTipoMetaPorCodigo($idempresa);
		$temDescItem = $this->_orcServicoTemDescontoColunas();
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
			$venda = \App\Utility\OrcamentoDescontoUtil::linhaLiquido($reg, $temDescItem);
			$descItemAbs = \App\Utility\OrcamentoDescontoUtil::linhaDescontoAbsoluto($reg, $temDescItem);
			$margemPct = null;
			if ($venda > 0.0001) {
				$margemPct = (int)round((($venda - $custoLinha) / $venda) * 100);
			}
			$tipoBadge = 'serv';
			$tipoLabel = 'Serviço';
			$keyTipo = trim((string)($idp ?? ''));
			if ($keyTipo !== '' && $keyTipo !== '0') {
				if (isset($tipoMeta[$keyTipo])) {
					$tipoBadge = $tipoMeta[$keyTipo]['tipoBadge'];
					$tipoLabel = $tipoMeta[$keyTipo]['tipoLabel'];
				} elseif (is_numeric($keyTipo) && isset($tipoMeta[(string)(int)$keyTipo])) {
					$tipoBadge = $tipoMeta[(string)(int)$keyTipo]['tipoBadge'];
					$tipoLabel = $tipoMeta[(string)(int)$keyTipo]['tipoLabel'];
				}
			}
			$brutoLinha = \App\Utility\OrcamentoDescontoUtil::linhaBruto($reg);
			$vuDisplay = (float)($reg->valoruni ?? 0);
			if ($vuDisplay <= 0.0001 && $brutoLinha > 0.0001 && $q > 0.0001) {
				$vuDisplay = $brutoLinha / $q;
			}
			$out[$rid] = [
				'custoLinha' => $custoLinha,
				'margemPct' => $margemPct,
				'tipoBadge' => $tipoBadge,
				'tipoLabel' => $tipoLabel,
				'descontoAbs' => $descItemAbs,
				'descontoValor' => $temDescItem ? (float)($reg->desconto_valor ?? 0) : 0.0,
				'descontoTipo' => $temDescItem ? (string)($reg->desconto_tipo ?? 'pct') : 'pct',
				'vlLiquido' => $venda,
				'valorUnitDisplay' => $vuDisplay,
				'linhaBruto' => $brutoLinha,
			];
		}

		return $out;
	}

	/**
	 * Metadados de tipo (prod/serv/lic/loc) por código de produto.
	 *
	 * @return array<string,array{tipoBadge:string,tipoLabel:string}>
	 */
	protected function _produtoTipoMetaPorCodigo($idempresa): array {
		$out = [];
		foreach ($this->Produtos->find()
			->select(['id', 'codigo', 'tipo'])
			->where(['idempresa' => $idempresa])
			->enableHydration(false) as $p) {
			$meta = \App\Utility\ProdutoTipoOrcamentoUtil::labelAndBadge($p['tipo'] ?? 0);
			$tipoBadge = $meta['badge'] === 'srv' ? 'serv' : $meta['badge'];
			$key = trim((string)($p['codigo'] ?? ''));
			if ($key !== '') {
				$out[$key] = ['tipoBadge' => $tipoBadge, 'tipoLabel' => $meta['tipoLabel']];
			}
			$out[(string)(int)$p['id']] = ['tipoBadge' => $tipoBadge, 'tipoLabel' => $meta['tipoLabel']];
		}

		return $out;
	}

	/**
	 * Próximo ID de orçamento (pré-visualização, sem incrementar).
	 */
	protected function _previewProximoOrcamentoId($idempresa): int {
		$empresa = $this->Empresas->get($idempresa);
		$candidate = (int)$empresa->prxorcamento + 1;
		for ($i = 0; $i < 50; $i++) {
			if (empty($this->Orcamentos->findById($candidate)->where(['idempresa' => $idempresa])->first())) {
				return $candidate;
			}
			$candidate++;
		}

		return $candidate;
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

	/**
	 * Garante formapagamento coerente com as opções da proposta (evita lixo/BOM na coluna).
	 *
	 * @param \Cake\Datasource\EntityInterface|null $orcamento
	 */
	protected function _orcNormalizeFormaPagamento($orcamento): void {
		if ($orcamento === null) {
			return;
		}
		$opcoes = $this->_orcFormaPagamentoOpcoes();
		$v = $orcamento->get('formapagamento');
		$s = is_string($v) ? trim(str_replace("\0", '', $v)) : '';
		if ($s !== '') {
			$s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
		}
		if ($s === '' || !array_key_exists($s, $opcoes)) {
			$orcamento->set('formapagamento', 'À vista');
		} else {
			$orcamento->set('formapagamento', $s);
		}
	}

	/**
	 * Resolve o iditem do carrinho (Orcamentosservicos.idorcamento) a partir do id do orçamento.
	 * Espelha a lógica de edit() — necessário quando $_SESSION['idcarrinho'] foi limpa (ex.: limpasession no beforeunload).
	 *
	 * @return string|null iditem ou null se o orçamento não existir / não pertencer à empresa
	 */
	protected function _orcamentoResolveIditemCarrinhoSession(int $idOrcamento): ?string {
		$idempresa = $this->Auth->user('idempresa');
		$orc = $this->Orcamentos->find('all')
			->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.id' => $idOrcamento])
			->first();
		if ($orc === null) {
			return null;
		}
		$carrinho = $this->Orcamentositens->find('all')
			->where(['AND' => ['idempresa' => $idempresa, 'idorcamento' => $idOrcamento]])
			->first();
		if (empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();

			return (string) ($this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id'));
		}

		return (string) $carrinho->iditem;
	}

	/**
	 * JSON do catálogo (mesmo payload da tela Novo orçamento) para o modal "Buscar no catálogo".
	 *
	 * @param \Cake\Datasource\EntityInterface[] $produtosOpt1 Produtos ativos da empresa
	 */
	protected function _orcamentoProdutosCatalogoJson(array $produtosOpt1): string {
		$produtosCatalogoLista = [];
		$idempresaCat = (int) $this->Auth->user('idempresa');
		$custoPorCodigo = $this->_produtosCustoUnitPorCodigo($idempresaCat);
		foreach ($produtosOpt1 as $reg) {
			$tipoInt = (int)($reg->tipo ?? 0);
			$tipoMeta = \App\Utility\ProdutoTipoOrcamentoUtil::labelAndBadge($tipoInt);
			$tipoLabel = $tipoMeta['tipoLabel'];
			$badge = $tipoMeta['badge'];
			$codKey = trim((string) $reg->codigo);
			$custoU = $custoPorCodigo[$codKey] ?? 0.0;
			$vendaU = (float) ($reg->vlunitario ?? 0);
			$margemPct = null;
			if ($vendaU > 0.0001 && $custoU > 0.0001) {
				$margemPct = (int) round((($vendaU - $custoU) / $vendaU) * 100);
			}
			$produtosCatalogoLista[] = [
				'id' => (string) $reg->codigo,
				'codigo' => (string) $reg->codigo,
				'descricao' => (string) ($reg->descricao ?? ''),
				'nome' => (string) ($reg->descricao ?? ''),
				'tipo' => $tipoInt,
				'tipoLabel' => $tipoLabel,
				'badge' => $badge,
				'vlunitario' => $vendaU,
				'unidade' => (string) ($reg->unidade ?? 'un'),
				'custoUnit' => $custoU > 0.0001 ? round($custoU, 4) : null,
				'margemPct' => $margemPct,
			];
		}

		return json_encode($produtosCatalogoLista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
	}

	public function index() {
		if ((int)$this->Auth->user('role') !== (int)C_RoleCliente) {
			$prototypeLista = PortalUi::redirectToPrototypeIfEnabled('orcamentos', 'OrcamentosPrototype', 'lista');
			if ($prototypeLista !== null) {
				return $this->redirect($prototypeLista);
			}
		}
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

	/**
	 * Tela de solicitação de orçamento para clientes (role==1).
	 * Coleta dados detalhados do pedido e cria um ticket de categoria "Orçamento".
	 * Acesso: permissaoacesso + RBAC orcamentos.solicitar quando o usuário tiver papéis RBAC.
	 */
	public function solicitar() {
		$this->set('title', __('Solicitar proposta comercial'));
		$this->set('hideLayoutPageTitle', true);
		$this->set('bodyPageClass', 'pgm-solicitar-proposta');

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$linhas   = [];
			$linhas[] = '--- SOLICITAÇÃO DE ORÇAMENTO ---';
			$linhas[] = 'Assunto: ' . ($data['assunto'] ?? '');
			$linhas[] = 'Urgência: ' . ($data['urgencia'] ?? 'Normal');
			if (!empty($data['descricao'])) {
				$linhas[] = 'Descrição: ' . $data['descricao'];
			}
			if (!empty($data['itens'])) {
				$linhas[] = '';
				$linhas[] = 'ITENS SOLICITADOS:';
				foreach ((array)$data['itens'] as $i => $item) {
					if (empty($item['descricao'])) {
						continue;
					}
					$cod = trim((string)($item['codigo'] ?? ''));
					$prefix = $cod !== '' ? ('[' . $cod . '] ') : '';
					$linhas[] = sprintf(
						'  %d. %s%s — Qtd: %s — Obs: %s',
						$i + 1,
						$prefix,
						$item['descricao'],
						$item['quantidade'] ?? '1',
						$item['obs'] ?? ''
					);
				}
			}
			if (!empty($data['prazo'])) {
				$linhas[] = 'Prazo desejado: ' . $data['prazo'];
			}
			$solicitacaoTexto = implode("\n", $linhas);

			$this->loadModel('Tickets');
			$ticket = $this->Tickets->newEntity();
			$ticket->idempresa  = $this->Auth->user('idempresa');
			$ticket->idcliente  = $this->Auth->user('idcliente');
			$ticket->idautor    = $this->Auth->user('id');
			$ticket->assunto    = 4;
			$ticket->solicitacao = $solicitacaoTexto;
			$ticket->situacao   = 0;
			$ticket->resolvido  = 0;
			$ticket->email      = $this->Auth->user('email') ?? '';
			if ($this->Tickets->save($ticket)) {
				$this->Flash->success(__('Proposta solicitada com sucesso. Nossa equipe retornará em breve. Referência do chamado #%s.', $ticket->id));

				return $this->redirect(['controller' => 'Orcamentos', 'action' => 'index']);
			}
			$this->Flash->error(__('Não foi possível enviar a solicitação. Tente novamente.'));
		}

		$catalogoDestaque = $this->Produtos->find()
			->select(['codigo', 'descricao', 'vlunitario', 'tipo'])
			->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])
			->order(['descricao' => 'ASC'])
			->limit(8)
			->toArray();
		$this->set('catalogoDestaque', $catalogoDestaque);
		$this->set('catalogoSugestoesUrl', Router::url(['controller' => 'Orcamentos', 'action' => 'catalogoSugestoes'], true));
	}

	/**
	 * JSON para autocomplete do catálogo na tela solicitar (apenas clientes autorizados).
	 */
	public function catalogoSugestoes() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;

		$q = trim((string)$this->request->getQuery('q', ''));
		if (strlen($q) < 2) {
			return $this->response
				->withType('application/json')
				->withStringBody(json_encode(['itens' => []], JSON_UNESCAPED_UNICODE));
		}
		$qSafe = str_replace(['%', '_'], '', $q);
		if ($qSafe === '') {
			return $this->response
				->withType('application/json')
				->withStringBody(json_encode(['itens' => []], JSON_UNESCAPED_UNICODE));
		}

		$query = $this->Produtos->find()
			->select(['codigo', 'descricao', 'vlunitario', 'tipo'])
			->where([
				'idempresa' => $this->Auth->user('idempresa'),
				'ativo' => 1,
				'OR' => [
					'codigo LIKE' => '%' . $qSafe . '%',
					'descricao LIKE' => '%' . $qSafe . '%',
				],
			])
			->order(['descricao' => 'ASC'])
			->limit(25);

		$itens = [];
		foreach ($query->toArray() as $p) {
			$itens[] = [
				'codigo' => trim((string)$p->codigo),
				'descricao' => trim((string)$p->descricao),
				'preco' => (float)($p->vlunitario ?? 0),
				'tipo' => (int)$p->tipo,
			];
		}

		return $this->response
			->withType('application/json')
			->withStringBody(json_encode(['itens' => $itens], JSON_UNESCAPED_UNICODE));
	}

	public function add($idticket = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'solicitar']);

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
			$postData = $this->request->getData();
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $postData);
			$this->_orcNormalizeFormaPagamento($orcamento);
			$this->_orcPatchDescontoFromRequest($orcamento, $postData);
			$orcamento->created = date("Y-m-d H:i:s");
			$orcamento->idautor = $this->Auth->user('id');
			$orcamento->id = $this->Empresas->incrementOrcamento($this->Auth->user('idempresa'));
			$orcamento->idempresa = $this->Auth->user('idempresa');
			$orcamento->hash = $orcamento->idautor . $orcamento->id . $orcamento->idempresa . sequenciaAleatoria();
			// cria status por padrao como pendente
			$orcamento->status = C_OrcamentoStatusPendente;
			$this->_orcEnsureGrupoVersaoOnSave($orcamento);
			if ($this->_orcSchemaHasColumn('idgrupoversao')) {
				$orcamento->set('idgrupoversao', (int)$orcamento->id);
			}

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
					$next = PortalUi::isPremiumModule('orcamentos')
						? PortalUi::orcamentosDetalheRoute((int)$orcamento->id)
						: ['action' => 'edit', $orcamento->id];

					return $this->redirect($next ?? ['action' => 'edit', $orcamento->id]);
				}
				$this->Orcamentos->delete($orcamento);
				$this->Empresas->decrementOrcamento($this->Auth->user('idempresa'));
				$this->Flash->error(__('Não foi possível vincular os itens ao orçamento (erro ao gravar o carrinho). Se o problema persistir, verifique a sequence da tabela orcamentosnovositens no PostgreSQL.'));
			}
			$orcamento->id = $this->Empresas->decrementOrcamento($this->Auth->user('idempresa'));
			$this->Flash->error(__('Não foi possível gerar o orçamento.'));
		} else $this->limpacarrinho();

		$this->_orcNormalizeFormaPagamento($orcamento);

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

			$this->set('produtosCatalogoJson', $this->_orcamentoProdutosCatalogoJson($produtosOpt1));

		$this->set('idcarrinho', $_SESSION['idcarrinhoadd']);
		$this->set('clientes', $clientesOpt);
		$this->set('produtos', $produtosOpt);
		$this->set('orcamento', $orcamento);
		$this->set('clientesMetaJson', json_encode($clientesMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE));
		$this->set('title', 'Gerar Orçamento');
		$this->set('hideLayoutPageTitle', true);
		$this->set('orcStepperStep', 1);
		$this->set('orcFormaPagamentoOpcoes', $this->_orcFormaPagamentoOpcoes());
		$this->set('orcPreviewNumero', $this->_previewProximoOrcamentoId($this->Auth->user('idempresa')));
		$this->set('orcItemDescontoEnabled', $this->_orcServicoTemDescontoColunas());
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
		if ($orcamento) {
			$this->_orcNormalizeFormaPagamento($orcamento);
		}
		$carrinho = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $id]])->first();


		if(empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();
			$_SESSION['idcarrinho'] =  $this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id');
		} else { 
			$idcarrinho = $carrinho->iditem;
			$_SESSION['idcarrinho'] = $idcarrinho;
		}


		if ($this->request->is(['post', 'put'])) {
			$postData = $this->request->getData();
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $postData);
			$this->_orcNormalizeFormaPagamento($orcamento);
			$this->_orcPatchDescontoFromRequest($orcamento, $postData);
			$this->_orcEnsureGrupoVersaoOnSave($orcamento);
			if ($this->_orcSchemaHasColumn('aprovacao_interna')
				&& (string)$orcamento->aprovacao_interna === C_OrcamentoAprovacaoInternaReprovado) {
				$orcamento->set('aprovacao_interna', C_OrcamentoAprovacaoInternaPendente);
				$orcamento->set('aprovacao_interna_em', null);
				$orcamento->set('aprovacao_interna_por', null);
				$orcamento->set('aprovacao_interna_motivo', null);
			}

			if ($this->Orcamentos->save($orcamento)) {
				$this->Flash->success(__('Orçamento alterado com sucesso!'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			} else {
				$this->Flash->error(__('Não foi possível alterar o orçamento.'));
			}

			return $this->redirect(['action' => 'edit', $orcamento->id]);
		}

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach ($produtosOpt1 as $reg) {
			$produtosOpt[$reg->codigo] = $reg->descricao . ' (' . $reg->codigo . ')';
		}
		$this->set('produtosCatalogoJson', $this->_orcamentoProdutosCatalogoJson($produtosOpt1));

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
		$this->set('orcItemDescontoEnabled', $this->_orcServicoTemDescontoColunas());
	}

	/**
	 * Duplica proposta como nova versão (novo id de orçamento, mesmo grupo).
	 */
	public function novaversao($id = null) {
		if ((int)$this->Auth->user('role') === 1) {
			$this->Flash->error(__('Sem permissão.'));

			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$this->request->allowMethod(['post', 'get']);
		$idempresa = (int)$this->Auth->user('idempresa');
		$origem = $this->_orcamentoEquipe($id, $idempresa);
		if ($origem === null) {
			$this->Flash->error(__('Orçamento não encontrado.'));

			return $this->redirect(['action' => 'index']);
		}
		if (!$this->_orcSchemaHasColumn('idgrupoversao')) {
			$this->Flash->error(__('Execute as migrations do módulo de orçamentos (versão/aprovação).'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}
		if ((int)$origem->status === (int)C_OrcamentoStatusAprovado) {
			$this->Flash->error(__('Não é possível criar revisão de orçamento já aprovado pelo cliente.'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}

		$grupoId = $this->_orcGrupoVersaoId($origem);
		$maxVersao = (int)$this->Orcamentos->find()
			->select(['versao'])
			->where(['idempresa' => $idempresa, 'idgrupoversao' => $grupoId])
			->order(['versao' => 'DESC'])
			->enableHydration(false)
			->first()['versao'] ?? 1;

		$link = $this->Orcamentositens->find()
			->where(['idempresa' => $idempresa, 'idorcamento' => (int)$origem->id])
			->order(['id' => 'ASC'])
			->first();
		if ($link === null || empty($link->iditem)) {
			$this->Flash->error(__('Orçamento sem itens para duplicar.'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}
		$iditemOrigem = $link->iditem;

		$novo = $this->Orcamentos->newEntity($origem->toArray(), ['validate' => false]);
		unset($novo->id, $novo->created, $novo->modified);
		$novoId = $this->Empresas->incrementOrcamento($idempresa);
		$novo->set('id', $novoId);
		$novo->set('idempresa', $idempresa);
		$novo->set('idautor', $this->Auth->user('id'));
		$novo->set('created', date('Y-m-d H:i:s'));
		$novo->set('status', C_OrcamentoStatusPendente);
		$novo->set('hash', $this->Auth->user('id') . $novoId . $idempresa . sequenciaAleatoria());
		$novo->set('versao', $maxVersao + 1);
		$novo->set('idgrupoversao', $grupoId);
		$novo->set('aprovacao_interna', C_OrcamentoAprovacaoInternaPendente);
		$novo->set('aprovacao_interna_em', null);
		$novo->set('aprovacao_interna_por', null);
		$novo->set('aprovacao_interna_motivo', null);
		if ($this->_orcSchemaHasColumn('pdfgerado')) {
			$novo->set('pdfgerado', 0);
		}

		if (!$this->Orcamentos->save($novo)) {
			$this->Empresas->decrementOrcamento($idempresa);
			$this->Flash->error(__('Não foi possível criar a nova versão.'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}

		$iditemNovo = $novoId . $this->Auth->user('id');
		if (!$this->_orcCopiarCarrinhoParaIditem($idempresa, $iditemOrigem, $iditemNovo)) {
			$this->Orcamentos->delete($novo);
			$this->Empresas->decrementOrcamento($idempresa);
			$this->Flash->error(__('Falha ao copiar itens da proposta.'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}
		if (!$this->_saveOrcamentositensNovoOrcamento($iditemNovo, $novoId, $idempresa)) {
			$this->Orcamentos->delete($novo);
			$this->Empresas->decrementOrcamento($idempresa);
			$this->Flash->error(__('Falha ao vincular itens da nova versão.'));

			return $this->redirect(['action' => 'view', $origem->id]);
		}

		$this->criarMov(
			$novoId,
			null,
			C_OrcamentoStatusPendente,
			'Nova versão v' . ($maxVersao + 1) . ' criada a partir do #' . (int)$origem->id,
			$idempresa
		);
		$this->Flash->success(__('Versão v{0} criada. Edite os itens e envie para revisão.', $maxVersao + 1));
		$this->Atividades->registrar($this->Auth->user('id'), 'Orcamentos', 'novaversao', $novoId);

		return $this->redirect(['action' => 'edit', $novoId]);
	}

	public function aprovarInterno($id = null) {
		$this->request->allowMethod(['post']);
		if (!$this->_orcPodeAprovarInterno()) {
			$this->Flash->error(__('Sem permissão para aprovar internamente.'));

			return $this->redirect(['action' => 'view', $id]);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$orcamento = $this->_orcamentoEquipe($id, $idempresa);
		if ($orcamento === null || !$this->_orcSchemaHasColumn('aprovacao_interna')) {
			$this->Flash->error(__('Orçamento não encontrado ou migration pendente.'));

			return $this->redirect(['action' => 'index']);
		}
		if ((int)$orcamento->status !== (int)C_OrcamentoStatusPendente) {
			$this->Flash->error(__('Aprovação interna só se aplica a propostas pendentes.'));

			return $this->redirect(['action' => 'view', $orcamento->id]);
		}
		$orcamento->set('aprovacao_interna', C_OrcamentoAprovacaoInternaAprovado);
		$orcamento->set('aprovacao_interna_em', date('Y-m-d H:i:s'));
		$orcamento->set('aprovacao_interna_por', (int)$this->Auth->user('id'));
		$orcamento->set('aprovacao_interna_motivo', null);
		if ($this->Orcamentos->save($orcamento)) {
			$this->criarMov(
				$orcamento->id,
				(int)$orcamento->status,
				(int)$orcamento->status,
				'Aprovação interna (gerente comercial)',
				$idempresa
			);
			$this->Flash->success(__('Proposta aprovada internamente.'));
			$this->Atividades->registrar($this->Auth->user('id'), 'Orcamentos', 'aprovarInterno', $orcamento->id);
		} else {
			$this->Flash->error(__('Não foi possível registrar a aprovação.'));
		}

		return $this->redirect(['action' => 'view', $orcamento->id]);
	}

	public function reprovarInterno($id = null) {
		$this->request->allowMethod(['post']);
		if (!$this->_orcPodeAprovarInterno()) {
			$this->Flash->error(__('Sem permissão para reprovar internamente.'));

			return $this->redirect(['action' => 'view', $id]);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$orcamento = $this->_orcamentoEquipe($id, $idempresa);
		if ($orcamento === null || !$this->_orcSchemaHasColumn('aprovacao_interna')) {
			$this->Flash->error(__('Orçamento não encontrado ou migration pendente.'));

			return $this->redirect(['action' => 'index']);
		}
		$motivo = trim((string)$this->request->getData('motivo'));
		if ($motivo === '') {
			$motivo = trim((string)$this->request->getData('aprovacao_interna_motivo'));
		}
		$orcamento->set('aprovacao_interna', C_OrcamentoAprovacaoInternaReprovado);
		$orcamento->set('aprovacao_interna_em', date('Y-m-d H:i:s'));
		$orcamento->set('aprovacao_interna_por', (int)$this->Auth->user('id'));
		$orcamento->set('aprovacao_interna_motivo', $motivo !== '' ? $motivo : null);
		if ($this->Orcamentos->save($orcamento)) {
			$this->criarMov(
				$orcamento->id,
				(int)$orcamento->status,
				(int)$orcamento->status,
				'Reprovação interna' . ($motivo !== '' ? ': ' . $motivo : ''),
				$idempresa
			);
			$this->Flash->warning(__('Proposta reprovada internamente. Ajuste e reenvie para aprovação.'));
			$this->Atividades->registrar($this->Auth->user('id'), 'Orcamentos', 'reprovarInterno', $orcamento->id);
		} else {
			$this->Flash->error(__('Não foi possível registrar a reprovação.'));
		}

		return $this->redirect(['action' => 'view', $orcamento->id]);
	}

	/**
	 * POST AJAX — desconto de uma linha do carrinho (% ou R$).
	 */
	public function salvarDescontoItem() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') === 1) {
			return $this->jsonResponse(['ok' => false, 'mensagem' => __('Sem permissão.')], 403);
		}
		if (!$this->_orcServicoTemDescontoColunas()) {
			return $this->jsonResponse(['ok' => false, 'mensagem' => __('Desconto por item indisponível. Execute as migrations.')], 503);
		}
		$id = (int)$this->request->getData('id');
		if ($id <= 0) {
			return $this->jsonResponse(['ok' => false, 'mensagem' => __('Item inválido.')], 400);
		}
		$item = $this->Orcamentosservicos->find()
			->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])
			->first();
		if ($item === null) {
			return $this->jsonResponse(['ok' => false, 'mensagem' => __('Item não encontrado.')], 404);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$this->_orcSincronizarPrecoProdutoNaLinhaSeBrutoZero($item, $idempresa);
		$this->_orcPatchItemDescontoFromRequest($item, $this->request->getData());
		if (!$this->Orcamentosservicos->save($item)) {
			return $this->jsonResponse(['ok' => false, 'mensagem' => __('Não foi possível salvar.')], 422);
		}
		$liquido = \App\Utility\OrcamentoDescontoUtil::linhaLiquido($item, true);
		$descAbs = \App\Utility\OrcamentoDescontoUtil::linhaDescontoAbsoluto($item, true);

		return $this->jsonResponse([
			'ok' => true,
			'vlLiquido' => round($liquido, 2),
			'descontoAbs' => round($descAbs, 2),
			'descontoLabel' => \App\Utility\OrcamentoDescontoUtil::rotuloDesconto(
				(float)$item->desconto_valor,
				(string)$item->desconto_tipo
			),
		], 200);
	}

	public function salvarDesconto($id = null) {
		$this->request->allowMethod(['post']);
		if ((int)$this->Auth->user('role') === 1) {
			$this->Flash->error(__('Sem permissão.'));

			return $this->redirect(['action' => 'view', $id]);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$orcamento = $this->_orcamentoEquipe($id, $idempresa);
		if ($orcamento === null) {
			$this->Flash->error(__('Orçamento não encontrado.'));

			return $this->redirect(['action' => 'index']);
		}
		$this->_orcPatchDescontoFromRequest($orcamento, $this->request->getData());
		if ($this->Orcamentos->save($orcamento)) {
			$this->Flash->success(__('Desconto atualizado.'));
			$this->Atividades->registrar($this->Auth->user('id'), 'Orcamentos', 'salvarDesconto', $orcamento->id);
		} else {
			$this->Flash->error(__('Não foi possível salvar o desconto.'));
		}

		$redirect = $this->request->getData('redirect');
		if ($redirect === 'edit') {
			return $this->redirect(['action' => 'edit', $orcamento->id]);
		}

		return $this->redirect(['action' => 'view', $orcamento->id]);
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
			->contain([
				'Users' => ['fields' => ['Users.name']],
				'Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.nome', 'Clientes.email', 'Clientes.tipo']],
			])
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
		$this->set('role', (int)$this->Auth->user('role'));
		$empresaId = (int)$this->Auth->user('idempresa');
		$orcId = (int)$id;
		$versaoMap = $this->_versaoRotuloPorOrcamentoIds($empresaId, [$orcId]);
		$valorMap = $this->_valorTotaisPorOrcamentoIds($empresaId, [$orcId]);
		$custoMap = $this->_custoTotaisPorOrcamentoIds($empresaId, [$orcId]);
		$margemMap = $this->_margemBrutaPctPorOrcamentoIds($empresaId, [$orcId], $valorMap);
		$subVenda = (float)($valorMap[$orcId] ?? 0);
		$subCusto = (float)($custoMap[$orcId] ?? 0);
		$descontoAbs = $this->_orcDescontoAbsoluto($orcamento, $subVenda);
		$totalLiquido = max(0.0, $subVenda - $descontoAbs);
		$lucro = $totalLiquido - $subCusto;
		$margemPctLiquido = $totalLiquido > 0.01 ? (int)round(($lucro / $totalLiquido) * 100) : 0;
		$grupoId = $this->_orcGrupoVersaoId($orcamento);
		$versoesLista = $this->_orcVersoesDoGrupo($empresaId, $grupoId);
		$aprovacaoInterna = $this->_orcSchemaHasColumn('aprovacao_interna')
			? (string)($orcamento->aprovacao_interna ?? C_OrcamentoAprovacaoInternaPendente)
			: C_OrcamentoAprovacaoInternaPendente;

		$this->set('orcVersaoLabel', $versaoMap[$orcId] ?? 'v1');
		$this->set('orcVersoesLista', $versoesLista);
		$this->set('orcGrupoVersaoId', $grupoId);
		$this->set('orcDescontoAbs', $descontoAbs);
		$this->set('orcDescontoValor', (float)($orcamento->desconto_valor ?? 0));
		$this->set('orcDescontoTipo', (string)($orcamento->desconto_tipo ?? 'pct'));
		$this->set('orcTotalLiquido', $totalLiquido);
		$this->set('orcAprovacaoInterna', $aprovacaoInterna);
		$this->set('orcPodeAprovarInterno', $this->_orcPodeAprovarInterno());
		$this->set('orcRevisaoMargem', [
			'subVenda' => $subVenda,
			'subCusto' => $subCusto,
			'lucro' => $lucro,
			'margemPct' => $margemPctLiquido,
			'descontoAbs' => $descontoAbs,
			'totalLiquido' => $totalLiquido,
		]);
		$this->set('empresaObj', $empresaObj);
		$this->set('orcamento', $orcamento);
		$this->set('idcarrinho', $_SESSION['idcarrinho']);
	}

	public function viewhash($hash = null) {
		$this->viewBuilder()->setLayout('orcamentos');
		$orcamento = $this->Orcamentos->findByHash($hash)->contain(['Users' => ['fields' => ['Users.name', 'Users.email']], 'Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome', 'Clientes.cpfcnpj', 'Clientes.fone', 'Clientes.fone2']]])->first();
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

	/**
	 * Portal autenticado — fluxo alinhado a pgm_portal_autenticado.html.
	 * Identidade e OTP validados no servidor; código enviado por e-mail (não exposto no HTML).
	 * URL: /orcamentos/seguro-proposta/{hash}
	 */
	public function seguroProposta($hash = null) {
		$this->viewBuilder()->setLayout(false);
		if (empty($hash)) {
			$this->Flash->error(__('Não foi encontrado um orçamento!'));
			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}

		$session = $this->request->getSession();

		if ($this->request->getQuery('reiniciar') === '1') {
			$this->_seguroPortalMerge($session, (string)$hash, [
				'awaiting_otp' => false,
				'otp_hash' => null,
				'otp_exp' => null,
				'otp_failures' => 0,
				'reenviar_count' => 0,
				'email_masked' => null,
			]);
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		if ($this->request->is('post')) {
			$orcamentoPost = $this->_findOrcamentoSeguroProposta($hash);
			if (empty($orcamentoPost)) {
				$this->Flash->error(__('Não foi encontrado um orçamento!'));
				return $this->redirect(['controller' => 'Users', 'action' => 'login']);
			}
			$acao = (string)$this->request->getData('portal_acao');
			if ($acao === 'identidade') {
				return $this->_seguroPropostaHandleIdentidade($orcamentoPost, (string)$hash);
			}
			if ($acao === 'otp') {
				return $this->_seguroPropostaHandleOtp($orcamentoPost, (string)$hash);
			}
			if ($acao === 'reenviar_otp') {
				return $this->_seguroPropostaHandleReenviarOtp($orcamentoPost, (string)$hash);
			}
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$orcamento = $this->_findOrcamentoSeguroProposta($hash);
		if (empty($orcamento)) {
			$this->Flash->error(__('Não foi encontrado um orçamento!'));
			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}

		$st = $this->_seguroPortalRead($session, (string)$hash);
		$blockedUntil = isset($st['blocked_until']) ? (int)$st['blocked_until'] : 0;
		$lockRemainingSec = ($blockedUntil > time()) ? max(0, $blockedUntil - time()) : 0;
		if ($blockedUntil > time()) {
			$passoSeguro = 'bloqueio';
			$otpExpiresIn = 0;
			$emailMaskedSeguro = '';
		} elseif (!empty($st['awaiting_otp']) && !empty($st['otp_hash']) && !empty($st['otp_exp'])) {
			$passoSeguro = 'otp';
			$otpExpiresIn = max(0, (int)$st['otp_exp'] - time());
			$emailMaskedSeguro = (string)($st['email_masked'] ?? '');
		} else {
			$passoSeguro = 'destino';
			$otpExpiresIn = 0;
			$emailMaskedSeguro = '';
		}

		$carrinhoItem = $this->Orcamentositens->find('all')->where(['idorcamento' => $orcamento->id])->first();
		$carrinho = [];
		if ($carrinhoItem) {
			$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $carrinhoItem->iditem])->order(['id'])->toArray();
		}
		$nomeClienteSeg = !empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome;
		$cnpjDigits = preg_replace('/\D/', '', (string)($orcamento->cliente->cpfcnpj ?? ''));
		$cnpj4 = strlen($cnpjDigits) >= 4 ? substr($cnpjDigits, -4) : '0000';
		$nomeClienteLower = mb_strtolower(trim((string)$nomeClienteSeg), 'UTF-8');
		$parts = preg_split('/\s+/u', $nomeClienteLower, -1, PREG_SPLIT_NO_EMPTY);
		$nomePartial = $parts[0] ?? 'cliente';
		if (mb_strlen($nomePartial, 'UTF-8') > 12) {
			$nomePartial = mb_substr($nomePartial, 0, 12, 'UTF-8');
		}
		$totalGeral = 0.0;
		foreach ($carrinho as $linha) {
			$totalGeral += (float)($linha->valordoservico ?? 0);
		}
		$descontoPct = 5;
		$descontoValor = round($totalGeral * ($descontoPct / 100), 2);
		$totalVista = max(0, $totalGeral - $descontoValor);
		$fmt = function ($v) {
			return 'R$ ' . number_format((float)$v, 2, ',', '.');
		};
		$validadeFmtSeguro = '';
		if (!empty($orcamento->validoate)) {
			$v = $orcamento->validoate;
			$validadeFmtSeguro = ($v instanceof \DateTimeInterface) ? $v->format('d/m/Y') : (string)$v;
		}
		$primeiroServicoSeguro = $carrinho[0] ?? null;
		$vendedorEmailSeguro = !empty($orcamento->user->email) ? $orcamento->user->email : 'contato@pgm.inf.br';

		$this->set('title', 'Acesso Seguro — Proposta PGM Soluções');
		$this->set('totalPrazoFmtSeguro', $fmt($totalGeral));
		$this->set('totalVistaFmtSeguro', $fmt($totalVista));
		$emailMaskedPreview = $this->_maskEmailForDisplay((string)($orcamento->cliente->email ?? ''));
		$this->set(compact(
			'orcamento',
			'nomeClienteSeg',
			'cnpj4',
			'nomePartial',
			'validadeFmtSeguro',
			'primeiroServicoSeguro',
			'vendedorEmailSeguro',
			'passoSeguro',
			'otpExpiresIn',
			'emailMaskedSeguro',
			'emailMaskedPreview',
			'lockRemainingSec'
		));
		$this->render('seguro_proposta');
	}

	/**
	 * @param string $hash
	 * @return \App\Model\Entity\Orcamento|null
	 */
	protected function _findOrcamentoSeguroProposta($hash) {
		return $this->Orcamentos->findByHash($hash)->contain([
			'Users' => ['fields' => ['Users.name', 'Users.email']],
			'Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome', 'Clientes.cpfcnpj', 'Clientes.fone', 'Clientes.fone2', 'Clientes.email']],
		])->first();
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function _seguroPortalRead($session, string $hash) {
		$all = $session->read('SeguroPortal');
		if (!is_array($all) || !isset($all[$hash]) || !is_array($all[$hash])) {
			return [];
		}
		return $all[$hash];
	}

	/**
	 * @param array<string, mixed> $merge
	 */
	protected function _seguroPortalMerge($session, string $hash, array $merge) {
		$all = $session->read('SeguroPortal');
		if (!is_array($all)) {
			$all = [];
		}
		$cur = isset($all[$hash]) && is_array($all[$hash]) ? $all[$hash] : [];
		foreach ($merge as $k => $v) {
			if ($v === null) {
				unset($cur[$k]);
			} else {
				$cur[$k] = $v;
			}
		}
		$all[$hash] = $cur;
		$session->write('SeguroPortal', $all);
	}

	protected function _seguroPortalUnsetHash($session, string $hash) {
		$all = $session->read('SeguroPortal');
		if (!is_array($all) || !isset($all[$hash])) {
			return;
		}
		unset($all[$hash]);
		$session->write('SeguroPortal', $all);
	}

	protected function _maskEmailForDisplay(string $email) {
		$email = trim($email);
		if ($email === '' || strpos($email, '@') === false) {
			return '••••@••••';
		}
		list($u, $d) = explode('@', $email, 2);
		$uLen = mb_strlen($u, 'UTF-8');
		if ($uLen <= 2) {
			return '••@' . $d;
		}
		$first = mb_substr($u, 0, 1, 'UTF-8');
		$last = mb_substr($u, -1, 1, 'UTF-8');
		return $first . '••••' . $last . '@' . $d;
	}

	protected function _sendSeguroOtpEmail($orcamento, string $toEmail, string $code) {
		try {
			$empresa = $this->Empresas->get($orcamento->idempresa);
			$nomeempresa = (isset($empresa->nomefantasia) && (string)$empresa->nomefantasia !== '') ? $empresa->nomefantasia : $empresa->razaosocial;
			$email = new Email();
			$email->transport(((int)$orcamento->idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm');
			$from = 'helpdesk@pgm.inf.br';
			$email->from([$from => $nomeempresa]);
			$esc = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
			$msg = '<p>Seu código de verificação para acessar a proposta comercial:</p>'
				. '<p style="font-size:24px;font-weight:bold;letter-spacing:4px;">' . $esc . '</p>'
				. '<p>Válido por 10 minutos. Se você não solicitou este código, ignore este e-mail.</p>';
			$email->to($toEmail)
				->emailFormat('html')
				->subject('Código de verificação — Proposta PGM nº ' . (int)$orcamento->id);
			return (bool)$email->send($msg);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _seguroClienteEmailValido($orcamento) {
		$e = trim((string)($orcamento->cliente->email ?? ''));
		if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL)) {
			return null;
		}
		return $e;
	}

	protected function _seguroPropostaValidateNomeParcial(string $nomeInput, string $nomeClienteSeg) {
		$nomeIn = mb_strtolower(trim($nomeInput), 'UTF-8');
		$nomeClienteLower = mb_strtolower(trim($nomeClienteSeg), 'UTF-8');
		$parts = preg_split('/\s+/u', $nomeClienteLower, -1, PREG_SPLIT_NO_EMPTY);
		$nomePartial = $parts[0] ?? 'cliente';
		if (mb_strlen($nomePartial, 'UTF-8') > 12) {
			$nomePartial = mb_substr($nomePartial, 0, 12, 'UTF-8');
		}
		if (mb_strpos($nomeIn, $nomePartial) !== false) {
			return true;
		}
		return mb_strlen($nomeIn, 'UTF-8') >= 2;
	}

	protected function _seguroPropostaHandleIdentidade($orcamento, string $hash) {
		$session = $this->request->getSession();
		$st = $this->_seguroPortalRead($session, $hash);
		$blockedUntil = isset($st['blocked_until']) ? (int)$st['blocked_until'] : 0;
		if ($blockedUntil > time()) {
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$cnpjDigits = preg_replace('/\D/', '', (string)($orcamento->cliente->cpfcnpj ?? ''));
		$cnpj4Expected = strlen($cnpjDigits) >= 4 ? substr($cnpjDigits, -4) : '0000';
		$cnpjIn = preg_replace('/\D/', '', (string)$this->request->getData('cnpj_input'));
		$nomeIn = (string)$this->request->getData('nome_input');
		$nomeClienteSeg = !empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome;

		$okCnpj = ($cnpjIn === $cnpj4Expected);
		$okNome = $this->_seguroPropostaValidateNomeParcial($nomeIn, (string)$nomeClienteSeg);
		if (!$okCnpj || !$okNome) {
			$fail = (int)($st['identity_failures'] ?? 0) + 1;
			$merge = ['identity_failures' => $fail];
			if ($fail >= 3) {
				$merge['blocked_until'] = time() + 1800;
				$merge['awaiting_otp'] = false;
				$merge['otp_hash'] = null;
				$merge['otp_exp'] = null;
				$this->Flash->error(__('Muitas tentativas incorretas. Acesso bloqueado por 30 minutos.'));
			} else {
				$this->Flash->error(__('Dados não conferem. Verifique os últimos dígitos do CNPJ e o nome cadastrado.'));
			}
			$this->_seguroPortalMerge($session, $hash, $merge);
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$to = $this->_seguroClienteEmailValido($orcamento);
		if ($to === null) {
			$this->Flash->error(__('Não há e-mail de faturamento cadastrado para este cliente. Entre em contato com o vendedor.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$otpHash = password_hash($code, PASSWORD_DEFAULT);
		if ($otpHash === false) {
			$this->Flash->error(__('Não foi possível gerar o código. Tente novamente.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		if (!$this->_sendSeguroOtpEmail($orcamento, $to, $code)) {
			$this->Flash->error(__('Não foi possível enviar o e-mail com o código. Tente novamente ou contate o vendedor.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$this->_seguroPortalMerge($session, $hash, [
			'identity_failures' => 0,
			'awaiting_otp' => true,
			'otp_hash' => $otpHash,
			'otp_exp' => time() + 600,
			'otp_failures' => 0,
			'reenviar_count' => 0,
			'email_masked' => $this->_maskEmailForDisplay($to),
		]);
		$this->Flash->success(__('Enviamos um código de 6 dígitos para o e-mail cadastrado.'));
		return $this->redirect(['action' => 'seguroProposta', $hash]);
	}

	protected function _seguroPropostaHandleOtp($orcamento, string $hash) {
		$session = $this->request->getSession();
		$st = $this->_seguroPortalRead($session, $hash);
		if (empty($st['awaiting_otp']) || empty($st['otp_hash']) || empty($st['otp_exp'])) {
			$this->Flash->error(__('Sessão expirada. Informe novamente seus dados para receber um novo código.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}
		$blockedUntil = isset($st['blocked_until']) ? (int)$st['blocked_until'] : 0;
		if ($blockedUntil > time()) {
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}
		if (time() > (int)$st['otp_exp']) {
			$this->Flash->error(__('Código expirado. Solicite um novo código.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$digits = preg_replace('/\D/', '', (string)$this->request->getData('otp_code'));
		if (strlen($digits) !== 6) {
			$this->Flash->error(__('Informe os 6 dígitos do código.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		if (!password_verify($digits, (string)$st['otp_hash'])) {
			$fail = (int)($st['otp_failures'] ?? 0) + 1;
			$merge = ['otp_failures' => $fail];
			if ($fail >= 5) {
				$merge['blocked_until'] = time() + 1800;
				$merge['awaiting_otp'] = false;
				$merge['otp_hash'] = null;
				$merge['otp_exp'] = null;
				$this->Flash->error(__('Muitas tentativas incorretas. Acesso bloqueado por 30 minutos.'));
			} else {
				$this->Flash->error(__('Código incorreto.'));
			}
			$this->_seguroPortalMerge($session, $hash, $merge);
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$this->_seguroPortalUnsetHash($session, $hash);
		$pv = $session->read('PortalSeguroVerificado');
		if (!is_array($pv)) {
			$pv = [];
		}
		$pv[$hash] = time() + 28800;
		$session->write('PortalSeguroVerificado', $pv);

		return $this->redirect(['action' => 'viewhash', $hash]);
	}

	protected function _seguroPropostaHandleReenviarOtp($orcamento, string $hash) {
		$session = $this->request->getSession();
		$st = $this->_seguroPortalRead($session, $hash);
		if (empty($st['awaiting_otp'])) {
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}
		$blockedUntil = isset($st['blocked_until']) ? (int)$st['blocked_until'] : 0;
		if ($blockedUntil > time()) {
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}
		$n = (int)($st['reenviar_count'] ?? 0);
		if ($n >= 3) {
			$this->Flash->error(__('Limite de reenvios atingido. Aguarde ou contate o vendedor.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$to = $this->_seguroClienteEmailValido($orcamento);
		if ($to === null) {
			$this->Flash->error(__('Não há e-mail cadastrado para reenvio.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$otpHash = password_hash($code, PASSWORD_DEFAULT);
		if ($otpHash === false) {
			$this->Flash->error(__('Não foi possível gerar o código.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}
		if (!$this->_sendSeguroOtpEmail($orcamento, $to, $code)) {
			$this->Flash->error(__('Falha ao reenviar o e-mail.'));
			return $this->redirect(['action' => 'seguroProposta', $hash]);
		}

		$this->_seguroPortalMerge($session, $hash, [
			'otp_hash' => $otpHash,
			'otp_exp' => time() + 600,
			'otp_failures' => 0,
			'reenviar_count' => $n + 1,
			'email_masked' => $this->_maskEmailForDisplay($to),
		]);
		$this->Flash->success(__('Novo código enviado por e-mail.'));
		return $this->redirect(['action' => 'seguroProposta', $hash]);
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
				$idorcamento = null;
				if (array_key_exists('idcarrinho', $_SESSION) && $_SESSION['idcarrinho'] !== '' && $_SESSION['idcarrinho'] !== null) {
					$idorcamento = $_SESSION['idcarrinho'];
				}
				if ($idorcamento === null || $idorcamento === '') {
					$idOrc = (int) ($data['id_orcamento'] ?? 0);
					if ($idOrc > 0) {
						$resolved = $this->_orcamentoResolveIditemCarrinhoSession($idOrc);
						if ($resolved !== null) {
							$idorcamento = $resolved;
							$_SESSION['idcarrinho'] = $idorcamento;
						}
					}
				}
				if ($idorcamento === null || $idorcamento === '') {
					return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão do orçamento expirada. Recarregue a página e tente novamente.']))->withStatus(400);
				}
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

			$orcamentond = $this->Orcamentosservicos->newEntity();
			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$orcamentond->idproduto = trim((string)$data['idproduto']);
			}
			$orcamentond->servico = $servico;
			$orcamentond->quantidade = $data['quantidade'] ?? 0;
			$orcamentond->observacao = isset($data['observacao']) ? $data['observacao'] : '';
			$orcamentond->tipo = isset($data['tipo']) ? $data['tipo'] : 0;
			$this->_orcAplicarPrecosLinhaServicoFromRequest($orcamentond, $data);

			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$produto = $this->_orcFindProdutoByIdprodutoCampo($data['idproduto'], $idempresa);
				if ($produto !== null) {
					$dbPreco = (float)($produto->vlunitario ?? 0);
					$qtd = $this->_parseQuantidadeOrcamentoLinha($data['quantidade'] ?? 1);
					if ($qtd <= 0) {
						$qtd = 1.0;
					}
					$formVu = (float)($orcamentond->valoruni ?? 0);
					$formBruto = \App\Utility\OrcamentoDescontoUtil::linhaBruto($orcamentond);
					if ($formVu <= 0.0001 && $dbPreco > 0.0001) {
						$orcamentond->valoruni = $dbPreco;
						$orcamentond->valormensal = 0;
						$orcamentond->valordoservico = $dbPreco * $qtd;
					} elseif ($formBruto <= 0.0001 && $dbPreco > 0.0001) {
						$orcamentond->valoruni = $dbPreco;
						$orcamentond->valormensal = 0;
						$orcamentond->valordoservico = $dbPreco * $qtd;
					} elseif ($formVu > 0.0001 && $formBruto <= 0.0001) {
						$orcamentond->valordoservico = $formVu * $qtd;
					}
				}
			}
			$orcamentond->idempresa = $idempresa;
			$orcamentond->idorcamento = $idorcamento;
			$this->_orcPatchItemDescontoFromRequest($orcamentond, $data);

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
		$this->set('orcItemDescontoEnabled', $this->_orcServicoTemDescontoColunas());
	}
	
	public function carrinhoedit($idorcamento = null){
		$orcamento = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'id' => $idorcamento])->first();
		$idcarrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento])->first();
		$idorcamentoServicos = $idcarrinho ? $idcarrinho->iditem : (isset($_SESSION['idcarrinho']) ? $_SESSION['idcarrinho'] : null);
		$carrinho = $idorcamentoServicos
			? $this->Orcamentosservicos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamentoServicos])->order(['id'])->toArray()
			: [];

		$idempresa = (int)$this->Auth->user('idempresa');
		$carrinhoLinhasExtra = $this->_carrinhoLinhasCustoMargem($carrinho, $idempresa);

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['descricao'])->toArray();
		foreach ($produtosOpt1 as $reg) {
			$produtosOpt[$reg->codigo] = "$reg->descricao ($reg->codigo)";
		}

		$this->set('produtos', $produtosOpt);
		$this->set('carrinho', $carrinho);
		$this->set('carrinhoLinhasExtra', $carrinhoLinhasExtra);
		$this->set('orcamento', $orcamento);
		$this->set('role', $this->Auth->user('role'));
		$layout = (string)$this->request->getQuery('layout', 'form');
		$this->set('orcCarrinhoLayout', $layout === 'revisao' ? 'revisao' : 'form');
		$this->set('orcItemDescontoEnabled', $this->_orcServicoTemDescontoColunas());
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
		if (empty($orcamento)) {
			$this->Flash->error(__('Não foi encontrado um orçamento!'));
			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}
		$obsExtra = '';
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$nome = trim((string)($data['sign_nome'] ?? ''));
			$cpfDigits = preg_replace('/\D/', '', (string)($data['sign_cpf'] ?? ''));
			$termos = !empty($data['aceite_termos']);
			if ($nome === '' || strlen($cpfDigits) < 11 || !$termos) {
				$this->Flash->error(__('Informe nome completo, CPF válido e aceite os termos para aprovar.'));
				return $this->redirect(['action' => 'viewhash', $orcamento->hash]);
			}
			$obsExtra = ' Signatário: ' . $nome . '. CPF informado pelo portal.';
		}
		$sitantiga = $orcamento->status;
		$observacao = 'O orçamento foi aprovado pelo cliente.' . $obsExtra;
		$orcamento->ipaprovacao = get_client_ip();
		$orcamento->navegadoraprovacao = VerificaNavegadorSO();
		$orcamento->status = C_OrcamentoStatusAprovado;

		if ($this->Orcamentos->save($orcamento)) {
			$this->Flash->success('O orçamento foi aprovado com sucesso!');
			$this->criarMov($orcamento->id, $sitantiga, C_OrcamentoStatusAprovado, $observacao, $orcamento->idempresa);
			$uid = $this->Auth->user('id');
			if (!empty($uid)) {
				$this->Atividades->registrar($uid, $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			}
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
			if ($this->_orcSchemaHasColumn('aprovacao_interna')) {
				$ai = (string)($orcamento->aprovacao_interna ?? C_OrcamentoAprovacaoInternaPendente);
				if ($ai !== C_OrcamentoAprovacaoInternaAprovado) {
					$this->Flash->error(__('A proposta precisa da aprovação interna do gerente antes do envio ao cliente.'));

					return $this->redirect(['action' => 'view', $orcamento->id]);
				}
			}
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

		// Link do portal do cliente (acesso seguro → depois viewhash).
		$assinarUrl = null;
		try {
			$base = $this->Config->get(1)->urlfora ?? null;
			if (!empty($base) && !empty($orcamento->hash)) {
				$assinarUrl = $base . 'orcamentos/seguro-proposta/' . $orcamento->hash;
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
				$urlHash = $this->Config->get(1)->urlfora.'orcamentos/seguro-proposta/'.$orcamento->hash;
				$linkacesso .= " ou se não possuir login, acesse <a href='$urlHash'>este link</a> (acesso seguro com verificação)";
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
				$item->servico = $data['servico'];
				$item->quantidade = $data['quantidade'];
				$item->observacao = $data['observacao'];
				$item->idproduto = !empty($data['idproduto']) && $data['idproduto'] != '0'
					? trim((string)$data['idproduto'])
					: $item->idproduto;
				$item->tipo = $data['tipo'];
				$this->_orcAplicarPrecosLinhaServicoFromRequest($item, $data);
				$this->_orcSincronizarPrecoProdutoNaLinhaSeBrutoZero($item, (int)$this->Auth->user('idempresa'));
				$discPost = $data['desconto_valor'] ?? $data['item_desconto_valor'] ?? 0;
				$discPostNum = is_numeric($discPost) ? (float)$discPost : (float)preg_replace('/[^\d.,-]/', '', (string)$discPost);
				$this->_orcPatchItemDescontoFromRequest($item, $data);
				if ($discPostNum > 0 && !$this->_orcServicoTemDescontoColunas()) {
					echo 'error:migration';

					return;
				}

				if ($this->Orcamentosservicos->save($item)) {
					echo 'success';

					return;
				}
			}
			
			echo 'error';
		}
	}
}